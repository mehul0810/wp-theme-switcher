<?php
/**
 * Preview Banner Class
 *
 * @package SmartThemeSwitcher
 * @since 1.0.0
 */

namespace SmartThemeSwitcher\Frontend;

use SmartThemeSwitcher\ThemeSwitcher;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PreviewBanner Class.
 *
 * Handles the preview banner functionality.
 *
 * @since 1.0.0
 */
class PreviewBanner {

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
		// Enqueue preview banner scripts and styles.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		
		// AJAX handler for switching themes.
		add_action( 'wp_ajax_sts_switch_theme', array( $this, 'ajax_switch_theme' ) );
	}

	/**
	 * Enqueue scripts and styles for the preview banner.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_scripts() {
		// Get theme switcher instance.
		$theme_switcher = new ThemeSwitcher();
		
		// Only enqueue for users who can preview and are in preview mode.
		if ( ! $theme_switcher->can_user_preview() || ! $theme_switcher->get_preview_theme() ) {
			return;
		}

		// Get settings.
		$settings = get_option( 'smart_theme_switcher_settings', array() );
		$enable_banner = isset( $settings['enable_preview_banner'] ) ? 'yes' === $settings['enable_preview_banner'] : true;
		
		// Only enqueue if banner is enabled.
		if ( ! $enable_banner ) {
			return;
		}

		// Enqueue banner CSS.
		wp_enqueue_style(
			'sts-preview-banner',
			STS_PLUGIN_URL . 'assets/dist/preview-banner.css',
			array(),
			STS_PLUGIN_VERSION
		);

		// Enqueue banner JS.
		wp_enqueue_script(
			'sts-preview-banner',
			STS_PLUGIN_URL . 'assets/dist/preview-banner.js',
			array( 'jquery' ),
			STS_PLUGIN_VERSION,
			true
		);

		// Localize script.
		wp_localize_script(
			'sts-preview-banner',
			'PreviewBanner',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'sts-preview-banner-nonce' ),
				'currentUrl'    => esc_url( remove_query_arg( $theme_switcher->get_query_param_name() ) ),
				'queryParam'    => $theme_switcher->get_query_param_name(),
				'currentTheme'  => $theme_switcher->get_preview_theme(),
			)
		);
	}

	/**
	 * AJAX handler for switching themes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_switch_theme() {
		// Check nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'sts-preview-banner-nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid nonce. Please refresh the page and try again.', 'smart-theme-switcher' ) ) );
		}

		// Check permissions.
		$theme_switcher = new ThemeSwitcher();
		if ( ! $theme_switcher->can_user_preview() ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to preview themes.', 'smart-theme-switcher' ) ) );
		}

		// Get theme from request.
		$theme = isset( $_POST['theme'] ) ? sanitize_text_field( wp_unslash( $_POST['theme'] ) ) : '';
		
		// Check if theme exists.
		if ( empty( $theme ) || ! wp_get_theme( $theme )->exists() ) {
			wp_send_json_error( array( 'message' => __( 'Invalid theme selection.', 'smart-theme-switcher' ) ) );
		}

		// Get current URL.
		$current_url = isset( $_POST['currentUrl'] ) ? esc_url_raw( wp_unslash( $_POST['currentUrl'] ) ) : '';
		
		// Build new URL with theme parameter.
		$new_url = add_query_arg( $theme_switcher->get_query_param_name(), $theme, $current_url );
		
		// Send success response.
		wp_send_json_success( array(
			'message' => __( 'Theme switched successfully.', 'smart-theme-switcher' ),
			'url'     => $new_url,
		) );
	}
}