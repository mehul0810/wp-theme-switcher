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
		// Register the new option
		register_setting(
			'smart_theme_switcher_settings',
			'smart_theme_switcher_settings',
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'show_in_rest'      => false,
			)
		);
		
		// Register the legacy option for backward compatibility
		register_setting(
			'sts_settings',
			'sts_settings',
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
		// Verify the nonce in request headers
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new \WP_Error(
				'invalid_nonce',
				__( 'Invalid security token. Please refresh the page and try again.', 'smart-theme-switcher' ),
				array( 'status' => 403 )
			);
		}
		
		// Get JSON parameters from request
		$settings = $request->get_json_params();
		
		if ( empty( $settings ) ) {
			return new \WP_Error(
				'invalid_settings',
				__( 'Invalid settings data.', 'smart-theme-switcher' ),
				array( 'status' => 400 )
			);
		}

		// Sanitize and save settings
		$sanitized_settings = $this->sanitize_settings( $settings );
		
		// Save settings to database
		$updated = update_option( 'smart_theme_switcher_settings', $sanitized_settings );
		
		// Also update legacy option for backward compatibility
		update_option( 'sts_settings', $sanitized_settings );
		
		if ( ! $updated && $sanitized_settings === get_option( 'smart_theme_switcher_settings' ) ) {
			// No update needed because settings unchanged
			return rest_ensure_response(
				array(
					'success'  => true,
					'message'  => __( 'No changes detected in settings.', 'smart-theme-switcher' ),
					'settings' => $sanitized_settings,
				)
			);
		} elseif ( ! $updated ) {
			return new \WP_Error(
				'settings_not_saved',
				__( 'Failed to save settings. Please try again.', 'smart-theme-switcher' ),
				array( 'status' => 500 )
			);
		}

		return rest_ensure_response(
			array(
				'success'  => true,
				'message'  => __( 'Settings saved successfully.', 'smart-theme-switcher' ),
				'settings' => $sanitized_settings,
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
			$sanitized_input['advanced'] = array(
				'preview_enabled' => isset( $input['advanced']['preview_enabled'] ) 
					? (bool) $input['advanced']['preview_enabled'] 
					: true,
				'debug_enabled'   => isset( $input['advanced']['debug_enabled'] ) 
					? (bool) $input['advanced']['debug_enabled'] 
					: false,
			);
		} else {
			$sanitized_input['advanced'] = array(
				'preview_enabled' => true,
				'debug_enabled'   => false,
			);
		}

		// For backward compatibility, maintain the old settings structure as well.
		$sanitized_input['enable_preview_banner'] = isset( $sanitized_input['advanced']['preview_enabled'] ) && $sanitized_input['advanced']['preview_enabled'] ? 'yes' : 'no';
		$sanitized_input['preview_query_param'] = isset( $input['preview_query_param'] ) ? sanitize_key( $input['preview_query_param'] ) : STS_DEFAULT_QUERY_PARAM;
		$sanitized_input['default_preview_theme'] = isset( $input['default_preview_theme'] ) ? sanitize_text_field( $input['default_preview_theme'] ) : '';

		return $sanitized_input;
	}

	/**
	 * Get settings.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_settings() {
		$default_legacy_settings = array(
			'enable_preview_banner' => 'yes',
			'default_preview_theme' => '',
			'preview_query_param'   => STS_DEFAULT_QUERY_PARAM,
		);

		$default_settings = array(
			'post_types' => array(),
			'taxonomies' => array(),
			'advanced'   => array(
				'preview_enabled' => true,
				'debug_enabled'   => false,
			),
		);

		// Try to get settings from new option name first
		$settings = get_option( 'smart_theme_switcher_settings', array() );
		
		// If no settings found in new location, try the legacy option
		if ( empty( $settings ) ) {
			$settings = get_option( 'sts_settings', array() );
			
			// If we found settings in the legacy location, migrate them to the new location
			if ( ! empty( $settings ) ) {
				update_option( 'smart_theme_switcher_settings', $settings );
			}
		}
		
		// Parse with default settings
		$settings = wp_parse_args( $settings, $default_legacy_settings );

		// Handle upgrade from old format to new format if needed
		if ( ! isset( $settings['post_types'] ) ) {
			// Add default post types and taxonomies settings
			$post_types = get_post_types( array( 'public' => true ), 'objects' );
			foreach ( $post_types as $post_type ) {
				if ( 'attachment' === $post_type->name ) {
					continue;
				}
				$default_settings['post_types'][ $post_type->name ] = array(
					'enabled' => false,
					'theme'   => 'use_active',
				);
			}

			$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
			foreach ( $taxonomies as $taxonomy ) {
				$default_settings['taxonomies'][ $taxonomy->name ] = array(
					'enabled' => false,
					'theme'   => 'use_active',
				);
			}

			// Convert legacy settings to new format
			$default_settings['advanced']['preview_enabled'] = 'yes' === $settings['enable_preview_banner'];
			
			// Merge old and new settings
			$settings = array_merge( $settings, $default_settings );
			
			// Save the updated settings
			update_option( 'smart_theme_switcher_settings', $settings );
			update_option( 'sts_settings', $settings ); // For backward compatibility
		}

		return $settings;
	}

	/**
	 * Save settings via AJAX (legacy method).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_save_settings() {
		// Check nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ets-settings-nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid nonce. Please refresh the page and try again.', 'smart-theme-switcher' ) ) );
		}

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
		update_option( 'sts_settings', $sanitized_settings );

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
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ets-settings-nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid nonce. Please refresh the page and try again.', 'smart-theme-switcher' ) ) );
		}

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