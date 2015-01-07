<?php

/**
 * Class WC_TA_API_Request
 * Base API request class. Can't be initiated, must be extended.
 */
abstract class WC_TA_Request {

	// API URL base
	const URL = 'https://api.taxamo.com/api/v1/';

	/**
	 * HTTP method
	 * Default is GET, can be overwritten.
	 * @var string
	 */
	private $http_method = 'GET';

	/**
	 * The private token
	 * Automatically loaded from WC options.
	 * @var string
	 */
	private $private_token = '';

	/**
	 * The public token
	 * Automatically loaded from WC options.
	 * @var string
	 */
	private $public_token = '';

	/**
	 * The request content type
	 * Default is JSON, can be overwritten.
	 * @var string
	 */
	private $content_type = 'application/json';

	/**
	 * The request user agent
	 * Set automatically by class.
	 * @var string
	 */
	private $user_agent = '';

	/**
	 * The request methods.
	 * Required
	 * @var array
	 */
	private $request_methods = null;

	/**
	 * The request endpoint
	 * Required
	 * @var string
	 */
	private $request_endpoint = null;

	/**
	 * The request body
	 * Optional
	 * @var array
	 */
	private $request_body = null;

	/**
	 * The request response
	 * @var array
	 */
	private $response = null;

	/**
	 * The error message
	 * @var string
	 */
	private $error_message = '';

	/**
	 * The constructor
	 */
	public function __construct() {
		$this->private_token = WC_TA_Integration::$private_token;
		$this->public_token  = WC_TA_Integration::$public_token;
		$this->user_agent    = 'WooCommerce ' . WC()->version;
	}

	/**
	 * Build the request URL
	 *
	 * @return string
	 */
	private function build_request_url() {
		// Build the URL
		$url = self::URL;

		// Add request methods
		if ( ! is_null( $this->request_methods ) && is_array( $this->request_methods ) && count( $this->request_methods ) > 0 ) {
			$url .= implode( '/', $this->request_methods ) . '/';
		}

		// Add the endpoint
		if ( ! is_null( $this->request_endpoint ) && '' != $this->request_endpoint ) {
			$url .= $this->request_endpoint;
		}

		// Add the request body if this is a GET request
		if ( 'GET' == $this->http_method && ! is_null( $this->request_body ) && is_array( $this->request_body ) && count( $this->request_body ) > 0 ) {
			$url .= '?' . build_query( $this->request_body );
		}

		return $url;
	}

	/**
	 * Clear the request variable
	 *
	 * @return bool
	 */
	private function clear_response() {
		$this->response = null;

		return true;
	}

	/**
	 * Set the error message
	 *
	 * @param $error_message
	 *
	 * @return bool
	 */
	private function set_error_message( $error_message ) {
		$this->error_message = $error_message;

		return true;
	}

	/**
	 * Set the request method
	 *
	 * @param $request_methods
	 *
	 * @return bool
	 */
	protected function set_request_methods( $request_methods ) {
		$this->request_methods = $request_methods;

		return true;
	}

	/**
	 * Set the request endpoint
	 *
	 * @param $endpoint
	 *
	 * @return bool
	 */
	protected function set_request_endpoint( $endpoint ) {
		$this->request_endpoint = $endpoint;

		return true;
	}

	/**
	 * Set the request body
	 *
	 * @param array $request_body
	 *
	 * @return bool
	 */
	protected function set_request_body( $request_body ) {
		$this->request_body = $request_body;

		return true;
	}

	/**
	 * Set the HTTP method
	 *
	 * @param string $http_method
	 */
	protected function set_http_method( $http_method ) {
		$this->http_method = $http_method;
	}

	/**
	 * Get the error message
	 *
	 * @return string
	 */
	public function get_error_message() {
		return $this->error_message;
	}

	/**
	 * Do the request
	 *
	 * @return bool
	 */
	public function do_request() {

		$args = array(
			'method'     => $this->http_method,
			'headers'    => array(
				'Private-Token' => $this->private_token,
				'Public-Token'  => $this->public_token,
				'Content-Type'  => $this->content_type,
			),
			'timeout'    => 70,
			'sslverify'  => false,
			'user-agent' => $this->user_agent
		);

		// Add the request body if we've got one
		if ( ! is_null( $this->request_body ) && is_array( $this->request_body ) && count( $this->request_body ) > 0 ) {
			$args['body'] = json_encode( $this->request_body );
		}

		$this->response = wp_remote_post( $this->build_request_url(), $args );

		// Check if request is an error
		if ( is_wp_error( $this->response ) ) {
			$this->set_error_message( __( 'There was a problem connecting to the API.', 'taxamo-wpec' ) );
			$this->clear_response();

			return false;
		}

		// Check response code
		if ( ! isset( $this->response['response'] ) || '200' != $this->response['response']['code'] ) {

			// 400 is a 'Validation failed' error. Setting a more user friendly error.
			if ( 400 === $this->response['response']['code'] ) {

				$error_message = "We couldn't confirm your location.";

				if ( 'yes' === WC_TA_Integration::$enable_self_declaration ) {
					$error_message .= " Please manually validate it.";
				}

				// Set error message
				$this->set_error_message( $error_message );

				// Allow user to self declare his location
				WC()->session->set( 'wc_ta_need_location_confirmation', true );
			}

			// Try to set provided error message if error message is still empty
			if ( '' == $this->get_error_message() ) {
				$response_body = json_decode( $this->response['body'] );

				// Check for errors
				if ( isset( $response_body->errors ) && count( $response_body->errors ) > 0 ) {

					// Errors string
					$errors = implode( PHP_EOL, $response_body->errors );

					// Check if we got an error
					if ( '' != $errors ) {
						// Set errors
						$this->set_error_message( $errors );
					}

				}
			}

			// Fallback
			if ( '' === $this->get_error_message() ) {
				// Set response code error message
				$this->set_error_message( sprintf( __( 'Response code not OK (%s).', 'taxamo-wpec' ), ( ( isset( $this->response['response'] ) ) ? $this->response['response']['code'] . ' : ' . $this->response['response']['message'] : '' ) ) );
			}

			// Clear response
			$this->clear_response();

			return false;
		}

		// Check if there is a response body
		if ( empty( $this->response['body'] ) ) {
			$this->set_error_message( __( 'API returned an empty response.', 'taxamo-wpec' ) );
			$this->clear_response();

			return false;
		}

		return true;
	}

	/**
	 * Get the response body
	 *
	 * @return array
	 */
	public function get_response_body() {
		if ( ! is_null( $this->response ) ) {
			return json_decode( $this->response['body'] );
		}

		return array();
	}

}