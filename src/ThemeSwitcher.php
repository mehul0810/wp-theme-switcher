<?php
/**
 * Theme Switcher Class
 *
 * @package SmartThemeSwitcher
 * @since 1.0.0
 */

namespace SmartThemeSwitcher;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ThemeSwitcher Class.
 *
 * Handles the core functionality of theme switching.
 * 
 * This class works with ThemeResolver to determine which theme to load
 * based on a priority system:
 * 1. Individual post setting (highest priority)
 * 2. Post type or taxonomy setting (medium priority)
 * 3. Global/default theme (lowest priority)
 *
 * @since 1.0.0
 */
class ThemeSwitcher {

	/**
	 * Theme resolver instance.
	 *
	 * @since 1.0.0
	 * @var ThemeResolver
	 */
	private $theme_resolver;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Initialize theme resolver.
		$this->theme_resolver = new ThemeResolver();
		
		// Initialize hooks.
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function init_hooks() {
		// Filter to switch theme.
		add_filter( 'template', array( $this, 'preview_theme_template' ) );
		add_filter( 'stylesheet', array( $this, 'preview_theme_stylesheet' ) );
		
		// Template include filter for complete theme switching.
		add_filter( 'template_include', array( $this, 'preview_theme_template_include' ), 999 );
		
		// Add body class for preview mode.
		add_filter( 'body_class', array( $this, 'add_preview_body_class' ) );
		
		// Enqueue scripts and styles.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		
		// Initialize editor assets.
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
		
		// Register post meta for theme switching.
		add_action( 'init', array( $this, 'register_post_meta' ) );
	}

	/**
	 * Get the theme to use for the current request (persistent for all users).
	 *
	 * @since 1.0.0
	 * @return string|bool Theme slug or false if no override.
	 */
	public function get_assigned_theme() {
		// Use theme resolver to determine the appropriate theme based on priority (post meta, post type/tax, fallback)
		return $this->theme_resolver->resolve_theme();
	}

	/**
	 * Get preview theme name for current request (admin preview mode).
	 *
	 * Uses ThemeResolver to determine theme with proper priority:
	 * 1. Preview parameter in URL (only for authorized users)
	 * 2. Theme set in individual post (post meta)
	 * 3. Theme set per post type or taxonomy from settings
	 * 4. Active theme (no override)
	 *
	 * @since 1.0.0
	 * @return string|bool Theme slug or false if no override.
	 */
	public function get_preview_theme() {
		// Only for preview mode (admins/editors, preview enabled, and query param present)
		$settings = get_option('smart_theme_switcher_settings', array());
		$preview_enabled = isset($settings['enable_preview']) && $settings['enable_preview'] === 'yes';
		if ( ! $preview_enabled || ! $this->can_user_preview() ) {
			return false;
		}
		$preview_theme = $this->theme_resolver->get_preview_theme_from_query();
		if ( ! empty($preview_theme) ) {
			return $preview_theme;
		}
		return false;
	}

