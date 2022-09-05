<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @see     https://www.github.com/alexandrosraikos/wordeley
 * @since   1.0.0
 * @package Wordeley/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @author  Alexandros Raikos <alexandros@araikos.gr>
 */
class Wordeley_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since   1.0.0
	 *
	 * @var string the ID of this plugin
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since   1.0.0
	 *
	 * @var string the current version of this plugin
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since   1.0.0
	 *
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since   1.0.0
	 */
	public function enqueue_styles() {
		/*
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
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wordeley-public.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since   1.0.0
	 */
	public function enqueue_scripts() {
		/*
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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wordeley-public.js', array( 'jquery' ), $this->version, false );

		wp_localize_script(
			$this->plugin_name,
			'PublicProperties',
			array(
				'GetArticlesNonce' => wp_create_nonce( 'wordeley_get_articles' ),
				'AJAXEndpoint'     => admin_url( 'admin-ajax.php' ),
			)
		);
	}

	/**
	 * Register the publicly visibly shortcodes.
	 *
	 * @since   1.0.0
	 *
	 * @author  Alexandros Raikos <alexandros@araikos.gr>
	 */
	public function register_shortcodes() {
		add_shortcode( 'wordeley', 'Wordeley_Public::catalogue_shortcode' );
	}

	/**
	 * Return the catalogue shortcode HTML.
	 *
	 * Handles printing the catalogue shortcode in HTTP or AJAX contexts.
	 *
	 * @since 1.0.0
	 */
	public static function catalogue_shortcode() {
		require_once plugin_dir_path( __DIR__ ) . 'public/partials/wordeley-public-display.php';

		/**
		 * Generate the catalogue shortcode HTML.
		 *
		 * Uses filter parameters to tailor article catalogue results and pagination.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $authors    The array of filtered authors.
		 * @param string $query      A search term for article titles.
		 * @param int    $start_year The first year of the publication year range filter.
		 * @param int    $end_year   The last year of the publication year range filter.
		 * @param int    $page_size  The number of articles per page.
		 * @param int    $page       The number of the page.
		 *
		 * @return HTML
		 */
		function print_shortcode(
			array $authors = null,
			string $query = null,
			int $start_year = null,
			int $end_year = null,
			int $page_size = null,
			int $page = null
		) {
			// Assign default on null or zero values.
			if ( empty( $authors ) ) {
				$authors = Wordeley_Author_Controller::get_authors();
			}
			$query     ??= '';
			$page_size ??= Wordeley_Article_Controller::$default_page_size;

			if ( empty( $page_size ) ) {
				$page_size = Wordeley_Article_Controller::$default_page_size;
			}
			$page ??= Wordeley_Article_Controller::$default_page;

			if ( empty( $page ) ) {
				$page = Wordeley_Article_Controller::$default_page;
			}

			// Get relevant articles.
			$article_controller = new Wordeley_Article_Controller();
			$oldest_total_year   = Wordeley_Article_Controller::get_year( $article_controller->articles, false );
			$recent_total_year   = Wordeley_Article_Controller::get_year( $article_controller->articles, true );
			$article_controller->filter_articles(
				$authors,
				$query,
				$start_year,
				$end_year
			);
			$articles            = $article_controller->get_page( $page, $page_size );
			$total_article_pages = $article_controller->total_pages( $page_size );

			// Consolidate information for all authors.
			$author_controller  = new Wordeley_Author_Controller();
			$author_information = $author_controller->get_information_table( $authors, $article_controller->articles );

			// Generate HTML.
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
				count( $article_controller->articles ),
				$total_article_pages
			);
		}

		if ( wp_doing_ajax() ) {
			Wordeley_Communication_Controller::ajax_handler(
				function ( $filters ) {
					return print_shortcode(
						$filters['authors'],
						$filters['article-search'],
						intval( $filters['starting-year'] ),
						intval( $filters['ending-year'] ),
						intval( $filters['articles-per-page'] ),
						intval( $filters['article-page'] )
					);
				}
			);
		} else {
			// Prepare form data.
			$authors    = array_map(
				function ( $author ) {
					return sanitize_text_field( $author );
				},
				wp_unslash( $_GET['authors'] ?? array() )
			);
			$query      = sanitize_text_field( wp_unslash( $_GET['article-search'] ?? '' ) );
			$start_year = intval( $_GET['starting-year'] ?? 0 );
			$end_year   = intval( $_GET['ending-year'] ?? 0 );
			$page_size  = intval( $_GET['articles-per-page'] ?? 0 );
			$page       = intval( $_GET['page'] ?? '0' );

			return print_shortcode(
				$authors,
				$query,
				$start_year,
				$end_year,
				$page_size,
				$page
			);
		}
	}
}
