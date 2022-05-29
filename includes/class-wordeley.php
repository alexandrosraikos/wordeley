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

		/**
		 * Cron hooks
		 */

		// add_filter(
		// 	'cron_schedules',
		// 	function ($schedules) {
		// 		$schedules['almost_hourly'] = array(
		// 			'interval' => 3300,
		// 			'display' => 'Every 55 minutes (slightly under an hour).'
		// 		);
		// 		return $schedules;
		// 	}
		// );
		// $this->loader->add_action('wordeley_access_token_cron_handler_hook', $plugin_admin, 'generate_access_token');
		// if (!wp_next_scheduled('wordeley_access_token_cron_handler_hook')) {
		// 	wp_schedule_event(time(), 'almost_hourly', 'wordeley_access_token_cron_handler_hook');
		// }
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

		if (!is_file(WORDELEY_FILE_STORE . "/articles.json")) {
			unlink(WORDELEY_FILE_STORE . '/articles.json');
		}
	}

	public static function update_article_cache(array $articles)
	{
		// Prepare filesystem access.
		global $wp_filesystem;
		WP_Filesystem();

		// Clear old cache and save new content.
		Wordeley::delete_article_cache();
		$wp_filesystem->put_contents(WORDELEY_FILE_STORE . '/articles.json', json_encode($articles), 0644);

		// Save timestamp.
		$options = get_option('wordeley_plugin_settings');
		$options['article_cache_last_updated_at'] = time();
		update_option('wordeley_plugin_settings', $options);
	}


	public static function retrieve_articles(array $authors)
	{
		$articles = [];

		foreach ($authors as $author) {
			// Get related articles.
			$response = Wordeley::api_request(
				'GET',
				'/search/catalog' . '?sort=title&authors=' . $author . '&limit=100'
			);
			$articles = array_merge($articles, $response);
		}

		return $articles;
	}

	public static function get_articles(array $authors = null)
	{
		if (empty($authors)) {
			return [];
		} else {
			if (is_file(WORDELEY_FILE_STORE . "/articles.json")) {
				// Get from cache.
				$articles = json_decode(file_get_contents(WORDELEY_FILE_STORE . "/articles.json"));
			} else {
				// Retrieve from API and update cache.
				$articles = Wordeley::retrieve_articles($authors);
				Wordeley::update_article_cache($articles);
			}

			// Return relevant authors only.
			return array_filter($articles, function ($article) use ($authors) {
				return count(
					array_intersect($authors, array_map(function ($author) {
						return $author['first_name'] . ' ' . $author['last_name'];
					}, $article['authors']))
				) > 0;
			});
		}
	}

	public static function parse_authors(string $serialized)
	{
		// Parse comma separated authors string.
		return array_map(function ($author) {
			return ltrim(rtrim($author));
		}, explode(',', $serialized));
	}
}