	/**
	 * Check if user can preview themes.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function can_user_preview() {
		/**
		 * Filter whether the current user can preview themes.
		 *
		 * @since 1.0.0
		 * @param bool $can_preview Whether the current user can preview themes.
		 */
		return apply_filters(
			'smart_theme_switcher_can_user_preview',
			is_user_logged_in() && current_user_can( 'edit_posts' )
		);
	}

	/**
	 * Filter the theme template.
	 *
	 * @since 1.0.0
	 * @param string $template Current theme template.
	 * @return string New theme template.
	 */
	public function preview_theme_template( $template ) {
		// Preview mode (admin-only, query param)
		$preview_theme = $this->get_preview_theme();
		if ( $preview_theme ) {
			$theme = wp_get_theme( $preview_theme );
			if ( $theme->exists() ) {
				return $theme->get_template();
			}
		}
		// Persistent assignment (all users)
		$assigned_theme = $this->get_assigned_theme();
		if ( $assigned_theme ) {
			$theme = wp_get_theme( $assigned_theme );
			if ( $theme->exists() ) {
				return $theme->get_template();
			}
		}
		return $template;
	}

	/**
	 * Filter the theme stylesheet.
	 *
	 * @since 1.0.0
	 * @param string $stylesheet Current theme stylesheet.
	 * @return string New theme stylesheet.
	 */
	public function preview_theme_stylesheet( $stylesheet ) {
		$preview_theme = $this->get_preview_theme();
		if ( $preview_theme ) {
			$theme = wp_get_theme( $preview_theme );
			if ( $theme->exists() ) {
				return $theme->get_stylesheet();
			}
		}
		$assigned_theme = $this->get_assigned_theme();
		if ( $assigned_theme ) {
			$theme = wp_get_theme( $assigned_theme );
			if ( $theme->exists() ) {
				return $theme->get_stylesheet();
			}
		}
		return $stylesheet;
	}

	/**
	 * Filter template include for theme preview and persistent assignment.
	 *
	 * @since 1.0.0
	 * @param string $template The path of the template to include.
	 * @return string The path of the template to include.
	 */
	public function preview_theme_template_include( $template ) {
		// 1. Preview mode (admin-only, query param, preview enabled)
		$preview_theme = $this->get_preview_theme();
		if ( $preview_theme ) {
			$theme = wp_get_theme( $preview_theme );
			if ( $theme->exists() ) {
				$template_file = basename( $template );
				$theme_template = $theme->get_stylesheet_directory() . '/' . $template_file;
				if ( file_exists( $theme_template ) ) {
					return $theme_template;
				}
				// Fallback to theme index.php if template file missing
				$theme_index = $theme->get_stylesheet_directory() . '/index.php';
				if ( file_exists( $theme_index ) ) {
					return $theme_index;
				}
			}
			// If preview theme is invalid, fallback to default template
			return $template;
		}

		// 2. Persistent assignment (all users, per-post, post type, taxonomy)
		$assigned_theme = $this->get_assigned_theme();
		if ( $assigned_theme ) {
			$theme = wp_get_theme( $assigned_theme );
			if ( $theme->exists() ) {
				$template_file = basename( $template );
				$theme_template = $theme->get_stylesheet_directory() . '/' . $template_file;
				if ( file_exists( $theme_template ) ) {
					return $theme_template;
				}
				// Fallback to theme index.php if template file missing
				$theme_index = $theme->get_stylesheet_directory() . '/index.php';
				if ( file_exists( $theme_index ) ) {
					return $theme_index;
				}
			}
			// If assigned theme is invalid, fallback to default template
			return $template;
		}

		// 3. Default: use active theme
		return $template;
	}

	/**
	 * Add body class for preview mode.
	 *
	 * @since 1.0.0
	 * @param array $classes Array of body classes.
	 * @return array Modified array of body classes.
	 */
	public function add_preview_body_class( $classes ) {
		// Only proceed if user can preview and we're in preview mode.
		if ( $this->can_user_preview() && $this->get_preview_theme() ) {
			$classes[] = 'sts-preview-mode';
			$classes[] = 'sts-preview-' . sanitize_html_class( $this->get_preview_theme() );
		}
		
		return $classes;
	}

	/**
	 * Enqueue scripts and styles for the frontend.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_scripts() {
		// Only enqueue for logged-in users who can preview.
		if ( ! $this->can_user_preview() ) {
			return;
		}

		// Enqueue preview CSS.
		wp_enqueue_style(
			'sts-preview',
			STS_PLUGIN_URL . 'assets/dist/sts-preview.css',
			array(),
			STS_PLUGIN_VERSION
		);

		// Enqueue preview JS.
		wp_enqueue_script(
			'sts-preview',
			STS_PLUGIN_URL . 'assets/dist/preview.js',
			array( 'jquery' ),
			STS_PLUGIN_VERSION,
			true
		);

		// Localize script.
		wp_localize_script(
			'sts-preview',
			'Preview',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'sts-preview-nonce' ),
				'currentTheme'  => $this->get_preview_theme(),
				'queryParam'    => $this->get_query_param_name(),
				'isPreviewMode' => (bool) $this->get_preview_theme(),
			)
		);
	}

	/**
	 * Enqueue editor assets.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_editor_assets() {
		// Only enqueue for users who can preview.
		if ( ! $this->can_user_preview() ) {
			return;
		}

		// Enqueue editor script.
		wp_enqueue_script(
			'sts-editor',
			STS_PLUGIN_URL . 'assets/dist/individual.js',
			array(
				'wp-blocks',
				'wp-element',
				'wp-editor',
				'wp-components',
				'wp-i18n',
				'wp-plugins',
				'wp-edit-post',
				'wp-data',
			),
			STS_PLUGIN_VERSION,
			true
		);

		// Get all themes.
		$themes = wp_get_themes();
		$theme_options = array();

		foreach ( $themes as $theme_slug => $theme ) {
			$theme_options[] = array(
				'label' => $theme->get( 'Name' ),
				'value' => $theme_slug,
			);
		}

		// Localize script.
		wp_localize_script(
			'sts-editor',
			'stsEditor',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'sts-editor-nonce' ),
				'themes'       => $theme_options,
				'currentUrl'   => get_preview_post_link(),
				'queryParam'   => $this->get_query_param_name(),
				'activeTheme'  => wp_get_theme()->get_stylesheet(),
			)
		);
	}

	/**
	 * Register post meta for theme switching.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_post_meta() {
		// Register meta for all public post types.
		foreach ( get_post_types( array( 'public' => true ) ) as $post_type ) {
			register_post_meta( $post_type, 'smart_theme_switcher_active_theme', array(
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'string',
				'auth_callback'     => function() { return current_user_can( 'edit_posts' ); },
				'sanitize_callback' => 'sanitize_text_field',
			) );
		}
	}

	/**
	 * Get query parameter name from settings.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_query_param_name() {
		return $this->theme_resolver->get_query_param_name();
	}

	/**
	 * Get available themes.
	 *
	 * @since 1.0.0
	 * @return array Array of themes.
	 */
	public function get_available_themes() {
		$themes = wp_get_themes();
		$theme_options = array();

		foreach ( $themes as $theme_slug => $theme ) {
			$theme_options[ $theme_slug ] = $theme->get( 'Name' );
		}

		return $theme_options;
	}
}