<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://www.github.com/alexandrosraikos/wordeley
 * @since      1.0.0
 *
 * @package    Wordeley
 * @subpackage Wordeley/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Wordeley
 * @subpackage Wordeley/public
 * @author     Alexandros Raikos <alexandros@araikos.gr>
 */
class Wordeley_Public
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wordeley_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wordeley_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/wordeley-public.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wordeley_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wordeley_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/wordeley-public.js', array('jquery'), $this->version, false);
	}

	/**
	 * Register the publicly visibly shortcodes.
	 * 
	 * @since	1.0.0
	 * @author	Alexandros Raikos <alexandros@araikos.gr>
	 */
	public function register_shortcodes()
	{
		add_shortcode('wordeley', 'Wordeley_Public::catalogue_shortcode');
	}

	public static function catalogue_shortcode()
	{
		require_once plugin_dir_path(dirname(__FILE__)) . 'public/partials/wordeley-public-display.php';

		$options = get_option('wordeley_plugin_settings');
		$authors = Wordeley::parse_authors($options['article_authors']);


		$articles = Wordeley::get_articles(
			$_GET['authors'] ?? null,
			empty($_GET['article-page']) ? null : $_GET['article-page'],
			empty($_GET['articles-per-page']) ? null : $_GET['articles-per-page']
		);

		// Print HTML.
		return catalogue_shortcode_html(
			$authors ?? null,
			$articles
		);
	}
}
