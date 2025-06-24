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

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

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
		// add_filter( 'template', [ $this, 'resolve_template' ] );
		// add_filter( 'stylesheet', [ $this, 'resolve_stylesheet' ] );
		
		add_filter( 'template', function( $template ) {
			return ThemeSwitcher::resolve_theme_filter( $template, 'template' );
		} );
		add_filter( 'stylesheet', function( $stylesheet ) {
			return ThemeSwitcher::resolve_theme_filter( $stylesheet, 'stylesheet' );
		} );

		// Theme Set Mode hooks (affects all users)
		add_filter( 'body_class', array( $this, 'add_preview_body_class' ) );
		
		// General hooks (for both modes)
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
		add_action( 'init', array( $this, 'register_post_meta' ) );
		add_action( 'init', array( $this, 'register_term_meta' ) );
		
		// Admin notices
		add_action( 'admin_notices', array( $this, 'maybe_show_theme_missing_notice' ) );

		add_filter( 'preview_post_link', [ $this, 'add_preview_theme_param_to_preview_link' ], 10, 2 );
	}

	/**
	 * Summary of resolve_theme_filter
	 * @param mixed $current_value
	 * @param mixed $which
	 */
	public static function resolve_theme_filter( $current_value, $which ) {
		$settings = get_option( 'smart_theme_switcher_settings', array() );
		$themes   = array_keys( wp_get_themes() );
		$post_id = self::get_post_id_early();
		$theme_slug = false;
		$preview_theme = '';

		// 1. Preview mode support
		$instance = self::get_instance();
		if ( method_exists( $instance, 'get_preview_theme' ) ) {
			$preview_theme = $instance->get_preview_theme();
			if ( ! empty( $preview_theme ) && in_array( $preview_theme, $themes, true ) ) {
				$theme_slug = $preview_theme;
			}
		}

		// 2. Post meta fallback (only if not in preview)
		if ( ! $theme_slug && $post_id ) {
			$post_theme = get_post_meta( $post_id, 'smart_theme_switcher_active_theme', true );
			if ( ! empty( $post_theme ) && in_array( $post_theme, $themes, true ) ) {
				$theme_slug = $post_theme;
			}
		}

		// 3. Global settings fallback
		if ( ! $theme_slug && ! empty( $settings['post_types'] ) && $post_id ) {
			$post_data = get_post( $post_id );
			if ( $post_data ) {
				foreach ( $settings['post_types'] as $post_type => $setting ) {
					if ( $post_data->post_type === $post_type ) {
						if ( ! empty( $setting['theme'] ) && in_array( $setting['theme'], $themes, true ) ) {
							$theme_slug = $setting['theme'];
							break;
						}
					}
				}
			}
		}

		// 4. Validate final theme slug
		if ( ! $theme_slug ) {
			return $current_value;
		}

		$theme = wp_get_theme( $theme_slug );
		if ( ! $theme->exists() ) {
			return $current_value;
		}

		// Log for debugging
		error_log("STS: $which | preview_theme: $preview_theme | post_id: $post_id | theme_slug: $theme_slug | template: ".$theme->get_template()." | stylesheet: ".$theme->get_stylesheet());

		return ( $which === 'template' )
			? $theme->get_template()
			: $theme->get_stylesheet();
	}

	public static function get_post_id_early() {
		// Try ?p=ID for plain permalinks
		if ( isset($_GET['p']) && is_numeric($_GET['p']) ) {
			return (int) $_GET['p'];
		}
		// Pretty permalinks: Try to extract the slug from the URL and find the post
		$request_uri = $_SERVER['REQUEST_URI'];
		$path = trim(parse_url($request_uri, PHP_URL_PATH), '/');
		if ( $path ) {
			// Assumes /post-slug or /category/post-slug format, not 100% bulletproof!
			$parts = explode('/', $path);
			$slug = end($parts);
			$post = get_page_by_path($slug, OBJECT, get_post_types(['public' => true]));
			if ( $post ) {
				return $post->ID;
			}
		}
		return 0;
	}

	public function add_preview_theme_param_to_preview_link( $preview_link, $post ) {
		
		$preview_theme = $this->get_preview_theme_for_post( $post->ID );
		// exit("teee");
		if ( $preview_theme ) {
			$query_param = $this->get_query_param_name();
			$preview_link = add_query_arg( $query_param, $preview_theme, $preview_link );
		}

		return $preview_link;
	}

	public function get_preview_theme_for_post( $post_id ) {
		// 1. Check for theme set in post meta
		$theme = get_post_meta( $post_id, 'smart_theme_switcher_active_theme', true );
	
		if ( ! empty( $theme ) ) {
			return $theme;
		}

		// 2. Fallback to currently active theme
		return get_stylesheet();
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
			STS_PLUGIN_URL . 'assets/dist/preview.css',
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
	 * Register term meta for theme switching.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_term_meta() {
		// Register meta for all public taxonomies.
		foreach ( get_taxonomies( array( 'public' => true ) ) as $taxonomy ) {
			register_term_meta( $taxonomy, 'smart_theme_switcher_active_theme', array(
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
	
	/**
	 * Check if a theme is valid and installed.
	 *
	 * @since 1.0.0
	 * @param string $theme_slug Theme slug to check
	 * @return bool True if theme exists and is valid, false otherwise
	 */
	public function is_valid_theme( $theme_slug ) {
		if ( empty( $theme_slug ) ) {
			return false;
		}
		
		$theme = wp_get_theme( $theme_slug );
		return $theme->exists();
	}
	
	/**
	 * Clear theme caches.
	 * 
	 * Used when a theme assignment is changed to ensure
	 * cached theme values are refreshed.
	 *
	 * @since 1.0.0
	 * @param int $post_id Optional post ID to clear specific cache
	 * @param int $term_id Optional term ID to clear specific cache
	 * @param string $post_type Optional post type to clear specific cache
	 * @param string $taxonomy Optional taxonomy to clear specific cache
	 * @return void
	 */
	public function clear_theme_caches( $post_id = 0, $term_id = 0, $post_type = '', $taxonomy = '' ) {
		// Clear specific caches if IDs or types provided
		if ( $post_id ) {
			wp_cache_delete( 'sts_post_' . $post_id, 'smart_theme_switcher' );
		}
		
		if ( $term_id ) {
			wp_cache_delete( 'sts_term_' . $term_id, 'smart_theme_switcher' );
		}
		
		if ( $post_type ) {
			wp_cache_delete( 'sts_post_type_' . $post_type, 'smart_theme_switcher' );
		}
		
		if ( $taxonomy ) {
			wp_cache_delete( 'sts_taxonomy_' . $taxonomy, 'smart_theme_switcher' );
		}
		
		// Or clear all theme caches if no specific IDs/types provided
		if ( ! $post_id && ! $term_id && ! $post_type && ! $taxonomy ) {
			// Get all cache keys for smart_theme_switcher group and delete them
			// Since wp_cache_flush() is too broad, we have to use creative workarounds
			// We'll use an action to allow other caching plugins to clear their caches
			do_action( 'smart_theme_switcher_clear_caches' );
		}
	}
	
	/**
	 * Maybe show admin notice for missing or broken theme.
	 * 
	 * This function displays a contextual admin notice only on:
	 * - The post edit screen where a theme is assigned but missing
	 * - The taxonomy term edit screen where a theme is assigned but missing
	 * - The plugin settings page
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function maybe_show_theme_missing_notice() {
		global $pagenow, $post, $tag;
		
		// Only show notices to users who can edit themes
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		
		$screen = get_current_screen();
		$missing_theme = false;
		$context = '';
		
		// Check post edit screen
		if ( 'post.php' === $pagenow && $post && isset( $_GET['action'] ) && 'edit' === $_GET['action'] ) {
			$theme_slug = get_post_meta( $post->ID, 'smart_theme_switcher_active_theme', true );
			if ( ! empty( $theme_slug ) ) {
				$theme = wp_get_theme( $theme_slug );
				if ( ! $theme->exists() ) {
					$missing_theme = $theme_slug;
					$context = sprintf( 
						__( 'the post "%s"', 'smart-theme-switcher' ),
						get_the_title( $post->ID )
					);
				}
			}
		}
		
		// Check term edit screen
		if ( 'term.php' === $pagenow && isset( $_GET['tag_ID'] ) ) {
			$term_id = absint( $_GET['tag_ID'] );
			$taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_key( $_GET['taxonomy'] ) : '';
			
			if ( $term_id && $taxonomy ) {
				$theme_slug = get_term_meta( $term_id, 'smart_theme_switcher_active_theme', true );
				if ( ! empty( $theme_slug ) ) {
					$theme = wp_get_theme( $theme_slug );
					if ( ! $theme->exists() ) {
						$missing_theme = $theme_slug;
						$term = get_term( $term_id, $taxonomy );
						$context = sprintf( 
							__( 'the term "%s"', 'smart-theme-switcher' ),
							$term->name
						);
					}
				}
			}
		}
		
		// Check settings page
		if ( isset( $screen->id ) && 'settings_page_smart-theme-switcher' === $screen->id ) {
			// Check post type settings
			$settings = get_option( 'smart_theme_switcher_settings', array() );
			
			if ( isset( $settings['post_types'] ) && is_array( $settings['post_types'] ) ) {
				foreach ( $settings['post_types'] as $post_type => $post_type_settings ) {
					if ( 
						isset( $post_type_settings['enabled'] ) && 
						$post_type_settings['enabled'] && 
						isset( $post_type_settings['theme'] ) && 
						! empty( $post_type_settings['theme'] ) 
					) {
						$theme = wp_get_theme( $post_type_settings['theme'] );
						if ( ! $theme->exists() ) {
							$missing_theme = $post_type_settings['theme'];
							$post_type_obj = get_post_type_object( $post_type );
							$context = sprintf( 
								__( 'all "%s" post types', 'smart-theme-switcher' ),
								$post_type_obj ? $post_type_obj->labels->name : $post_type
							);
							break;
						}
					}
				}
			}
			
			// Check taxonomy settings
			if ( ! $missing_theme && isset( $settings['taxonomies'] ) && is_array( $settings['taxonomies'] ) ) {
				foreach ( $settings['taxonomies'] as $taxonomy => $taxonomy_settings ) {
					if ( 
						isset( $taxonomy_settings['enabled'] ) && 
						$taxonomy_settings['enabled'] && 
						isset( $taxonomy_settings['theme'] ) && 
						! empty( $taxonomy_settings['theme'] ) 
					) {
						$theme = wp_get_theme( $taxonomy_settings['theme'] );
						if ( ! $theme->exists() ) {
							$missing_theme = $taxonomy_settings['theme'];
							$taxonomy_obj = get_taxonomy( $taxonomy );
							$context = sprintf( 
								__( 'all "%s" taxonomy archives', 'smart-theme-switcher' ),
								$taxonomy_obj ? $taxonomy_obj->labels->name : $taxonomy
							);
							break;
						}
					}
				}
			}
		}
		
		// Display notice if needed
		if ( $missing_theme && $context ) {
			$notice = sprintf(
				/* translators: 1: Theme name, 2: Context where theme is assigned */
				__( '<strong>Warning:</strong> The theme "%1$s" is assigned to %2$s but is not installed or is broken. The default theme will be used instead.', 'smart-theme-switcher' ),
				esc_html( $missing_theme ),
				esc_html( $context )
			);
			
			echo '<div class="notice notice-warning is-dismissible"><p>' . $notice . '</p></div>';
		}
	}
}