<?php
/**
 * Admin Class
 *
 * @package EasyThemeSwitcher
 * @since 1.0.0
 */

namespace EasyThemeSwitcher\Admin;

use EasyThemeSwitcher\ThemeSwitcher;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Class.
 *
 * Handles admin-related functionality.
 *
 * @since 1.0.0
 */
class Admin {

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
		// Add settings link to plugins page.
		add_filter( 'plugin_action_links_' . plugin_basename( ETS_PLUGIN_FILE ), array( $this, 'add_settings_link' ) );
		
		// Add admin bar menu.
		add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_menu' ), 999 );
	}

	/**
	 * Add settings link to plugins page.
	 *
	 * @since 1.0.0
	 * @param array $links Array of plugin action links.
	 * @return array Modified array of plugin action links.
	 */
	public function add_settings_link( $links ) {
		// Add settings link.
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'options-general.php?page=easy-theme-switcher' ),
			__( 'Settings', 'easy-theme-switcher' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Add admin bar menu.
	 *
	 * @since 1.0.0
	 * @param \WP_Admin_Bar $wp_admin_bar Admin bar object.
	 * @return void
	 */
	public function add_admin_bar_menu( $wp_admin_bar ) {
		// Only show for users who can preview.
		$theme_switcher = new ThemeSwitcher();
		if ( ! $theme_switcher->can_user_preview() ) {
			return;
		}

		// Get current preview theme.
		$preview_theme = $theme_switcher->get_preview_theme();

		// If in preview mode, add admin bar menu.
		if ( $preview_theme ) {
			$theme = wp_get_theme( $preview_theme );
			
			// Add main node.
			$wp_admin_bar->add_node( array(
				'id'    => 'ets-preview',
				'title' => sprintf(
					/* translators: %s: Theme name */
					__( 'Previewing: %s', 'easy-theme-switcher' ),
					$theme->get( 'Name' )
				),
				'href'  => '#',
				'meta'  => array(
					'class' => 'ets-preview-node',
				),
			) );

			// Add exit preview link.
			$current_url = remove_query_arg( $theme_switcher->get_query_param_name(), esc_url( $_SERVER['REQUEST_URI'] ) );
			$wp_admin_bar->add_node( array(
				'id'     => 'ets-exit-preview',
				'parent' => 'ets-preview',
				'title'  => __( 'Exit Preview', 'easy-theme-switcher' ),
				'href'   => $current_url,
				'meta'   => array(
					'class' => 'ets-exit-preview-link',
				),
			) );

			// Get all themes for switcher.
			$themes = $theme_switcher->get_available_themes();
			
			// Add "Switch Theme" submenu.
			$wp_admin_bar->add_node( array(
				'id'     => 'ets-switch-theme',
				'parent' => 'ets-preview',
				'title'  => __( 'Switch Theme', 'easy-theme-switcher' ),
				'href'   => '#',
			) );

			// Add theme options.
			foreach ( $themes as $theme_slug => $theme_name ) {
				$wp_admin_bar->add_node( array(
					'id'     => 'ets-theme-' . sanitize_html_class( $theme_slug ),
					'parent' => 'ets-switch-theme',
					'title'  => $theme_name,
					'href'   => add_query_arg( $theme_switcher->get_query_param_name(), $theme_slug ),
					'meta'   => array(
						'class' => $theme_slug === $preview_theme ? 'ets-current-theme' : '',
					),
				) );
			}
		}
	}
}