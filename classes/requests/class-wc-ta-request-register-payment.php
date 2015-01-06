<?php

class WC_TA_Request_Register_Payment extends WC_TA_Request {

	/**
	 * The constructor
	 *
	 * @param String $transaction_key
	 * @param int $amount
	 * @param String $gateway
	 */
	public function __construct( $transaction_key, $amount, $gateway = null ) {

		// Set request method and endpoint
		$this->set_request_methods( array( 'transactions', $transaction_key ) );
		$this->set_request_endpoint( 'payments' );

		// Set the HTTP method
		$this->set_http_method( 'POST' );

		// Set the transaction
		$this->set_request_body( array( 'amount'              => floatval( $amount ),
		                                'payment_information' => 'Gateway: ' . $gateway
			) );

		parent::__construct();
	}

}