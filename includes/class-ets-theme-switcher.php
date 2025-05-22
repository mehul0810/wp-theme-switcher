<?php
/**
 * Theme Switcher Class
 *
 * @package EasyThemeSwitcher
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ETS_Theme_Switcher Class.
 *
 * Handles the core functionality of theme switching.
 *
 * @since 1.0.0
 */
class ETS_Theme_Switcher {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
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
	}

	/**
	 * Get preview theme name from query parameter.
	 *
	 * @since 1.0.0
	 * @return string|bool Theme name or false if not in preview mode.
	 */
	public function get_preview_theme() {
		// Get settings.
		$settings = get_option( 'ets_settings', array() );
		
		// Get query parameter name.
		$query_param = isset( $settings['preview_query_param'] ) ? $settings['preview_query_param'] : ETS_DEFAULT_QUERY_PARAM;
		
		// Check if preview parameter exists.
		if ( isset( $_GET[ $query_param ] ) && ! empty( $_GET[ $query_param ] ) ) {
			return sanitize_text_field( $_GET[ $query_param ] );
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
		return is_user_logged_in() && current_user_can( 'edit_posts' );
	}

	/**
	 * Filter the theme template.
	 *
	 * @since 1.0.0
	 * @param string $template Current theme template.
	 * @return string New theme template.
	 */
	public function preview_theme_template( $template ) {
		// Only proceed if user can preview and we're in preview mode.
		if ( ! $this->can_user_preview() || ! $this->get_preview_theme() ) {
			return $template;
		}

		$theme = wp_get_theme( $this->get_preview_theme() );
		
		if ( $theme->exists() ) {
			return $theme->get_template();
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
		// Only proceed if user can preview and we're in preview mode.
		if ( ! $this->can_user_preview() || ! $this->get_preview_theme() ) {
			return $stylesheet;
		}

		$theme = wp_get_theme( $this->get_preview_theme() );
		
		if ( $theme->exists() ) {
			return $theme->get_stylesheet();
		}
		
		return $stylesheet;
	}

	/**
	 * Filter template include for theme preview.
	 *
	 * @since 1.0.0
	 * @param string $template The path of the template to include.
	 * @return string The path of the template to include.
	 */
	public function preview_theme_template_include( $template ) {
		// Only proceed if user can preview and we're in preview mode.
		if ( ! $this->can_user_preview() || ! $this->get_preview_theme() ) {
			return $template;
		}

		// Verify the theme exists.
		$theme = wp_get_theme( $this->get_preview_theme() );
		
		if ( ! $theme->exists() ) {
			return $template;
		}

		// Get the template filename.
		$template_file = basename( $template );
		
		// Look for the template in the preview theme.
		$theme_template = $theme->get_stylesheet_directory() . '/' . $template_file;
		
		if ( file_exists( $theme_template ) ) {
			return $theme_template;
		}
		
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
			$classes[] = 'ets-preview-mode';
			$classes[] = 'ets-preview-' . sanitize_html_class( $this->get_preview_theme() );
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
			'ets-preview',
			ETS_PLUGIN_URL . 'assets/css/ets-preview.css',
			array(),
			ETS_PLUGIN_VERSION
		);

		// Enqueue preview JS.
		wp_enqueue_script(
			'ets-preview',
			ETS_PLUGIN_URL . 'assets/js/ets-preview.js',
			array( 'jquery' ),
			ETS_PLUGIN_VERSION,
			true
		);

		// Localize script.
		wp_localize_script(
			'ets-preview',
			'etsPreview',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'ets-preview-nonce' ),
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
			'ets-editor',
			ETS_PLUGIN_URL . 'assets/js/ets-editor.js',
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
			ETS_PLUGIN_VERSION,
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
			'ets-editor',
			'etsEditor',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'ets-editor-nonce' ),
				'themes'       => $theme_options,
				'currentUrl'   => get_preview_post_link(),
				'queryParam'   => $this->get_query_param_name(),
				'activeTheme'  => wp_get_theme()->get_stylesheet(),
			)
		);
	}

	/**
	 * Get query parameter name from settings.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_query_param_name() {
		$settings = get_option( 'ets_settings', array() );
		return isset( $settings['preview_query_param'] ) ? $settings['preview_query_param'] : ETS_DEFAULT_QUERY_PARAM;
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

// Initialize Theme Switcher.
new ETS_Theme_Switcher();