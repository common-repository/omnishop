<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://omnishopapp.com
 * @since             1.0.0
 * @package           Omnishop
 *
 * @wordpress-plugin
 * Plugin Name:       Omnishop
 * Plugin URI:        https://omnishopapp.com/wp-plugin
 * Description:       Plugin required for your Omnishop mobile applications
 * Version:           1.0.9
 * Author:            Omnishop Dev Team
 * Author URI:        https://omnishopapp.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       omnishop
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


define( 'OMNISHOP_VERSION', '1.0.9' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-omnishop-activator.php
 */
function activate_omnishop() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-omnishop-activator.php';
	Omnishop_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-omnishop-deactivator.php
 */
function deactivate_omnishop() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-omnishop-deactivator.php';
	Omnishop_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_omnishop' );
register_deactivation_hook( __FILE__, 'deactivate_omnishop' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-omnishop.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_omnishop() {

	$plugin = new Omnishop();
	$plugin->run();

}
run_omnishop();
