<?php

class WC_TA_Request_Confirm_Transaction extends WC_TA_Request {

	/**
	 * The constructor
	 *
	 * @param String $transaction_key
	 */
	public function __construct( $transaction_key ) {

		// Set request method and endpoint
		$this->set_request_methods( array( 'transactions', $transaction_key ) );
		$this->set_request_endpoint( 'confirm' );

		// Set the HTTP method
		$this->set_http_method( 'POST' );

		parent::__construct();
	}

}