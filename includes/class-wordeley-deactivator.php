<?php
/**
 * Fired during plugin deactivation
 *
 * @link       https://www.github.com/alexandrosraikos/wordeley
 * @since      1.0.0
 *
 * @package    Wordeley
 * @subpackage Wordeley/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Wordeley
 * @subpackage Wordeley/includes
 * @author     Alexandros Raikos <alexandros@araikos.gr>
 */
class Wordeley_Deactivator {


	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		Wordeley_Article_Controller::delete_article_cache();
		delete_option( 'wordeley_plugin_settings' );
	}
}
