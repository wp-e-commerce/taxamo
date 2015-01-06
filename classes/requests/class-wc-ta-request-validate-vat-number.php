<?php

class WC_TA_Request_Validate_VAT_Number extends WC_TA_Request {

	/**
	 * The constructor
	 *
	 * @param $vat_number
	 * @param $country_code
	 */
	public function __construct( $vat_number, $country_code = '' ) {

		// Set request method and endpoint
		$this->set_request_methods( array( 'tax', 'vat_numbers', $vat_number ) );
		$this->set_request_endpoint( 'validate' );

		// Optionally set the country code
		if ( '' != $country_code ) {
			$this->set_request_body( array( 'country_code' => $country_code ) );
		}

		parent::__construct();
	}

}