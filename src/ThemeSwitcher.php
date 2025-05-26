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
 * Handles the core functionality of theme switching with two separate modes:
 * 
 * 1. Preview Mode: For administrators/editors to preview themes privately
 *    - Only visible to logged-in users with proper capabilities
 *    - Enabled/disabled via settings
 *    - Shows a preview banner
 *    - Does not affect visitors or SEO
 *
 * 2. Theme Set Mode: For setting specific themes for posts, types, taxonomies
 *    - Visible to all users (logged-in and visitors)
 *    - Affects SEO and all site visitors
 *    - Based on individual post settings or post type/taxonomy settings
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
		// Theme Set Mode hooks (affects all users)
		add_filter( 'template', array( $this, 'set_theme_template' ) );
		add_filter( 'stylesheet', array( $this, 'set_theme_stylesheet' ) );
		add_filter( 'template_include', array( $this, 'set_theme_template_include' ), 999 );
		
		// Preview Mode hooks (admin-only)
		add_filter( 'template', array( $this, 'preview_theme_template' ) );
		add_filter( 'stylesheet', array( $this, 'preview_theme_stylesheet' ) );
		add_filter( 'template_include', array( $this, 'preview_theme_template_include' ), 1000 );
		add_filter( 'body_class', array( $this, 'add_preview_body_class' ) );
		
		// General hooks (for both modes)
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
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
	 * Get preview theme for current request (Preview Mode - admin only).
	 *
	 * Preview mode is only available to authorized users and when enabled in settings.
	 * This theme is only shown to the current user and never affects visitors or SEO.
	 *
	 * @since 1.0.0
	 * @return string|bool Theme slug or false if preview mode is disabled or user can't preview.
	 */
	public function get_preview_theme() {
		// Only proceed if Preview Mode is enabled in settings
		if ( ! $this->theme_resolver->is_preview_mode_enabled() ) {
			return false;
		}
		
		// Only proceed if current user has permission to preview themes
		if ( ! $this->can_user_preview() ) {
			return false;
		}
		
		// Get theme from query parameter
		$preview_theme = $this->theme_resolver->get_preview_theme_from_query();
		if ( ! empty( $preview_theme ) ) {
			return $preview_theme;
		}
		
		return false;
	}

	/**
	 * Check if user can preview themes.
	 *
	 * This is part of Preview Mode which is separate from Theme Set Mode.
	 * By default, only logged-in users with edit_posts capability can preview themes.
	 * This can be filtered to extend or restrict access to theme previews.
	 *
	 * @since 1.0.0
	 * @return bool Whether the current user can preview themes.
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
	 * Filter the theme template for Preview Mode (admin only).
	 * 
	 * This filter is only applied for authorized users in preview mode.
	 * It never affects visitors or SEO.
	 *
	 * @since 1.0.0
	 * @param string $template Current theme template.
	 * @return string New theme template or unchanged if not in preview mode.
	 */
	public function preview_theme_template( $template ) {
		// Check if we're in preview mode (admin only)
		$preview_theme = $this->get_preview_theme();
		if ( $preview_theme ) {
			$theme = wp_get_theme( $preview_theme );
			if ( $theme->exists() ) {
				return $theme->get_template();
			}
		}
		return $template;
	}
	
	/**
	 * Filter the theme template for Theme Set Mode (all users).
	 * 
	 * This filter applies to all visitors and affects SEO.
	 * It uses the theme set for individual posts, post types, or taxonomies.
	 *
	 * @since 1.0.0
	 * @param string $template Current theme template.
	 * @return string New theme template or unchanged if no theme is set.
	 */
	public function set_theme_template( $template ) {
		// Skip if we're in preview mode - that takes precedence
		if ( $this->get_preview_theme() ) {
			return $template;
		}
		
		// Get theme set for the current post/archive (visible to all users)
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
	 * Filter the theme stylesheet for Preview Mode (admin only).
	 * 
	 * This filter is only applied for authorized users in preview mode.
	 * It never affects visitors or SEO.
	 *
	 * @since 1.0.0
	 * @param string $stylesheet Current theme stylesheet.
	 * @return string New theme stylesheet or unchanged if not in preview mode.
	 */
	public function preview_theme_stylesheet( $stylesheet ) {
		// Check if we're in preview mode (admin only)
		$preview_theme = $this->get_preview_theme();
		if ( $preview_theme ) {
			$theme = wp_get_theme( $preview_theme );
			if ( $theme->exists() ) {
				return $theme->get_stylesheet();
			}
		}
		return $stylesheet;
	}
	
	/**
	 * Filter the theme stylesheet for Theme Set Mode (all users).
	 * 
	 * This filter applies to all visitors and affects SEO.
	 * It uses the theme set for individual posts, post types, or taxonomies.
	 *
	 * @since 1.0.0
	 * @param string $stylesheet Current theme stylesheet.
	 * @return string New theme stylesheet or unchanged if no theme is set.
	 */
	public function set_theme_stylesheet( $stylesheet ) {
		// Skip if we're in preview mode - that takes precedence
		if ( $this->get_preview_theme() ) {
			return $stylesheet;
		}
		
		// Get theme set for the current post/archive (visible to all users)
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
	 * Filter template include for Preview Mode (admin only).
	 *
	 * This filter is only applied for authorized users in preview mode.
	 * It never affects visitors or SEO.
	 *
	 * @since 1.0.0
	 * @param string $template The path of the template to include.
	 * @return string The path of the template to include.
	 */
	public function preview_theme_template_include( $template ) {
		// Only proceed if we're in preview mode (admin only)
		$preview_theme = $this->get_preview_theme();
		if ( ! $preview_theme ) {
			return $template;
		}
		
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
	
	/**
	 * Filter template include for Theme Set Mode (all users).
	 *
	 * This filter applies to all visitors and affects SEO.
	 * It uses the theme set for individual posts, post types, or taxonomies.
	 *
	 * @since 1.0.0
	 * @param string $template The path of the template to include.
	 * @return string The path of the template to include.
	 */
	public function set_theme_template_include( $template ) {
		// Skip if we're in preview mode - that takes precedence
		if ( $this->get_preview_theme() ) {
			return $template;
		}
		
		// Get theme set for the current post/archive (visible to all users)
		$assigned_theme = $this->get_assigned_theme();
		if ( ! $assigned_theme ) {
			return $template;
		}
		
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

	/**
	 * Add body class for Preview Mode (admin only).
	 *
	 * This only applies to authorized users in preview mode.
	 * It adds classes to help style the preview experience differently.
	 *
	 * @since 1.0.0
	 * @param array $classes Array of body classes.
	 * @return array Modified array of body classes.
	 */
	public function add_preview_body_class( $classes ) {
		// Only proceed if we're in preview mode (admin only)
		$preview_theme = $this->get_preview_theme();
		if ( ! $preview_theme ) {
			return $classes;
		}
		
		// Add classes for preview mode
		$classes[] = 'sts-preview-mode';
		$classes[] = 'sts-preview-' . sanitize_html_class( $preview_theme );
		
		return $classes;
	}

	/**
	 * Enqueue scripts and styles for Preview Mode.
	 *
	 * These scripts and styles are only loaded for users who can preview themes.
	 * They never affect regular visitors.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_scripts() {
		// Check if preview mode is enabled in settings
		if ( ! $this->theme_resolver->is_preview_mode_enabled() ) {
			return;
		}
		
		// Only enqueue for users who can preview themes
		if ( ! $this->can_user_preview() ) {
			return;
		}

		// Enqueue preview CSS
		wp_enqueue_style(
			'sts-preview',
			STS_PLUGIN_URL . 'assets/dist/sts-preview.css',
			array(),
			STS_PLUGIN_VERSION
		);

		// Enqueue preview JS
		wp_enqueue_script(
			'sts-preview',
			STS_PLUGIN_URL . 'assets/dist/preview.js',
			array( 'jquery' ),
			STS_PLUGIN_VERSION,
			true
		);

		// Localize script
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
	 * Enqueue editor assets for Theme Set Mode.
	 *
	 * These assets allow setting a theme for individual posts in the editor.
	 * This is separate from Preview Mode and affects all users.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_editor_assets() {
		// Only enqueue for users who can assign themes to posts
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		// Enqueue editor script
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

		// Get all themes
		$themes = wp_get_themes();
		$theme_options = array();

		foreach ( $themes as $theme_slug => $theme ) {
			$theme_options[] = array(
				'label' => $theme->get( 'Name' ),
				'value' => $theme_slug,
			);
		}

		// Localize script
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