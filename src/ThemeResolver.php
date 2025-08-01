<?php
/**
 * Theme Resolver Class
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
 * ThemeResolver Class.
 *
 * Handles the resolution of which theme to load based on various conditions.
 * Implements a clear priority order:
 * 1. Individual post setting (highest priority)
 * 2. Post type or taxonomy setting (medium priority)
 * 3. Global/default theme (lowest priority)
 *
 * @since 1.0.0
 */
class ThemeResolver {

	/**
	 * Plugin settings.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->settings = get_option( 'wpts_theme_switcher_settings', array() );
	}

	/**
	 * Determine the theme to use for current request in 'Theme Set Mode'.
	 * This mode applies to all visitors (not just admins) and affects SEO.
	 *
	 * Implements priority logic:
	 * 1. Theme set in individual post (post meta)
	 * 2. Theme set per post type or taxonomy from settings
	 * 3. Active theme (no override)
	 *
	 * Note: This method does NOT include preview mode logic, which is handled
	 * separately and only applies to authorized users.
	 *
	 * @since 1.0.0
	 * @return string|bool Theme slug or false if no override.
	 */
	public function resolve_theme() {
		// 1. Check for per-post theme assignment (individual post setting)
		$post_theme = $this->get_post_theme();
		if ( false !== $post_theme ) {
			return $post_theme;
		}

		// 2. Check for post type or taxonomy theme assignment
		$term_theme = $this->get_term_theme();
		if ( false !== $term_theme ) {
			return $term_theme;
		}

		// 3. Fallback: use active theme (no override)
		return false;
	}

	/**
	 * Get theme set for individual post.
	 *
	 * @since 1.0.0
	 * @return string|bool Theme slug or false if no post theme is set.
	 */
	public function get_post_theme() {
		// Only applicable on singular pages
		if ( ! is_singular() ) {
			return false;
		}

		$post_id = get_queried_object_id();
		if ( ! $post_id ) {
			return false;
		}

		// Get from object cache if available
		$cache_key = 'wpts_post_' . $post_id;
		$cached_theme = wp_cache_get( $cache_key, 'wpts_theme_switcher' );
		
		if ( false !== $cached_theme ) {
			return $cached_theme ?: false; // Convert empty string to false
		}

		$meta_theme = get_post_meta( $post_id, 'wpts_theme_switcher_active_theme', true );
		if ( ! empty( $meta_theme ) ) {
			// Verify theme exists before using it
			$theme_switcher = new \WpThemeSwitcher\ThemeSwitcher();
			if ( $theme_switcher->is_valid_theme( $meta_theme ) ) {
				// Cache the result
				wp_cache_set( $cache_key, $meta_theme, 'wpts_theme_switcher' );
				return $meta_theme;
			}
		}

		// Cache the negative result (no theme set)
		wp_cache_set( $cache_key, '', 'wpts_theme_switcher' );
		return false;
	}

	/**
	 * Get theme set for post type or taxonomy.
	 *
	 * @since 1.0.0
	 * @return string|bool Theme slug or false if no post type/taxonomy theme is set.
	 */
	public function get_term_theme() {
		// Check for post type theme (when on singular pages)
		if ( is_singular() ) {
			return $this->get_post_type_theme();
		}

		// Check for taxonomy theme (when on taxonomy archives)
		if ( is_tax() || is_category() || is_tag() ) {
			return $this->get_taxonomy_theme();
		}

		return false;
	}

	/**
	 * Get theme set for current post type.
	 *
	 * @since 1.0.0
	 * @return string|bool Theme slug or false if no post type theme is set.
	 */
	private function get_post_type_theme() {
		global $post;
		
		if ( ! $post ) {
			return false;
		}
		
		$post_type = get_post_type( $post );
		if ( ! $post_type ) {
			return false;
		}
		
		// Get from object cache if available
		$cache_key = 'wpts_post_type_' . $post_type;
		$cached_theme = wp_cache_get( $cache_key, 'wpts_theme_switcher' );
		
		if ( false !== $cached_theme ) {
			return $cached_theme ?: false; // Convert empty string to false
		}
		
		// Check settings for this post type
		if ( ! isset( $this->settings['post_types'] ) || ! is_array( $this->settings['post_types'] ) ) {
			wp_cache_set( $cache_key, '', 'wpts_theme_switcher' );
			return false;
		}
		
		if (
			isset( $this->settings['post_types'][ $post_type ] ) &&
			! empty( $this->settings['post_types'][ $post_type ]['enabled'] ) &&
			isset( $this->settings['post_types'][ $post_type ]['theme'] ) &&
			$this->settings['post_types'][ $post_type ]['theme'] !== 'use_active' &&
			! empty( $this->settings['post_types'][ $post_type ]['theme'] )
		) {
			$theme_slug = sanitize_text_field( $this->settings['post_types'][ $post_type ]['theme'] );
			
			// Verify theme exists before using it
			$theme_switcher = new \WpThemeSwitcher\ThemeSwitcher();
			if ( $theme_switcher->is_valid_theme( $theme_slug ) ) {
				wp_cache_set( $cache_key, $theme_slug, 'wpts_theme_switcher' );
				return $theme_slug;
			}
		}
		
		// Cache the negative result
		wp_cache_set( $cache_key, '', 'wpts_theme_switcher' );
		return false;
	}

