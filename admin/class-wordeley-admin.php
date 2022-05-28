<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
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
 * @author     Your Name <email@example.com>
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
    }


    function wordeley_validate_plugin_settings($input)
    {
        $output['api_access_token'] = sanitize_text_field($input['api_access_token']);
        $output['api_access_token'] = sanitize_text_field($input['api_access_token']);
        $output['api_access_token'] = sanitize_text_field($input['api_access_token']);
        return $output;
    }

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
    }

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
}
