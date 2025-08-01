<?php
/**
 * Settings Page Class
 *
 * @package WpThemeSwitcher
 * @since 1.0.0
 */

namespace WpThemeSwitcher\Admin;

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
			__( 'WP Theme Switcher Settings', 'wts-theme-switcher' ),
			__( 'WP Theme Switcher', 'wts-theme-switcher' ),
			'manage_options',
			'wts-theme-switcher',
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
		<div id="sts-settings-app" class="sts-settings-app-wrap"></div>
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
		if ( 'settings_page_wts-theme-switcher' !== $hook ) {
			return;
		}

		// Enqueue React and settings script.
		wp_enqueue_script(
			'sts-settings',
			WTS_PLUGIN_URL . 'assets/dist/settings.js',
			array( 
				'wp-element', 
				'wp-components', 
				'wp-api-fetch', 
				'wp-i18n',
				'wp-data',
				'wp-notices',
			),
			WTS_PLUGIN_VERSION,
			true
		);

		// Enqueue WP components CSS.
		wp_enqueue_style( 'wp-components' );

		// Enqueue settings CSS.
		wp_enqueue_style(
			'sts-settings',
			WTS_PLUGIN_URL . 'assets/dist/settings.css',
			array( 'wp-components' ),
			WTS_PLUGIN_VERSION
		);

		// Localize script with data and REST API endpoints.
		wp_localize_script(
			'sts-settings',
			'stsSettings',
			array(
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'restUrl'     => esc_url_raw( rest_url( 'wts-theme-switcher/v1' ) ),
				'nonce'       => wp_create_nonce( 'wp_rest' ),
				'version'     => WTS_PLUGIN_VERSION,
				'adminUrl'    => admin_url(),
				'docUrl'      => 'https://github.com/mehul0810/wp-theme-switcher',
				'strings'     => array(
					// Header.
					'pluginName'          => __( 'WP Theme Switcher', 'wts-theme-switcher' ),
					'settingsTitle'       => __( 'Settings', 'wts-theme-switcher' ),
					'viewDocs'            => __( 'View Documentation', 'wts-theme-switcher' ),
					
					// Tabs.
					'generalTab'          => __( 'General', 'wts-theme-switcher' ),
					'advancedTab'         => __( 'Advanced', 'wts-theme-switcher' ),
					
					// Post Types & Taxonomies.
					'enableForPostType'   => __( 'Enable theme preview for this post type', 'wts-theme-switcher' ),
					'enableForTaxonomy'   => __( 'Enable theme preview for this taxonomy', 'wts-theme-switcher' ),
					'selectTheme'         => __( 'Select theme', 'wts-theme-switcher' ),
					
					// Advanced settings.
					'enableThemePreview'  => __( 'Enable Theme Preview', 'wts-theme-switcher' ),
					'enableDebugging'     => __( 'Enable Debugging', 'wts-theme-switcher' ),
					
					// Actions.
					'save'                => __( 'Save Settings', 'wts-theme-switcher' ),
					'saving'              => __( 'Saving...', 'wts-theme-switcher' ),
					'saved'               => __( 'Settings Saved', 'wts-theme-switcher' ),
					
					// Messages.
					'loading'             => __( 'Loading...', 'wts-theme-switcher' ),
					'error'               => __( 'Error Saving Settings', 'wts-theme-switcher' ),
					'success'             => __( 'Settings saved successfully!', 'wts-theme-switcher' ),
					'useActiveTheme'      => __( 'Use Active Theme', 'wts-theme-switcher' ),
				),
			)
		);
	}
}