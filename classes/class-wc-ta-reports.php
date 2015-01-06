<?php

class WC_TA_Reports {

	/**
	 * Setup the filters
	 */
	public function setup() {
		add_filter( 'woocommerce_reports_taxes_tax_rate', array( $this, 'fix_tax_rate' ), 10, 3 );
		add_filter( 'woocommerce_reports_taxes_rate', array( $this, 'fix_rate' ), 10, 3 );
	}

	/**
	 * Correct the dynamic Taxamo tax rate
	 *
	 * @param String $rate
	 * @param String $rate_id
	 * @param array $rate_row
	 *
	 * @return String
	 */
	public function fix_tax_rate( $rate, $rate_id, $rate_row ) {
		if ( 0 === strpos( $rate_id, 'taxamo_' ) ) {
			$rate = array_shift( explode( '|', $rate ) );
		}

		return $rate;
	}

	/**
	 * Correct the dynamic Taxamo rate
	 *
	 * @param String $rate
	 * @param String $rate_id
	 * @param array $rate_row
	 *
	 * @return String
	 */
	public function fix_rate( $rate, $rate_id, $rate_row ) {

		if ( 0 === strpos( $rate_id, 'taxamo_' ) ) {
			$rate = array_pop( explode( '|', $rate_row->tax_rate ) );
		}

		return $rate;
	}

}