	/**
	 * Get theme set for current taxonomy.
	 *
	 * @since 1.0.0
	 * @return string|bool Theme slug or false if no taxonomy theme is set.
	 */
	private function get_taxonomy_theme() {
		$queried_object = get_queried_object();
		
		if ( ! $queried_object || ! isset( $queried_object->taxonomy ) ) {
			return false;
		}
		
		// 1. First check for individual term meta
		if ( isset( $queried_object->term_id ) ) {
			// Get from object cache if available
			$cache_key = 'wpts_term_' . $queried_object->term_id;
			$cached_theme = wp_cache_get( $cache_key, 'wpts_theme_switcher' );
			
			if ( false !== $cached_theme ) {
				return $cached_theme ?: false; // Convert empty string to false
			}
			
			$meta_theme = get_term_meta( $queried_object->term_id, 'wpts_theme_switcher_active_theme', true );
			if ( ! empty( $meta_theme ) ) {
				// Verify theme exists before using it
				$theme_switcher = new \WpThemeSwitcher\ThemeSwitcher();
				if ( $theme_switcher->is_valid_theme( $meta_theme ) ) {
					// Cache the result
					wp_cache_set( $cache_key, $meta_theme, 'wpts_theme_switcher' );
					return $meta_theme;
				}
			}
			
			// Cache the negative result (no theme set)
			wp_cache_set( $cache_key, '', 'wpts_theme_switcher' );
		}
		
		// 2. Then check for taxonomy-level setting
		if ( ! isset( $this->settings['taxonomies'] ) || ! is_array( $this->settings['taxonomies'] ) ) {
			return false;
		}
		
		$taxonomy = $queried_object->taxonomy;
		if (
			isset( $this->settings['taxonomies'][ $taxonomy ] ) &&
			! empty( $this->settings['taxonomies'][ $taxonomy ]['enabled'] ) &&
			isset( $this->settings['taxonomies'][ $taxonomy ]['theme'] ) &&
			$this->settings['taxonomies'][ $taxonomy ]['theme'] !== 'use_active' &&
			! empty( $this->settings['taxonomies'][ $taxonomy ]['theme'] )
		) {
			// Verify theme exists before using it
			$theme_slug = sanitize_text_field( $this->settings['taxonomies'][ $taxonomy ]['theme'] );
			$theme_switcher = new \WpThemeSwitcher\ThemeSwitcher();
			if ( $theme_switcher->is_valid_theme( $theme_slug ) ) {
				return $theme_slug;
			}
		}

		return false;
	}

	/**
	 * Get query parameter name from settings.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_query_param_name() {
		return isset( $this->settings['preview_query_param'] ) ? $this->settings['preview_query_param'] : WPTS_DEFAULT_QUERY_PARAM;
	}

	/**
	 * Get theme slug from query parameter.
	 *
	 * Used for Preview Mode only - determines if a theme preview is requested
	 * via query parameter.
	 *
	 * @since 1.0.0
	 * @return string|bool Theme slug or false if no query parameter.
	 */
	public function get_preview_theme_from_query() {
		$query_param = $this->get_query_param_name();
		
		if ( isset( $_GET[ $query_param ] ) && ! empty( $_GET[ $query_param ] ) ) {
			return sanitize_text_field( $_GET[ $query_param ] );
		}

		return false;
	}
	
	/**
	 * Determine if preview mode is enabled in settings.
	 *
	 * @since 1.0.0
	 * @return bool True if preview mode is enabled, false otherwise.
	 */
	public function is_preview_mode_enabled() {
		return isset( $this->settings['enable_preview'] ) && $this->settings['enable_preview'] === 'yes';
	}
}