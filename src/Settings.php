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
		
		// Register REST API routes.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		
		// Legacy AJAX handlers (for backward compatibility).
		add_action( 'wp_ajax_sts_save_settings', array( $this, 'ajax_save_settings' ) );
		add_action( 'wp_ajax_sts_get_settings', array( $this, 'ajax_get_settings' ) );
	}

	/**
	 * Register settings.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_settings() {
		// Register the new option
		register_setting(
			'smart_theme_switcher_settings',
			'smart_theme_switcher_settings',
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_rest_routes() {
		register_rest_route(
			'smart-theme-switcher/v1',
			'/settings',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'rest_get_settings' ),
					'permission_callback' => array( $this, 'rest_permission_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'rest_update_settings' ),
					'permission_callback' => array( $this, 'rest_permission_check' ),
					'args'                => array(
						'post_types' => array(
							'type'        => 'object',
							'required'    => false,
						),
						'taxonomies' => array(
							'type'        => 'object',
							'required'    => false,
						),
						'advanced'   => array(
							'type'        => 'object',
							'required'    => false,
						),
					),
				),
			)
		);

		register_rest_route(
			'smart-theme-switcher/v1',
			'/post-types',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_get_post_types' ),
				'permission_callback' => array( $this, 'rest_permission_check' ),
			)
		);

		register_rest_route(
			'smart-theme-switcher/v1',
			'/taxonomies',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_get_taxonomies' ),
				'permission_callback' => array( $this, 'rest_permission_check' ),
			)
		);

		register_rest_route(
			'smart-theme-switcher/v1',
			'/themes',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_get_themes' ),
				'permission_callback' => array( $this, 'rest_permission_check' ),
			)
		);
	}
	
	/**
	 * Permission callback for REST API endpoints.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function rest_permission_check() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Handle REST API GET request for settings.
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST API request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function rest_get_settings( $request ) {
		// Get settings from database
		$settings = $this->get_settings();
		
		if ( empty( $settings ) ) {
			return new \WP_Error(
				'settings_not_found',
				__( 'Settings could not be retrieved.', 'smart-theme-switcher' ),
				array( 'status' => 404 )
			);
		}
		
		return rest_ensure_response( $settings );
	}

	/**
	 * Handle REST API GET request for post types.
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST API request.
	 * @return \WP_REST_Response
	 */
	public function rest_get_post_types( $request ) {
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		$result = array();

		foreach ( $post_types as $post_type ) {
			// Skip attachments and other non-content post types.
			if ( 'attachment' === $post_type->name ) {
				continue;
			}

			$result[ $post_type->name ] = array(
				'name'  => $post_type->name,
				'label' => $post_type->label,
			);
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Handle REST API GET request for taxonomies.
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST API request.
	 * @return \WP_REST_Response
	 */
	public function rest_get_taxonomies( $request ) {
		$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
		$result = array();

		foreach ( $taxonomies as $taxonomy ) {
			$result[ $taxonomy->name ] = array(
				'name'  => $taxonomy->name,
				'label' => $taxonomy->label,
			);
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Handle REST API GET request for themes.
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST API request.
	 * @return \WP_REST_Response
	 */
	public function rest_get_themes( $request ) {
		$themes_instance = new ThemeSwitcher();
		$themes = $themes_instance->get_available_themes();

		// Format themes for dropdown.
		$formatted_themes = array(
			'use_active' => __( 'Use Active Theme', 'smart-theme-switcher' ),
		);

		// Add all other themes.
		foreach ( $themes as $slug => $name ) {
			$formatted_themes[ $slug ] = $name;
		}

		return rest_ensure_response( $formatted_themes );
	}

	/**
	 * Handle REST API PUT/POST request for settings.
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST API request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function rest_update_settings( $request ) {
		$params = $request->get_json_params();
		// Only allow expected keys
		$allowed = array('post_types', 'taxonomies', 'advanced', 'enable_preview_banner', 'default_preview_theme', 'preview_query_param');
		$settings = array();
		foreach ( $allowed as $key ) {
			if ( isset( $params[ $key ] ) ) {
				$settings[ $key ] = $params[ $key ];
			}
		}
		
		// Sanitize the settings
		$sanitized_settings = $this->sanitize_settings( $settings );
		
		// Update the settings
		update_option( 'smart_theme_switcher_settings', $sanitized_settings );
		
		// Clear theme caches when settings are updated
		$theme_switcher = new ThemeSwitcher();
		$theme_switcher->clear_theme_caches();
		
		return rest_ensure_response( array( 'success' => true, 'settings' => $sanitized_settings ) );
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

		// Handle legacy settings format.
		if ( isset( $input['enable_preview_banner'] ) || isset( $input['default_preview_theme'] ) || isset( $input['preview_query_param'] ) ) {
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

		// New settings format.
		
		// Post types.
		if ( isset( $input['post_types'] ) && is_array( $input['post_types'] ) ) {
			$sanitized_input['post_types'] = array();
			
			foreach ( $input['post_types'] as $post_type => $settings ) {
				// Skip if post type is not valid
				if ( ! post_type_exists( sanitize_key( $post_type ) ) && 'post' !== $post_type && 'page' !== $post_type ) {
					continue;
				}
				
				$sanitized_input['post_types'][ sanitize_key( $post_type ) ] = array(
					'enabled' => isset( $settings['enabled'] ) ? (bool) $settings['enabled'] : false,
					'theme'   => isset( $settings['theme'] ) ? sanitize_text_field( $settings['theme'] ) : 'use_active',
				);
			}
		}

		// Taxonomies.
		if ( isset( $input['taxonomies'] ) && is_array( $input['taxonomies'] ) ) {
			$sanitized_input['taxonomies'] = array();
			
			foreach ( $input['taxonomies'] as $taxonomy => $settings ) {
				// Skip if taxonomy is not valid
				if ( ! taxonomy_exists( sanitize_key( $taxonomy ) ) && 'category' !== $taxonomy && 'post_tag' !== $taxonomy ) {
					continue;
				}
				
				$sanitized_input['taxonomies'][ sanitize_key( $taxonomy ) ] = array(
					'enabled' => isset( $settings['enabled'] ) ? (bool) $settings['enabled'] : false,
					'theme'   => isset( $settings['theme'] ) ? sanitize_text_field( $settings['theme'] ) : 'use_active',
				);
			}
		}

		// Advanced settings.
		if ( isset( $input['advanced'] ) && is_array( $input['advanced'] ) ) {
			$sanitized_input['advanced'] = array();
			foreach ( $input['advanced'] as $key => $value ) {
				$sanitized_input['advanced'][ sanitize_key( $key ) ] = sanitize_text_field( $value );
			}
			
			// Set enable_preview based on the advanced setting
			$sanitized_input['enable_preview'] = isset( $input['advanced']['preview_enabled'] ) && 
			                                    $input['advanced']['preview_enabled'] ? 'yes' : 'no';
		} else {
			$sanitized_input['advanced'] = array(
				'preview_enabled' => true,
				'debug_enabled'   => false,
			);
			
			// Default enable_preview to 'yes' if advanced settings are missing
			$sanitized_input['enable_preview'] = 'yes';
		}

		// For backward compatibility, maintain the old settings structure as well.
		$sanitized_input['enable_preview_banner'] = 'yes'; // Always enabled in new version
		$sanitized_input['preview_query_param'] = isset( $input['preview_query_param'] ) ? sanitize_key( $input['preview_query_param'] ) : STS_DEFAULT_QUERY_PARAM;
		$sanitized_input['default_preview_theme'] = isset( $input['default_preview_theme'] ) ? sanitize_text_field( $input['default_preview_theme'] ) : '';

		return $sanitized_input;
	}

	/**
	 * Get settings from the database.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_settings() {
		// Always use the unified option key
		$settings = get_option( 'smart_theme_switcher_settings', array() );
		return is_array( $settings ) ? $settings : array();
	}

	/**
	 * Save settings via AJAX (legacy method).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_save_settings() {
		// Check nonce.
		$this->check_nonce( 'sts-settings-nonce' );

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to update settings.', 'smart-theme-switcher' ) ) );
		}

		// Get settings from request.
		$settings = isset( $_POST['settings'] ) ? json_decode( wp_unslash( $_POST['settings'] ), true ) : array();

		// Sanitize settings.
		$sanitized_settings = $this->sanitize_settings( $settings );

		// Update settings in both locations for compatibility.
		update_option( 'smart_theme_switcher_settings', $sanitized_settings );
		
		// Clear theme caches when settings are updated
		$theme_switcher = new ThemeSwitcher();
		$theme_switcher->clear_theme_caches();

		// Send success response.
		wp_send_json_success( array(
			'message'  => __( 'Settings saved successfully.', 'smart-theme-switcher' ),
			'settings' => $sanitized_settings,
		) );
	}

	/**
	 * Get settings via AJAX (legacy method).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_get_settings() {
		// Check nonce.
		$this->check_nonce( 'sts-settings-nonce' );

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to view settings.', 'smart-theme-switcher' ) ) );
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