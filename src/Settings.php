<?php
/**
 * Settings Class
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
 * Settings Class.
 *
 * Handles the plugin settings.
 *
 * @since 1.0.0
 */
class Settings {

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
		// Register settings.
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		
		// AJAX handlers.
		add_action( 'wp_ajax_ets_save_settings', array( $this, 'ajax_save_settings' ) );
		add_action( 'wp_ajax_ets_get_settings', array( $this, 'ajax_get_settings' ) );
	}

	/**
	 * Register settings.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'sts_settings',
			'sts_settings',
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @since 1.0.0
	 * @param array $input Settings input.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( $input ) {
		$sanitized_input = array();

		// Enable preview banner.
		$sanitized_input['enable_preview_banner'] = isset( $input['enable_preview_banner'] )
			? sanitize_text_field( $input['enable_preview_banner'] )
			: 'yes';

		// Default preview theme.
		$sanitized_input['default_preview_theme'] = isset( $input['default_preview_theme'] )
			? sanitize_text_field( $input['default_preview_theme'] )
			: '';

		// Preview query parameter.
		$sanitized_input['preview_query_param'] = isset( $input['preview_query_param'] )
			? sanitize_key( $input['preview_query_param'] )
			: STS_DEFAULT_QUERY_PARAM;

		return $sanitized_input;
	}

	/**
	 * Get settings.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_settings() {
		$default_settings = array(
			'enable_preview_banner' => 'yes',
			'default_preview_theme' => '',
			'preview_query_param'   => STS_DEFAULT_QUERY_PARAM,
		);

		$settings = get_option( 'sts_settings', array() );
		return wp_parse_args( $settings, $default_settings );
	}

	/**
	 * Save settings via AJAX.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_save_settings() {
		// Check nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ets-settings-nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid nonce. Please refresh the page and try again.', 'easy-theme-switcher' ) ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to update settings.', 'easy-theme-switcher' ) ) );
		}

		// Get settings from request.
		$settings = isset( $_POST['settings'] ) ? json_decode( wp_unslash( $_POST['settings'] ), true ) : array();

		// Sanitize settings.
		$sanitized_settings = $this->sanitize_settings( $settings );

		// Update settings.
		update_option( 'sts_settings', $sanitized_settings );

		// Send success response.
		wp_send_json_success( array(
			'message'  => __( 'Settings saved successfully.', 'smart-theme-switcher' ),
			'settings' => $sanitized_settings,
		) );
	}

	/**
	 * Get settings via AJAX.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_get_settings() {
		// Check nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ets-settings-nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid nonce. Please refresh the page and try again.', 'easy-theme-switcher' ) ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to view settings.', 'easy-theme-switcher' ) ) );
		}

		// Get settings.
		$settings = $this->get_settings();

		// Get themes.
		$themes_instance = new ThemeSwitcher();
		$themes = $themes_instance->get_available_themes();

		// Send response.
		wp_send_json_success( array(
			'settings' => $settings,
			'themes'   => $themes,
		) );
	}
}