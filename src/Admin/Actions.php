<?php
/**
 * Admin Actions
 * 
 * @since 1.0.0
 * @package WPThemeSwitcher
 */

namespace WPThemeSwitcher\Admin;

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
		add_action('admin_notices', array( $this, 'remove_admin_notices' ));
	}

	function remove_admin_notices() {
		$current_screen = get_current_screen();

		// Only remove notices on the settings page.
		if (
			! empty( $current_screen->id ) &&
			'settings_page_wpts-theme-switcher' !== $current_screen->id
		) {
			return;
		}
		
		// Remove all admin notices.
		remove_all_actions('admin_notices');
	}
}