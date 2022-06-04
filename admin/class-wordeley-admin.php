<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.github.com/alexandrosraikos/wordeley
 * @since      1.0.0
 *
 * @package    Wordeley
 * @subpackage Wordeley/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wordeley
 * @subpackage Wordeley/admin
 * @author     Alexandros Raikos <alexandros@araikos.gr>
 */
class Wordeley_Admin
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
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
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

        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/wordeley-admin.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
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

        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/wordeley-admin.js', array('jquery'), $this->version, false);
        wp_localize_script($this->plugin_name, 'AdministrativeProperties', [
            'AJAXEndpoint' => admin_url('admin-ajax.php'),
            'GenerateAccessTokenNonce' => wp_create_nonce('wordeley_generate_access_token'),
            'RefreshCacheNonce' => wp_create_nonce('wordeley_refresh_cache'),
            'ClearCacheNonce' => wp_create_nonce('wordeley_clear_cache')
        ]);
    }


    /**
     * Validates and sanitizes the submitted settings fields.
     * 
     * @since 1.0.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @return array
     */
    function wordeley_validate_plugin_settings($input)
    {
        // Sanitize text fields.
        $output['application_id'] = sanitize_text_field($input['api_access_token']);
        $output['application_secret'] = sanitize_text_field($input['api_access_token']);
        $output['api_access_token'] = sanitize_text_field($input['api_access_token']);

        // Parse author list.
        $output['article_authors'] = $input['article_authors'];
        $options = get_option('woreley_plugin_settings');
        if (!empty($options['article_authors'])) {
            if ($options['article_authors'] !== $output['article_authors']) {
                Wordeley::retrieve_articles($output['article_authors']);
            }
        }

        return $output;
    }

    /**
     * Registers the settings page sections and fields.
     * 
     * @since 1.0.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @return void
     */
    function register_settings()
    {

        register_setting(
            'wordeley_plugin_settings',
            'wordeley_plugin_settings',
            'wordeley_validate_plugin_settings'
        );

        add_settings_section(
            'section_one',
            'Mendeley API',
            'wordeley_plugin_section_one',
            'wordeley_plugin'
        );

        add_settings_field(
            'application_id',
            'Application ID',
            'wordeley_plugin_application_id',
            'wordeley_plugin',
            'section_one'
        );

        add_settings_field(
            'application_secret',
            'Application Secret',
            'wordeley_plugin_application_secret',
            'wordeley_plugin',
            'section_one'
        );

        add_settings_field(
            'api_access_token',
            'Access Token',
            'wordeley_plugin_api_access_token',
            'wordeley_plugin',
            'section_one'
        );

        add_settings_field(
            'api_access_token_automatic',
            __('Refresh token automatically', 'wordeley'),
            'wordeley_plugin_api_access_token_automatic',
            'wordeley_plugin',
            'section_one'
        );

        add_settings_section(
            'section_two',
            __('Catalog Filtering', 'wordeley'),
            'wordeley_plugin_section_two',
            'wordeley_plugin'
        );

        add_settings_field(
            'article_authors',
            __('Authors', 'wordeley'),
            'wordeley_plugin_article_authors',
            'wordeley_plugin',
            'section_two'
        );

        add_settings_section(
            'section_three',
            __('Manage Cache', 'wordeley'),
            'wordeley_plugin_section_three',
            'wordeley_plugin'
        );

        add_settings_field(
            'refresh_cache',
            __('Refresh cache', 'wordeley'),
            'wordeley_plugin_refresh_cache',
            'wordeley_plugin',
            'section_three'
        );

        add_settings_field(
            'delete_cache',
            __('Delete cache', 'wordeley'),
            'wordeley_plugin_delete_cache',
            'wordeley_plugin',
            'section_three'
        );

        add_settings_field(
            'refresh_cache_automatic',
            __('Refresh cache automatically', 'wordeley'),
            'wordeley_plugin_refresh_cache_automatic',
            'wordeley_plugin',
            'section_three'
        );
    }

    /**
     * Adds the settings page link to the WordPress Dashboard sidebar.
     * 
     * @since 1.0.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @return void
     */
    public function add_settings_page()
    {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wordeley-admin-display.php';

        add_options_page(
            'Wordeley Settings',
            'Wordeley',
            'manage_options',
            'wordeley-plugin',
            'render_settings_page'
        );
    }


    public static function update_access_token()
    {
        $options = get_option('wordeley_plugin_settings');
        if (empty($options['application_id']) || empty($options['application_secret'])) {
            throw new ErrorException("You need to enter your Mendeley application's credentials in Wordeley settings.");
        }
        $response = Wordeley::api_request(
            'POST',
            '/oauth/token',
            [
                'grant_type' => 'client_credentials',
                'scope' => 'all',
                'client_id' => $options['application_id'],
                'client_secret' => $options['application_secret'],
            ],
            true
        );
        $options['api_access_token'] = $response['access_token'];
        $options['api_access_token_expires_at'] = time() + $response['expires_in'];
        update_option('wordeley_plugin_settings', $options);
    }

    /**
     * Requests the generation of an API access token from the Mendeley API.
     * 
     * @since 1.0.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @return void
     */
    public function generate_access_token()
    {
        if (wp_doing_ajax()) {
            Wordeley::ajax_handler(
                function () {
                    Wordeley_Admin::update_access_token();
                }
            );
        } elseif (wp_doing_cron()) {
            $options = get_option('wordeley_plugin_settings');
            if ($options['api_access_token_automatic'] ?? 'off' == 'on') {
                Wordeley_Admin::update_access_token();
            }
        }
    }


    public function refresh_cache()
    {
        if (wp_doing_ajax()) {
            Wordeley::ajax_handler(
                function () {
                    Wordeley::update_article_cache();
                }
            );
        } elseif (wp_doing_cron()) {
            $options = get_option('wordeley_plugin_settings');
            if ($options['refresh_cache_automatic'] ?? 'off' == 'on') {
                Wordeley_Admin::update_access_token();
            }
        }
    }

    public function clear_cache()
    {
        if (wp_doing_ajax()) {
            Wordeley::ajax_handler(
                function () {
                    Wordeley::delete_article_cache();
                }
            );
        }
    }
}
