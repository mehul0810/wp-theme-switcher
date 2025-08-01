<?php
/**
 * Settings Class
 *
 * @package WpThemeSwitcher
 * @since 1.0.0
 */

namespace WpThemeSwitcher;

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
			'wts_theme_switcher_settings',
			'wts_theme_switcher_settings',
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'show_in_rest'      => true,
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
			'wts-theme-switcher/v1',
			'/settings',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'rest_get_settings' ),
					'permission_callback' => array( $this, 'rest_permission_check_read' ),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'rest_update_settings' ),
					'permission_callback' => array( $this, 'rest_permission_check_manage' ),
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
			'wts-theme-switcher/v1',
			'/post-types',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_get_post_types' ),
				'permission_callback' => array( $this, 'rest_permission_check_read' ),
			)
		);

		register_rest_route(
			'wts-theme-switcher/v1',
			'/taxonomies',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_get_taxonomies' ),
				'permission_callback' => array( $this, 'rest_permission_check_read' ),
			)
		);

		register_rest_route(
			'wts-theme-switcher/v1',
			'/themes',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_get_themes' ),
				'permission_callback' => array( $this, 'rest_permission_check_read' ),
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
	 * Permission callback for REST API endpoints that require read capabilities.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function rest_permission_check_read() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Permission callback for REST API endpoints that require management capabilities.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function rest_permission_check_manage() {
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
				__( 'Settings could not be retrieved.', 'wts-theme-switcher' ),
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
			'use_active' => __( 'Use Active Theme', 'wts-theme-switcher' ),
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
		$allowed = array('post_types', 'taxonomies', 'advanced', 'preview_query_param');
		$settings = array();
		foreach ( $allowed as $key ) {
			if ( isset( $params[ $key ] ) ) {
				$settings[ $key ] = $params[ $key ];
			}
		}
		
		// Sanitize the settings
		$sanitized_settings = $this->sanitize_settings( $settings );
		
		// Update the settings
		update_option( 'wts_theme_switcher_settings', $sanitized_settings );
		
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
		$settings = get_option( 'wts_theme_switcher_settings', array() );
		return is_array( $settings ) ? $settings : array();
	}


}