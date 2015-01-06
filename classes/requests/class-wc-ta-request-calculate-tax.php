<?php

class WC_TA_Request_Calculate_Tax extends WC_TA_Request {

	/**
	 * The constructor
	 *
	 * @param string $country
	 * @param array $items
	 * @param array $transaction_extra
	 */
	public function __construct( $country, $items, $transaction_extra = array() ) {

		// Set request method and endpoint
		$this->set_request_methods( array( 'tax' ) );
		$this->set_request_endpoint( 'calculate' );

		// Set the HTTP method
		$this->set_http_method( 'POST' );

		// Transaction manager
		$transaction_manager = new WC_TA_Transaction_Manager();

		// Set the transaction
		$this->set_request_body( array( 'transaction' => $transaction_manager->build_transaction( $country, $items, null, null, null, $transaction_extra ) ) );

		parent::__construct();
	}

}