<?php
/**
 * Helpers
 * 
 * @since 1.0.0
 * @package WPThemeSwitcher
 */

namespace WPThemeSwitcher;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helpers {
	/**
	 * Sanitize settings input.
	 * 
	 * @param array $input The input settings array.
	 * 
	 * @since 1.0.0
	 * 
	 * @return array Sanitized settings array.
	 */
	public static function sanitize_settings( $input ) {
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
					//'enabled' => isset( $settings['enabled'] ) ? (bool) $settings['enabled'] : false,
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
					//'enabled' => isset( $settings['enabled'] ) ? (bool) $settings['enabled'] : false,
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
}