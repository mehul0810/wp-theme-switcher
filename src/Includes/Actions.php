<?php
/**
 * Frontend Actions.
 * 
 * @since 1.0.0
 * @package WPThemeSwitcher
 */

namespace WPThemeSwitcher\Includes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Actions {
	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Add admin bar menu for preview banner (frontend only)
		add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_menu' ), 999 );
	}

	/**
	 * Add admin bar menu.
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar Admin bar object.
	 * 
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_admin_bar_menu( $wp_admin_bar ) {
		// Only show for users who can preview.
		$theme_switcher = new \WPThemeSwitcher\ThemeSwitcher();
		if ( ! $theme_switcher->can_user_preview() ) {
			return;
		}

		$preview_theme = $theme_switcher->get_preview_theme();
		$query_param = $theme_switcher->get_query_param_name();
		$themes = $theme_switcher->get_available_themes();

		if ( $preview_theme ) {
			$theme = wp_get_theme( $preview_theme );
			// Show previewing theme and exit link
			$wp_admin_bar->add_node( array(
				'id'    => 'wpts-preview',
				'title' => sprintf(
					/* translators: %s: Theme name */
					__( 'Previewing: %s', 'wpts-theme-switcher' ),
					$theme->get( 'Name' )
				),
				'href'  => '#',
				'meta'  => array(
					'class' => 'wpts-preview-node',
				),
			) );
			$current_url = remove_query_arg( $query_param, esc_url( $_SERVER['REQUEST_URI'] ) );
			$wp_admin_bar->add_node( array(
				'id'     => 'wpts-exit-preview',
				'parent' => 'wpts-preview',
				'title'  => __( 'Exit Preview', 'wpts-theme-switcher' ),
				'href'   => $current_url,
				'meta'   => array(
					'class' => 'wpts-exit-preview-link',
				),
			) );
		} else {
			// Show Preview Theme menu by default
			$wp_admin_bar->add_node( array(
				'id'    => 'wpts-preview',
				'title' => __( 'Preview Theme', 'wpts-theme-switcher' ),
				'href'  => '#',
			) );
		}

		$active_theme_slug = get_stylesheet();
		foreach ( $themes as $theme_slug => $theme_name ) {
			$default_active_theme = get_option( 'current_theme' );
			$is_active = ( $theme_name === $default_active_theme );
			$display_name = $theme_name . ( $is_active ? ' ' . __( '[Active Theme]', 'wpts-theme-switcher' ) : '' );
			$wp_admin_bar->add_node( array(
				'id'     => 'wpts-theme-' . sanitize_html_class( $theme_slug ),
				'parent' => 'wpts-preview',
				'title'  => $display_name,
				'href'   => add_query_arg( $query_param, $theme_slug ),
				'meta'   => array(
					'class' => ( $preview_theme && $theme_slug === $preview_theme ) ? 'wpts-current-theme' : '',
				),
			) );
		}
	}
}