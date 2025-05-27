<?php
/**
 * Settings Page Class
 *
 * @package SmartThemeSwitcher
 * @since 1.0.0
 */

namespace SmartThemeSwitcher\Admin;

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
			__( 'Smart Theme Switcher Settings', 'smart-theme-switcher' ),
			__( 'Smart Theme Switcher', 'smart-theme-switcher' ),
			'manage_options',
			'smart-theme-switcher',
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
		if ( 'settings_page_smart-theme-switcher' !== $hook ) {
			return;
		}

		// Enqueue React and settings script.
		wp_enqueue_script(
			'sts-settings',
			STS_PLUGIN_URL . 'assets/dist/settings.js',
			array( 
				'wp-element', 
				'wp-components', 
				'wp-api-fetch', 
				'wp-i18n',
				'wp-data',
				'wp-notices',
			),
			STS_PLUGIN_VERSION,
			true
		);

		// Enqueue WP components CSS.
		wp_enqueue_style( 'wp-components' );

		// Enqueue settings CSS.
		wp_enqueue_style(
			'sts-settings',
			STS_PLUGIN_URL . 'assets/dist/settings.css',
			array( 'wp-components' ),
			STS_PLUGIN_VERSION
		);

		// Localize script with data and REST API endpoints.
		wp_localize_script(
			'sts-settings',
			'stsSettings',
			array(
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'restUrl'     => esc_url_raw( rest_url( 'smart-theme-switcher/v1' ) ),
				'nonce'       => wp_create_nonce( 'wp_rest' ),
				'version'     => STS_PLUGIN_VERSION,
				'adminUrl'    => admin_url(),
				'docUrl'      => 'https://github.com/mehul0810/smart-theme-switcher',
				'strings'     => array(
					// Header.
					'pluginName'          => __( 'Smart Theme Switcher', 'smart-theme-switcher' ),
					'settingsTitle'       => __( 'Settings', 'smart-theme-switcher' ),
					'viewDocs'            => __( 'View Documentation', 'smart-theme-switcher' ),
					
					// Tabs.
					'generalTab'          => __( 'General', 'smart-theme-switcher' ),
					'advancedTab'         => __( 'Advanced', 'smart-theme-switcher' ),
					
					// Post Types & Taxonomies.
					'enableForPostType'   => __( 'Enable theme preview for this post type', 'smart-theme-switcher' ),
					'enableForTaxonomy'   => __( 'Enable theme preview for this taxonomy', 'smart-theme-switcher' ),
					'selectTheme'         => __( 'Select theme', 'smart-theme-switcher' ),
					
					// Advanced settings.
					'enableThemePreview'  => __( 'Enable Theme Preview', 'smart-theme-switcher' ),
					'enableDebugging'     => __( 'Enable Debugging', 'smart-theme-switcher' ),
					
					// Actions.
					'save'                => __( 'Save Settings', 'smart-theme-switcher' ),
					'saving'              => __( 'Saving...', 'smart-theme-switcher' ),
					'saved'               => __( 'Settings Saved', 'smart-theme-switcher' ),
					
					// Messages.
					'loading'             => __( 'Loading...', 'smart-theme-switcher' ),
					'error'               => __( 'Error Saving Settings', 'smart-theme-switcher' ),
					'success'             => __( 'Settings saved successfully!', 'smart-theme-switcher' ),
					'useActiveTheme'      => __( 'Use Active Theme', 'smart-theme-switcher' ),
				),
			)
		);
	}
}