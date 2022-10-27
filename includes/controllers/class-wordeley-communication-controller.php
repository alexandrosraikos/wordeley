<?php

/**
 * The file that defines the core plugin communication class
 *
 * @link       https://www.github.com/alexandrosraikos/wordeley
 * @since      1.0.0
 *
 * @package    Wordeley
 * @subpackage Wordeley/includes/controller
 */

/**
 * The core plugin communication class.
 *
 * This is used for handling communication between the client and WordPress,
 * as well as between WordPress and the Mendeley API.
 *
 * @since      1.0.0
 * @package    Wordeley
 * @subpackage Wordeley/includes/controllers
 * @author     Alexandros Raikos <alexandros@araikos.gr>
 */
class Wordeley_Communication_Controller
{

	/**
	 * The generalized handler for AJAX calls.
	 *
	 * @param   callable $completion The callback for completed data.
	 * @throws  RuntimeException When checks or response encoding fails.
	 *
	 * @usedby All functions triggered by the WordPress AJAX handler.
	 *
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.0.0
	 */
	public static function ajax_handler($completion): void
	{
		// Prepare action identifier.
		if (empty($_POST['action'])) {
			throw new RuntimeException('The action was not defined.');
		}
		$action = sanitize_key($_POST['action']);

		// Prepare nonce data.
		if (empty($_POST['nonce'])) {
			throw new RuntimeException('The action related nonce was not included.');
		} else {
			$nonce = sanitize_key($_POST['nonce']);
		}

		// Verify action related nonce.
		if (!wp_verify_nonce($nonce, $action)) {
			http_response_code(403);
			die('Unverified request for action: ' . esc_attr($action));
		}

		try {
			// The filtered $_POST data excluding WP specific keys.
			$data = $completion(
				array_filter(
					$_POST,
					function ($key) {
						return ('action' !== $key && 'nonce' !== $key);
					},
					ARRAY_FILTER_USE_KEY
				)
			);

			// Prepare the data and send.
			if (empty($data)) {
				http_response_code(200);
				die();
			} else {
				$data = wp_json_encode($data);
				if (false === $data) {
					throw new RuntimeException('There was an error while encoding the data to JSON.');
				} else {
					http_response_code(200);
					die($data);
				}
			}
		} catch (\Exception $e) {
			http_response_code(500);
			die(esc_attr($e->getMessage()));
		}
	}

	/**
	 * Send a request to the Mendeley API.
	 *
	 * @param string $http_method The standardized HTTP method used for the request.
	 * @param string $uri The URI of the Mendeley API resource.
	 * @param array  $data The data to be sent according to the documented schema.
	 * @param bool   $request_access_token Whether this request is a request for an access token.
	 *
	 * @throws ErrorException For connectivity and other API issues.
	 *
	 * @since   1.0.0
	 * @author  Alexandros Raikos <alexandros@araikos.gr>
	 */
	public static function api_request($http_method, $uri, $data = array(), $request_access_token = false)
	{

		// Prepare headers and authorization data.
		if ($request_access_token) {
			$headers = array(
				'Content-Type' => 'application/x-www-form-urlencoded',
			);
		} else {
			$headers = array(
				'Authorization' => 'Bearer ' . rawurlencode(self::get_access_token()),
				'Accept'        => 'application/vnd.mendeley-document.1+json',
			);
		}

		// Contact Mendeley API.
		$response = wp_remote_post(
			'https://api.mendeley.com' . $uri,
			array(
				'httpversion' => '1.1',
				'method'      => $http_method,
				'headers'     => $headers,
				'body'        => http_build_query($data),
			)
		);

		// Handle unknown communication errors.
		if (is_wp_error($response)) {
			throw new ErrorException(
				'Unable to reach the Mendeley API. More details: ' . $response->get_error_message()
			);
		}

		// Get the response data.
		$data      = wp_remote_retrieve_body($response);
		$http_code = wp_remote_retrieve_response_code($response);

		// Handle response cases.
		if (200 !== $http_code && 201 !== $http_code && 204 !== $http_code && 404 !== $http_code) {
			throw new ErrorException(
				'The Mendeley API returned an HTTP ' . $http_code . ' status code. More information: ' . esc_attr($data ?? 'Not provided') . '.'
			);
		} else {
			if (!empty($data) && is_string($data)) {
				$decoded = json_decode($data, true);
				return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $data;
			} else {
				throw new ErrorException('The response was invalid.');
			};
		}
	}

	/**
	 * Check the Mendeley API access credentials.
	 *
	 * Checks for existing Application ID, Secret and Access Token.
	 *
	 * @since 1.0.0
	 * @throws ErrorException When credentials are missing.
	 */
	public static function check_adequate_access()
	{
		// Check Mendeley application credentials.
		$options = get_option('wordeley_plugin_settings');
		if (empty($options['application_id']) || empty($options['application_secret'])) {
			throw new ErrorException(
				__("You need to enter your Mendeley application's credentials in Wordeley settings.", 'wordeley')
			);
		}

		// Check for valid access token.
		$access_options = get_option('wordeley_plugin_access_settings');
		if (!empty($access_options['api_access_token']) && !empty($access_options['api_access_token_expires_at'])) {
			return (($access_options['api_access_token_expires_at'] - time()) > 0);
		} else {
			return false;
		}
	}

	/**
	 * Get the access token from the database.
	 */
	public static function get_access_token()
	{
		if (self::check_adequate_access()) {
			$access_options = get_option('wordeley_plugin_access_settings');
			return $access_options['api_access_token'];
		} else {
			return self::update_access_token(true, true);
		}
	}

	/**
	 * Update the Mendeley API Access Token.
	 *
	 * Retrieves a new Mendeley API Access Token and persists it to the database.
	 *
	 * @since 1.0.0
	 * @param bool $return Whether to return the retrieved access token.
	 * @param bool $force Whether to force updating.
	 * @throws ErrorException When credentials are missing.
	 */
	public static function update_access_token(bool $return = false, bool $force = false)
	{
		if (!self::check_adequate_access() || $force) {
			$options                                       = get_option('wordeley_plugin_settings');
			$response                                      = self::api_request(
				'POST',
				'/oauth/token',
				array(
					'grant_type'    => 'client_credentials',
					'scope'         => 'all',
					'client_id'     => $options['application_id'],
					'client_secret' => $options['application_secret'],
				),
				true
			);
			$access_options                                = array();
			$access_options['api_access_token']            = $response['access_token'];
			$access_options['api_access_token_expires_at'] = time() + $response['expires_in'];
			update_option('wordeley_plugin_access_settings', $access_options);
			if ($return) {
				return $response['access_token'];
			}
		}
	}
}
