<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://araikos.gr
 * @since             1.0.0
 * @package           Wordeley
 *
 * @wordpress-plugin
 * Plugin Name:       Wordeley
 * Plugin URI:        https://www.araikos.gr/en/projects
 * Description:       Integrate and showcase your Mendeley catalogue.
 * Version:           1.0.0
 * Author:            Alexandros Raikos
 * Author URI:        https://www.araikos.gr/
 * License:           The Unlicense
 * License URI:       https://github.com/alexandrosraikos/wordeley/blob/master/LICENSE.md
 * Text Domain:       wordeley
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('WORDELEY_VERSION', '1.0.0');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wordeley-activator.php
 */
function activate_wordeley()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-wordeley-activator.php';
	Wordeley_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wordeley-deactivator.php
 */
function deactivate_wordeley()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-wordeley-deactivator.php';
	Wordeley_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_wordeley');
register_deactivation_hook(__FILE__, 'deactivate_wordeley');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-wordeley.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wordeley()
{

	$plugin = new Wordeley();
	$plugin->run();
}
run_wordeley();
