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
class Wordeley {
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
	public function __construct() {
		require_once ABSPATH . 'wp-admin/includes/file.php';

		$this->plugin_name = 'wordeley';
		if ( defined( 'WORDELEY_VERSION' ) ) {
			$this->version = WORDELEY_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		if ( ! defined( 'WORDLEY_FILE_STORE' ) ) {
			define( 'WORDELEY_FILE_STORE', trailingslashit( wp_upload_dir()['basedir'] ) . 'wordeley/' );
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
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wordeley-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wordeley-i18n.php';

		/**
		 * The controllers classes responsible for Mendeley related actions.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/controllers/class-wordeley-communication-controller.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/controllers/class-wordeley-author-controller.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/controllers/class-wordeley-article-controller.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wordeley-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-wordeley-public.php';

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
	private function set_locale() {

		$plugin_i18n = new Wordeley_I18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Wordeley_Admin( $this->get_plugin_name(), $this->get_version() );

		/**
		 * Add the settings pages.
		 */
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_settings_page' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );

		/**
		 * Enqueue all admin-related scripts and stylesheets.
		 */
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		/**
		 * Add all AJAX handlers.
		 */
		$this->loader->add_action( 'wp_ajax_wordeley_generate_access_token', $plugin_admin, 'generate_access_token' );
		$this->loader->add_action( 'wp_ajax_wordeley_refresh_cache', $plugin_admin, 'refresh_cache_handler' );
		$this->loader->add_action( 'wp_ajax_wordeley_clear_cache', $plugin_admin, 'clear_cache_handler' );

		/**
		 * Hook all cron-based jobs and define schedules.
		 */
		$this->loader->add_action( 'wordeley_access_token_cron_handler_hook', $plugin_admin, 'generate_access_token' );
		$this->loader->add_action( 'wordeley_refresh_cache_cron_handler_hook', $plugin_admin, 'refresh_cache_handler' );

		add_filter(
			'cron_schedules',
			function ( $schedules ) {
				$schedules['hourly'] = array(
					'interval' => 3600,
					'display'  => 'Every 60 minutes.',
				);
				return $schedules;
			}
		);
		add_filter(
			'cron_schedules',
			function ( $schedules ) {
				$schedules['monthly'] = array(
					'interval' => 2592000,
					'display'  => 'Every month.',
				);
				return $schedules;
			}
		);
		if ( ! wp_next_scheduled( 'wordeley_access_token_cron_handler_hook' ) ) {
			wp_schedule_event( time(), 'hourly', 'wordeley_access_token_cron_handler_hook' );
		}
		if ( ! wp_next_scheduled( 'wordeley_refresh_cache_cron_handler_hook' ) ) {
			wp_schedule_event( time(), 'monthly', 'wordeley_refresh_cache_cron_handler_hook' );
		}
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Wordeley_Public( $this->get_plugin_name(), $this->get_version() );

		/**
		 * Enqueue all public-facing scripts and stylesheets.
		 */
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		/**
		 * Register all shortcodes.
		 */
		$this->loader->add_action( 'init', $plugin_public, 'register_shortcodes' );

		/**
		 * Add all AJAX handlers.
		 */
		$this->loader->add_action( 'wp_ajax_wordeley_get_articles', $plugin_public, 'Wordeley_Public::catalogue_shortcode' );
		$this->loader->add_action( 'wp_ajax_nopriv_wordeley_get_articles', $plugin_public, 'Wordeley_Public::catalogue_shortcode' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Wordeley_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
