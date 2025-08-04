<?php
/**
 * Endpoints
 * 
 * @since 1.0.0
 * @package WPThemeSwitcher
 */

namespace WPThemeSwitcher\Includes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Endpoints
 *
 * @since 1.0.0
 * @package WPThemeSwitcher
 */
class Endpoints {
	
	/**
	 * Register REST API routes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_rest_routes() {
		register_rest_route(
			'wpts-theme-switcher/v1',
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
			'wpts-theme-switcher/v1',
			'/post-types',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_get_post_types' ),
				'permission_callback' => array( $this, 'rest_permission_check_read' ),
			)
		);

		register_rest_route(
			'wpts-theme-switcher/v1',
			'/taxonomies',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_get_taxonomies' ),
				'permission_callback' => array( $this, 'rest_permission_check_read' ),
			)
		);

		register_rest_route(
			'wpts-theme-switcher/v1',
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
				__( 'Settings could not be retrieved.', 'wpts-theme-switcher' ),
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
		$themes_instance = new \WPThemeSwitcher\ThemeSwitcher();
		$themes = $themes_instance->get_available_themes();

		// Get the active theme slug (stylesheet).
		$active_theme_slug = get_stylesheet();

		// Format themes for dropdown, excluding the active theme.
		$formatted_themes = array(
			'use_active' => __( 'Use Active Theme', 'wpts-theme-switcher' ),
		);

		foreach ( $themes as $slug => $name ) {
			if ( $slug === $active_theme_slug ) {
				continue;
			}
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
		$sanitized_settings = Helpers::sanitize_settings( $settings );

		// Update the settings
		update_option( 'wpts_theme_switcher_settings', $sanitized_settings );
		
		// Clear theme caches when settings are updated
		$theme_switcher = new ThemeSwitcher();
		$theme_switcher->clear_theme_caches();
		
		return rest_ensure_response( array( 'success' => true, 'settings' => $sanitized_settings ) );
	}

	/**
	 * Get settings from the database.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_settings() {
		// Always use the unified option key
		$settings = get_option( 'wpts_theme_switcher_settings', array() );
		return is_array( $settings ) ? $settings : array();
	}
}