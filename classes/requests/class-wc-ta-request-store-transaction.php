<?php

class WC_TA_Request_Store_Transaction extends WC_TA_Request {

	/**
	 * The constructor
	 *
	 * @param string $country
	 * @param array $items
	 * @param String $buyer_name
	 * @param String $buyer_email
	 * @param int $custom_transaction_id
	 * @param array $transaction_extra
	 */
	public function __construct( $country, $items, $buyer_name, $buyer_email, $custom_transaction_id, $transaction_extra = array() ) {

		// Set request method and endpoint
		//$this->set_request_methods( array( 'transactions' ) );
		$this->set_request_endpoint( 'transactions' );

		// Set the HTTP method
		$this->set_http_method( 'POST' );

		// Transaction manager
		$transaction_manager = new WC_TA_Transaction_Manager();

		// Set the transaction
		$this->set_request_body( array( 'transaction' => $transaction_manager->build_transaction( $country, $items, $buyer_name, $buyer_email, $custom_transaction_id, $transaction_extra ) ) );

		parent::__construct();
	}

}