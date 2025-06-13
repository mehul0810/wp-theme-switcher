<?php
/**
 * Plugin Name: Smart Theme Switcher
 * Plugin URI: https://github.com/mehul0810/smart-theme-switcher
 * Description: Preview, test, and assign any installed WordPress theme to individual pages, posts, or taxonomy archives—privately and instantly.
 * Version: 1.0.0
 * Author: Mehul Gohil
 * Author URI: https://mehulgohil.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: smart-theme-switcher
 * Domain Path: /languages
 *
 * Smart Theme Switcher is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Smart Theme Switcher is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Smart Theme Switcher. If not, see <https://www.gnu.org/licenses/>.
 */

namespace SmartThemeSwitcher;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define plugin constants.
 */
if ( ! defined( 'STS_PLUGIN_FILE' ) ) {
	define( 'STS_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'STS_PLUGIN_DIR' ) ) {
	define( 'STS_PLUGIN_DIR', plugin_dir_path( STS_PLUGIN_FILE ) );
}

if ( ! defined( 'STS_PLUGIN_URL' ) ) {
	define( 'STS_PLUGIN_URL', plugin_dir_url( STS_PLUGIN_FILE ) );
}

if ( ! defined( 'STS_PLUGIN_VERSION' ) ) {
	define( 'STS_PLUGIN_VERSION', '1.0.0' );
}

// Default query parameter name for theme previews.
if ( ! defined( 'STS_DEFAULT_QUERY_PARAM' ) ) {
	define( 'STS_DEFAULT_QUERY_PARAM', 'sts_theme' );
}

/**
 * Load Composer autoloader if available.
 */
if ( file_exists( STS_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once STS_PLUGIN_DIR . 'vendor/autoload.php';
} else {
	/**
	 * Manual autoloader as fallback.
	 */
	spl_autoload_register( function( $class ) {
		// If the class does not start with our prefix, bail out.
		if ( strpos( $class, 'SmartThemeSwitcher\\' ) !== 0 ) {
			return;
		}

		// Remove prefix from the class name.
		$relative_class = substr( $class, strlen( 'SmartThemeSwitcher\\' ) );

		// Convert namespace to file path.
		$file = STS_PLUGIN_DIR . 'src/' . str_replace( '\\', '/', $relative_class ) . '.php';

		// If the file exists, require it.
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	} );
}

// Initialize the plugin.
$init_plugin = new Plugin();
$init_plugin->register();