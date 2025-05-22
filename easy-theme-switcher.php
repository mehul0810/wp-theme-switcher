<?php
/**
 * Plugin Name: Easy Theme Switcher
 * Plugin URI: https://github.com/mehul0810/easy-theme-switcher
 * Description: Preview, test, and assign any installed WordPress theme to individual pages, posts, or taxonomy archives—privately and instantly.
 * Version: 1.0.0
 * Author: Mehul Gohil
 * Author URI: https://mehulgohil.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: easy-theme-switcher
 * Domain Path: /languages
 *
 * Easy Theme Switcher is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Easy Theme Switcher is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Easy Theme Switcher. If not, see <https://www.gnu.org/licenses/>.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define plugin constants.
 */
if ( ! defined( 'ETS_PLUGIN_FILE' ) ) {
	define( 'ETS_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'ETS_PLUGIN_DIR' ) ) {
	define( 'ETS_PLUGIN_DIR', plugin_dir_path( ETS_PLUGIN_FILE ) );
}

if ( ! defined( 'ETS_PLUGIN_URL' ) ) {
	define( 'ETS_PLUGIN_URL', plugin_dir_url( ETS_PLUGIN_FILE ) );
}

if ( ! defined( 'ETS_PLUGIN_VERSION' ) ) {
	define( 'ETS_PLUGIN_VERSION', '1.0.0' );
}

// Default query parameter name for theme previews.
if ( ! defined( 'ETS_DEFAULT_QUERY_PARAM' ) ) {
	define( 'ETS_DEFAULT_QUERY_PARAM', 'ets_theme' );
}

/**
 * Load Composer autoloader if available.
 */
if ( file_exists( ETS_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once ETS_PLUGIN_DIR . 'vendor/autoload.php';
} else {
	/**
	 * Manual autoloader as fallback.
	 */
	spl_autoload_register( function( $class ) {
		// If the class does not start with our prefix, bail out.
		if ( strpos( $class, 'EasyThemeSwitcher\\' ) !== 0 ) {
			return;
		}

		// Remove prefix from the class name.
		$relative_class = substr( $class, strlen( 'EasyThemeSwitcher\\' ) );

		// Convert namespace to file path.
		$file = ETS_PLUGIN_DIR . 'src/' . str_replace( '\\', '/', $relative_class ) . '.php';

		// If the file exists, require it.
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	} );
}

// Backward compatibility for non-namespaced code.
class_alias( 'EasyThemeSwitcher\\Plugin', 'Easy_Theme_Switcher' );

/**
 * Returns the main instance of Easy_Theme_Switcher.
 *
 * @since 1.0.0
 * @return \EasyThemeSwitcher\Plugin
 */
function ETS() {
	return \EasyThemeSwitcher\Plugin::instance();
}

// Initialize the plugin.
ETS();