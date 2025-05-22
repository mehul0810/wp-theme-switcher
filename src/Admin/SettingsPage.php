<?php
/**
 * Settings Page Class
 *
 * @package EasyThemeSwitcher
 * @since 1.0.0
 */

namespace EasyThemeSwitcher\Admin;

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
			__( 'Easy Theme Switcher Settings', 'easy-theme-switcher' ),
			__( 'Easy Theme Switcher', 'easy-theme-switcher' ),
			'manage_options',
			'easy-theme-switcher',
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
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<div id="ets-settings-app"></div>
		</div>
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
		if ( 'settings_page_easy-theme-switcher' !== $hook ) {
			return;
		}

		// Enqueue React and settings script.
		wp_enqueue_script(
			'ets-settings',
			ETS_PLUGIN_URL . 'assets/dist/js/ets-settings.js',
			array( 'wp-element', 'wp-components', 'wp-api-fetch' ),
			ETS_PLUGIN_VERSION,
			true
		);

		// Enqueue WP components CSS.
		wp_enqueue_style(
			'wp-components'
		);

		// Enqueue settings CSS.
		wp_enqueue_style(
			'ets-settings',
			ETS_PLUGIN_URL . 'assets/dist/css/ets-settings.css',
			array( 'wp-components' ),
			ETS_PLUGIN_VERSION
		);

		// Localize script.
		wp_localize_script(
			'ets-settings',
			'etsSettings',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'ets-settings-nonce' ),
				'strings' => array(
					'save'                => __( 'Save Settings', 'easy-theme-switcher' ),
					'saving'              => __( 'Saving...', 'easy-theme-switcher' ),
					'saved'               => __( 'Settings Saved', 'easy-theme-switcher' ),
					'error'               => __( 'Error Saving', 'easy-theme-switcher' ),
					'enableBanner'        => __( 'Enable Preview Banner', 'easy-theme-switcher' ),
					'enableBannerHelp'    => __( 'Display a banner at the top of the page when previewing a theme.', 'easy-theme-switcher' ),
					'defaultTheme'        => __( 'Default Preview Theme', 'easy-theme-switcher' ),
					'defaultThemeHelp'    => __( 'Select the default theme for new previews.', 'easy-theme-switcher' ),
					'queryParam'          => __( 'Query Parameter', 'easy-theme-switcher' ),
					'queryParamHelp'      => __( 'Customize the URL parameter used for theme previews.', 'easy-theme-switcher' ),
					'settingsTitle'       => __( 'Easy Theme Switcher Settings', 'easy-theme-switcher' ),
					'settingsDescription' => __( 'Configure how theme previews work on your site.', 'easy-theme-switcher' ),
					'yes'                 => __( 'Yes', 'easy-theme-switcher' ),
					'no'                  => __( 'No', 'easy-theme-switcher' ),
					'loading'             => __( 'Loading...', 'easy-theme-switcher' ),
					'selectTheme'         => __( 'Select a theme...', 'easy-theme-switcher' ),
				),
			)
		);
	}
}