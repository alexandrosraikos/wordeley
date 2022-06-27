<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://www.github.com/alexandrosraikos/wordeley
 * @since      1.0.0
 *
 * @package    Wordeley
 * @subpackage Wordeley/includes
 */

require_once(ABSPATH . 'wp-admin/includes/file.php');

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Wordeley
 * @subpackage Wordeley/includes
 * @author     Alexandros Raikos <alexandros@araikos.gr>
 */
class Wordeley
{

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Wordeley_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct()
	{
		if (defined('WORDELEY_VERSION')) {
			$this->version = WORDELEY_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'wordeley';
		if (!defined('WORDLEY_FILE_STORE')) {
			define('WORDELEY_FILE_STORE', trailingslashit(wp_upload_dir()['basedir']) . 'wordeley/');
		}

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Wordeley_Loader. Orchestrates the hooks of the plugin.
	 * - Wordeley_i18n. Defines internationalization functionality.
	 * - Wordeley_Admin. Defines all hooks for the admin area.
	 * - Wordeley_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies()
	{

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wordeley-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wordeley-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wordeley-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-wordeley-public.php';

		$this->loader = new Wordeley_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Wordeley_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale()
	{

		$plugin_i18n = new Wordeley_i18n();

		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks()
	{

		$plugin_admin = new Wordeley_Admin($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('admin_menu', $plugin_admin, 'add_settings_page');
		$this->loader->add_action('admin_init', $plugin_admin, 'register_settings');

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

		$this->loader->add_action('wp_ajax_wordeley_generate_access_token', $plugin_admin, 'generate_access_token');
		$this->loader->add_action('wp_ajax_wordeley_refresh_cache', $plugin_admin, 'refresh_cache');
		$this->loader->add_action('wp_ajax_wordeley_clear_cache', $plugin_admin, 'clear_cache');

		/**
		 * Cron hooks
		 */

		// Re-generate access token.
		add_filter(
			'cron_schedules',
			function ($schedules) {
				$schedules['hourly'] = array(
					'interval' => 3600,
					'display' => 'Every 60 minutes.'
				);
				return $schedules;
			}
		);
		$this->loader->add_action('wordeley_access_token_cron_handler_hook', $plugin_admin, 'generate_access_token');
		if (!wp_next_scheduled('wordeley_access_token_cron_handler_hook')) {
			wp_schedule_event(time(), 'hourly', 'wordeley_access_token_cron_handler_hook');
		}

		// Refresh cache.
		add_filter(
			'cron_schedules',
			function ($schedules) {
				$schedules['monthly'] = array(
					'interval' => 2592000,
					'display' => 'Every month.'
				);
				return $schedules;
			}
		);
		$this->loader->add_action('wordeley_refresh_cache_cron_handler_hook', $plugin_admin, 'refresh_cache');
		if (!wp_next_scheduled('wordeley_refresh_cache_cron_handler_hook')) {
			wp_schedule_event(time(), 'monthly', 'wordeley_refresh_cache_cron_handler_hook');
		}
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks()
	{

		$plugin_public = new Wordeley_Public($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
		$this->loader->add_action('init', $plugin_public, 'register_shortcodes');
		$this->loader->add_action('wp_ajax_wordeley_get_articles', $plugin_public, 'get_articles_handler');
		$this->loader->add_action('wp_ajax_nopriv_wordeley_get_articles', $plugin_public, 'get_articles_handler');
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run()
	{
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name()
	{
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Wordeley_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader()
	{
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version()
	{
		return $this->version;
	}

	/**
	 * The generalized handler for AJAX calls.
	 *
	 * @param string $action The action slug used in WordPress.
	 * @param callable $completion The callback for completed data.
	 * @return void The function simply echoes the response to the
	 *
	 * @usedby All functions triggered by the WordPress AJAX handler.
	 *
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.0.0
	 */
	public static function ajax_handler($completion): void
	{
		$action = sanitize_key($_POST['action']);

		// Verify the action related nonce.
		if (!wp_verify_nonce($_POST['nonce'], $action)) {
			http_response_code(403);
			die("Unverified request for action: " . $action);
		}

		try {
			/** @var array $data The filtered $_POST data excluding WP specific keys. */
			$data = $completion(array_filter($_POST, function ($key) {
				return ($key != 'action' && $key != 'nonce');
			}, ARRAY_FILTER_USE_KEY));

			// Prepare the data and send.
			if (empty($data)) {
				http_response_code(200);
				die();
			} else {
				$data = json_encode($data);
				if ($data == false) {
					throw new RuntimeException("There was an error while encoding the data to JSON.");
				} else {
					http_response_code(200);
					die($data);
				}
			}
		} catch (\Exception $e) {
			http_response_code(500);
			die($e->getMessage());
		}
	}

	/**
	 * Send a request to the Mendeley API.
	 *
	 * @param string $http_method The standardized HTTP method used for the request.
	 * @param array $data The data to be sent according to the documented schema.
	 * @param string $token The encoded user access token.
	 * @param array $additional_headers Any additional HTTP headers for the request.
	 *
	 * @throws InvalidArgumentException For missing WordPress settings.
	 * @throws ErrorException For connectivity and other API issues.
	 *
	 * @since   1.0.0
	 * @author  Alexandros Raikos <alexandros@araikos.gr>
	 */
	public static function api_request($http_method, $uri, $data = [], $requesting_access = false)
	{
		// Retrieve access token.
		$options = get_option('wordeley_plugin_settings');
		$access_token = $options['api_access_token'] ?? null;
		if (empty($access_token) && !$requesting_access) {
			throw new InvalidArgumentException("You need to generate a Mendeley API access token in Wordeley settings.");
		}

		// Contact Mendeley API.
		$curl = curl_init();
		curl_setopt_array($curl, [
			CURLOPT_URL => 'https://api.mendeley.com'  . $uri,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => $http_method,
			CURLOPT_POSTFIELDS => http_build_query($data),
			CURLOPT_HTTPHEADER => $requesting_access ?
				['Content-Type: application/x-www-form-urlencoded'] :
				[
					'Authorization: Bearer ' . $options['api_access_token'],
					'Accept: application/vnd.mendeley-document.1+json'
				]
		]);

		// Get the data.
		$response = curl_exec($curl);
		$curl_http = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		// Handle errors.
		if (curl_errno($curl)) {
			throw new Exception("Unable to reach the Mendeley API. More details: " . curl_error($curl));
		}

		curl_close($curl);
		if ($curl_http != 200 && $curl_http != 201 && $curl_http != 204 && $curl_http != 404) {
			throw new ErrorException(
				"The Mendeley API returned an HTTP " . $curl_http . " status code. More information: " . $response ?? '',
				$curl_http
			);
		} else {
			if (isset($response)) {
				if (is_string($response)) {
					$decoded = json_decode($response, true);
					if (json_last_error() === JSON_ERROR_NONE) {
						return $decoded;
					} else {
						return $response;
					}
				}
			} else {
				curl_close($curl);
				throw new ErrorException("There was no response.");
			};
		}
	}

	public static function delete_article_cache()
	{
		// Prepare filesystem access.
		global $wp_filesystem;

		if (is_file(WORDELEY_FILE_STORE . "/articles.json")) {
			unlink(WORDELEY_FILE_STORE . '/articles.json');
		}
	}

	public static function update_article_cache(array $articles = null)
	{
		if (empty($articles)) {
			$articles = Wordeley::retrieve_articles();
		}

		// Prepare filesystem access.
		global $wp_filesystem;
		WP_Filesystem();

		// Clear old cache and save new content.
		Wordeley::delete_article_cache();
		if (!file_exists(WORDELEY_FILE_STORE)) {
			mkdir(WORDELEY_FILE_STORE, 0777, true);
		}
		$wp_filesystem->put_contents(WORDELEY_FILE_STORE . '/articles.json', json_encode($articles), 0644);
	}


	public static function retrieve_articles()
	{
		$articles = [];
		$authors = Wordeley::parse_authors();

		foreach ($authors as $author) {
			$olders_exist = true;
			$starting_year = intval(date('Y'));
			while ($olders_exist) {
				$response = Wordeley::api_request(
					'GET',
					'/search/catalog?author=' . urlencode($author) . '&limit=100&min_year=' . $starting_year . '&max_year=' . $starting_year
				);
				$response = Wordeley::api_request(
					'GET',
					'/search/catalog?author=' . urlencode($author) . '&limit=100&min_year=' . $starting_year . '&max_year=' . $starting_year
				);
				$olders_exist = !empty($response);
				$articles = array_merge($articles, $response);
				$starting_year -= 1;
			}
		}

		Wordeley::update_article_cache($articles);
		return $articles;
	}

	public static function filter_articles_by_author(array $articles, array $authors)
	{
		return array_filter($articles, function ($article) use ($authors) {
			return count(
				array_intersect($authors, array_map(function ($author) {
					return ((empty($author['first_name']) ? '' : $author['first_name'] . ' ')) . ($author['last_name'] ?? '');
				}, $article['authors']))
			) > 0;
		});
	}

	public static function filter_articles_by_year(array $articles, int $starting_year, int $ending_year)
	{
		return array_filter($articles, function ($article) use ($starting_year, $ending_year) {
			return $article['year'] >= $starting_year && $article['year'] <= $ending_year;
		});
	}

	public static function get_articles(array $authors = null, int|null $page = 0, int|null $articles_per_page = 10, string|null $search_term = null, int|null $starting_year = null, int|null $ending_year = null)
	{
		// Get requested authors.
		if (empty($authors)) {
			$authors = Wordeley::parse_authors();
		}

		// Get all articles.
		if (is_file(WORDELEY_FILE_STORE . "/articles.json")) {
			// Get from cache.
			$total_articles = json_decode(file_get_contents(WORDELEY_FILE_STORE . "/articles.json"), true);
		} else {
			// Retrieve from API and update cache.
			$total_articles = Wordeley::retrieve_articles();
		}

		// Filter relevant articles by author.
		$filtered_articles = Wordeley::filter_articles_by_author($total_articles, $authors);

		// Filter relevant articles by years.
		$filtered_articles = Wordeley::filter_articles_by_year($filtered_articles, $starting_year ?? 1900, $ending_year ?? intval(date('Y')));

		// Filter relevant articles by search term.
		if (!empty($search_term)) {
			$filtered_articles = array_filter(
				$filtered_articles,
				function ($article) use ($search_term) {
					$search_result = stripos($article['title'], $search_term);
					return $search_result !== false;
				}
			);
		}

		// Initialize pagination.
		if (empty($articles_per_page)) {
			$articles_per_page = 5;
		}
		if (empty($page)) {
			$page = 0;
		}

		// Create article page.
		$starting_index = $articles_per_page * $page;
		$paged_articles = array_slice($filtered_articles, $starting_index, $articles_per_page);

		// Calculate article years.
		$article_years = array_map(function ($article) {
			return $article['year'];
		}, $filtered_articles);
		sort($article_years);

		$author_article_total = [];
		foreach (Wordeley::parse_authors() as $author) {
			$author_article_total[$author] = count(Wordeley::filter_articles_by_author($filtered_articles, [$author]));
		}
		$articles = [
			'content' =>  $paged_articles,
			'total_pages' => ceil(count($filtered_articles) / $articles_per_page),
			'oldest_year' => $article_years[0] ?? 1975,
			'author_statistics' => $author_article_total,
			'total_articles' => count($filtered_articles)
		];

		return $articles;
	}

	public static function parse_authors(string $serialized = null)
	{
		if (empty($serialized)) {
			$options = get_option('wordeley_plugin_settings');
			$serialized = $options['article_authors'] ?? "";
		}

		// Parse comma separated authors string.
		return array_map(function ($author) {
			return ltrim(rtrim($author));
		}, explode(',', $serialized));
	}
}
