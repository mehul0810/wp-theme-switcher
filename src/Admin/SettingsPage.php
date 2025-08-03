<?php
/**
 * Settings Page Class
 *
 * @package WPThemeSwitcher
 * @since 1.0.0
 */

namespace WPThemeSwitcher\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SettingsPage Class.
 *
 * Handles the settings page in admin.
 *
 * @since 1.0.0
 */
class SettingsPage {

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
		// Add settings page.
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );

		// Enqueue admin scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Add settings page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'WP Theme Switcher Settings', 'wpts-theme-switcher' ),
			__( 'WP Theme Switcher', 'wpts-theme-switcher' ),
			'manage_options',
			'wpts-theme-switcher',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Render settings page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_settings_page() {
		// Modern, no wrapper approach - just the container for React to render in.
		?>
		<div id="wpts-settings-app" class="wpts-settings-app-wrap"></div>
		<?php
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @since 1.0.0
	 * @param string $hook Hook name.
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook ) {
		// Only enqueue on our settings page.
		if ( 'settings_page_wpts-theme-switcher' !== $hook ) {
			return;
		}

		// Enqueue React and settings script.
		wp_enqueue_script(
			'wpts-settings',
			WPTS_PLUGIN_URL . 'assets/dist/settings.js',
			array( 
				'wp-element', 
				'wp-components', 
				'wp-api-fetch', 
				'wp-i18n',
				'wp-data',
				'wp-notices',
			),
			WPTS_PLUGIN_VERSION,
			true
		);

		// Enqueue WP components CSS.
		wp_enqueue_style( 'wp-components' );

		// Enqueue settings CSS.
		wp_enqueue_style(
			'wpts-settings',
			WPTS_PLUGIN_URL . 'assets/dist/settings.css',
			array( 'wp-components' ),
			WPTS_PLUGIN_VERSION
		);

		// Localize script with data and REST API endpoints.
		wp_localize_script(
			'wpts-settings',
			'wptsSettings',
			array(
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'restUrl'     => esc_url_raw( rest_url( 'wpts-theme-switcher/v1' ) ),
				'nonce'       => wp_create_nonce( 'wp_rest' ),
				'version'     => WPTS_PLUGIN_VERSION,
				'adminUrl'    => admin_url(),
				'docUrl'      => 'https://github.com/mehul0810/wp-theme-switcher',
				'strings'     => array(
					// Header.
					'pluginName'          => __( 'WP Theme Switcher', 'wpts-theme-switcher' ),
					'settingsTitle'       => __( 'Settings', 'wpts-theme-switcher' ),
					'viewDocs'            => __( 'View Documentation', 'wpts-theme-switcher' ),
					'pluginLogo'          => esc_url( WPTS_PLUGIN_URL . 'assets/dist/images/logo.png' ),

					// Tabs.
					'generalTab'          => __( 'General', 'wpts-theme-switcher' ),
					'advancedTab'         => __( 'Advanced', 'wpts-theme-switcher' ),
					
					// Post Types & Taxonomies.
					//'enableForTaxonomy'   => __( 'Enable theme preview for this taxonomy', 'smart-theme-switcher' ),
					'selectTheme'         => __( 'Select theme', 'smart-theme-switcher' ),
					
					// Advanced settings.
					'enableThemePreview'  => __( 'Enable Theme Preview', 'wpts-theme-switcher' ),
					'enableDebugging'     => __( 'Enable Debugging', 'wpts-theme-switcher' ),
					
					// Actions.
					'save'                => __( 'Save Settings', 'wpts-theme-switcher' ),
					'saving'              => __( 'Saving...', 'wpts-theme-switcher' ),
					'saved'               => __( 'Settings Saved', 'wpts-theme-switcher' ),
					
					// Messages.
					'loading'             => __( 'Loading...', 'wpts-theme-switcher' ),
					'error'               => __( 'Error Saving Settings', 'wpts-theme-switcher' ),
					'success'             => __( 'Settings saved successfully!', 'wpts-theme-switcher' ),
					'useActiveTheme'      => __( 'Use Active Theme', 'wpts-theme-switcher' ),
				),
			)
		);
	}
}