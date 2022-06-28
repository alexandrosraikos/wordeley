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
class Wordeley_Communication_Controller {

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
	public static function ajax_handler( $completion ): void {
		// Prepare action identifier.
		if ( empty( $_POST['action'] ) ) {
			throw new RuntimeException( 'The action was not defined.' ); // TODO @alexandrosraikos: Translate.
		}
		$action = sanitize_key( $_POST['action'] );

		// Prepare nonce data.
		if ( empty( $_POST['nonce'] ) ) {
			throw new RuntimeException( 'The action related nonce was not included.' ); // TODO @alexandrosraikos: Translate.
		} else {
			$nonce = sanitize_key( $_POST['nonce'] );
		}

		// Verify action related nonce.
		if ( ! wp_verify_nonce( $nonce, $action ) ) {
			http_response_code( 403 );
			die( 'Unverified request for action: ' . esc_attr( $action ) ); // TODO @alexandrosraikos: Translate.
		}

		try {
			// The filtered $_POST data excluding WP specific keys.
			$data = $completion(
				array_filter(
					$_POST,
					function ( $key ) {
						return ( 'action' !== $key && 'nonce' !== $key );
					},
					ARRAY_FILTER_USE_KEY
				)
			);

			// Prepare the data and send.
			if ( empty( $data ) ) {
				http_response_code( 200 );
				die();
			} else {
				$data = wp_json_encode( $data );
				if ( false === $data ) {
					throw new RuntimeException( 'There was an error while encoding the data to JSON.' ); // TODO @alexandrosraikos: Translate.
				} else {
					http_response_code( 200 );
					die( esc_attr( $data ) );
				}
			}
		} catch ( \Exception $e ) {
			http_response_code( 500 );
			die( esc_attr( $e->getMessage() ) );
		}
	}

	/**
	 * Send a request to the Mendeley API.
	 *
	 * @param string $http_method The standardized HTTP method used for the request.
	 * @param string $uri The URI of the Mendeley API resource.
	 * @param array  $data The data to be sent according to the documented schema.
	 * @param bool   $requesting_access Whether this request is a request for an access token.
	 *
	 * @throws InvalidArgumentException For missing WordPress settings.
	 * @throws ErrorException For connectivity and other API issues.
	 *
	 * @since   1.0.0
	 * @author  Alexandros Raikos <alexandros@araikos.gr>
	 */
	public static function api_request( $http_method, $uri, $data = array(), $requesting_access = false ) {

		// Retrieve access token.
		$options      = get_option( 'wordeley_plugin_settings' );
		$access_token = $options['api_access_token'] ?? null;
		if ( empty( $access_token ) && ! $requesting_access ) {
			throw new InvalidArgumentException( 'You need to generate a Mendeley API access token in Wordeley settings.' ); // TODO @alexandrosraikos: Translate.
		}

		// Prepare headers.
		if ( $requesting_access ) {
			$headers = array( 'Content-Type: application/x-www-form-urlencoded' );
		} else {
			$headers = array(
				'Authorization: Bearer ' . $access_token,
				'Accept: application/vnd.mendeley-document.1+json',
			);
		}

		// Contact Mendeley API.
		$response = wp_remote_get(
			'https://api.mendeley.com' . $uri,
			array(
				'method'  => $http_method,
				'headers' => $headers,
				'body'    => http_build_query( $data ),
			)
		);

		// Handle unknown communication errors.
		if ( is_wp_error( $response ) ) {
			throw new ErrorException(
				'Unable to reach the Mendeley API. More details: ' . $response->get_error_message()
			); // TODO @alexandrosraikos: Translate.
		}

		// Get the response data.
		$response  = wp_remote_retrieve_body( $response );
		$http_code = wp_remote_retrieve_response_code( $response );

		// Handle response cases.
		if ( 200 !== $http_code && 201 !== $http_code && 204 !== $http_code && 404 !== $http_code ) {
			throw new ErrorException(
				'The Mendeley API returned an HTTP ' . $http_code . ' status code. More information: ' . esc_attr( $response ?? 'Not provided' ) . '.'
			); // TODO @alexandrosraikos: Translate.
		} else {
			if ( ! empty( $response ) && is_string( $response ) ) {
				$decoded = json_decode( $response, true );
				return ( json_last_error() === JSON_ERROR_NONE ) ? $decoded : $response;
			} else {
				throw new ErrorException( 'The response was invalid.' ); // TODO @alexandrosraikos: Translate.
			};
		}
	}
}
