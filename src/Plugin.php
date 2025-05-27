<?php
/**
 * Main Plugin Class
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
 * Main Plugin Class.
 *
 * @since 1.0.0
 */
final class Plugin {

	/**
	 * Singleton instance of the class.
	 *
	 * @since 1.0.0
	 * @var Plugin
	 */
	private static $instance;

	/**
	 * Main Plugin Instance.
	 *
	 * Ensures only one instance of Plugin is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @return Plugin - Main instance.
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Plugin ) ) {
			self::$instance = new Plugin();
			self::$instance->setup();
			self::$instance->init();
			self::$instance->hooks();
		}

		return self::$instance;
	}

	/**
	 * Setup plugin constants.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function setup() {
		// Register activation hook.
		register_activation_hook( STS_PLUGIN_FILE, array( $this, 'activate' ) );

		// Register deactivation hook.
		register_deactivation_hook( STS_PLUGIN_FILE, array( $this, 'deactivate' ) );
	}

	/**
	 * Initialize plugin components.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function init() {
		// Initialize core components.
		new ThemeSwitcher();
		new Settings();
		
		// Initialize admin components if in admin area.
		if ( is_admin() ) {
			new Admin\Admin();
			new Admin\SettingsPage();
		}

		// Initialize frontend components.
		new Frontend\Frontend();
		new Frontend\PreviewBanner();
	}

	/**
	 * Setup hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function hooks() {
		// Load text domain.
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Activation hook.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function activate() {
		// Set default options.
		$default_options = array(
			'enable_preview'        => 'yes',
			'preview_query_param'   => STS_DEFAULT_QUERY_PARAM,
			'advanced'              => array(
				'preview_enabled'     => true,
				'debug_enabled'       => false,
			),
		);

		// Add options if they don't exist.
		add_option( 'smart_theme_switcher_settings', $default_options );

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Deactivation hook.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function deactivate() {
		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Load plugin text domain.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'smart-theme-switcher',
			false,
			dirname( plugin_basename( STS_PLUGIN_FILE ) ) . '/languages/'
		);
	}
}