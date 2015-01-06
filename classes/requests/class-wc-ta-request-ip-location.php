<?php

class WC_TA_Request_IP_Location extends WC_TA_Request {

	/**
	 * The constructor
	 *
	 * @param string $ip
	 */
	public function __construct( $ip ) {

		// Set request method and endpoint
		$this->set_request_methods( array( 'geoip' ) );
		$this->set_request_endpoint( $ip );

		parent::__construct();
	}

}
