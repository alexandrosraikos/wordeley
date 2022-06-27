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

		wp_localize_script($this->plugin_name, 'PublicProperties', [
			'GetArticlesNonce' => wp_create_nonce('wordeley_get_articles'),
			'AJAXEndpoint' => admin_url('admin-ajax.php')
		]);
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

		function print_shortcode(
			array $authors = null,
			string $query = null,
			int $start_year = null,
			int $end_year = null,
			int $page_size = null,
			int $page = null
		) {
			$authors ??= Wordeley_Author_Controller::get_authors();
			$query ??= '';
			$page_size ??= Wordeley_Article_Controller::$default_page_size;
			$page ??= Wordeley_Article_Controller::$default_page;

			// Get relevant articles.
			$article_controller = new Wordeley_Article_Controller;
			$oldest_total_year = Wordeley_Article_Controller::get_year($article_controller->articles, false);
			$recent_total_year = Wordeley_Article_Controller::get_year($article_controller->articles, true);
			$article_controller->filter_articles(
				$authors,
				$query,
				$start_year,
				$end_year
			);
			$articles = $article_controller->get_page($page, $page_size);
			$total_article_pages = $article_controller->total_pages($page_size);

			// Consolidate information for all authors.
			$author_controller = new Wordeley_Author_Controller;
			$author_information = $author_controller->get_information_table($authors, $article_controller->articles);

			return catalogue_shortcode_html(
				$articles,
				$author_information,
				$query,
				$oldest_total_year,
				$recent_total_year,
				$start_year,
				$end_year,
				$page_size,
				$page,
				count($article_controller->articles),
				$total_article_pages
			);
		}

		if (wp_doing_ajax()) {
			Wordeley::ajax_handler(function ($filters) {
				return print_shortcode(
					$filters['authors'],
					$filters['article-search'],
					intval($filters['starting-year']),
					intval($filters['ending-year']),
					intval($filters['articles-per-page']),
					intval($filters['article-page'])
				);
			});
		} else {
			return print_shortcode(
				$_GET['authors'] ?? null,
				empty($_GET['article-search']) ? null : $_GET['article-search'],
				empty($_GET['starting-year']) ? null : $_GET['starting-year'],
				empty($_GET['ending-year']) ? null : $_GET['ending-year'],
				empty($_GET['articles-per-page']) ? null : $_GET['articles-per-page'],
				empty($_GET['article-page']) ? null : $_GET['article-page']
			);
		}
	}
}
