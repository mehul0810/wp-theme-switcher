<?php
/**
 * Admin Filters
 * 
 * @since 1.0.0
 * @package WPThemeSwitcher
 * 
 */

namespace WPThemeSwitcher\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Filters {
	public function __construct() {
		// Add settings link to plugins page.
		add_filter( 'plugin_action_links_' . plugin_basename( WPTS_PLUGIN_FILE ), array( $this, 'add_settings_link' ) );
	}

	/**
	 * Add settings link to plugins page.
	 * 
	 * @param array $links Array of plugin action links.
	 * 
	 * @since 1.0.0
	 * 
	 * @return array Modified array of plugin action links.
	 */
	public function add_settings_link( $links ) {
		// Add settings link.
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'options-general.php?page=wpts-theme-switcher' ),
			esc_html__( 'Settings', 'wpts-theme-switcher' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}
}