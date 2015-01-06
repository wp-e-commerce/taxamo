<?php

class WC_TA_Tax_Manager {

	/**
	 * @var array The tax rates
	 */
	private $tax_rates = array();

	/**
	 * @var array Hashmap containing product ID's with the correct tax class
	 */
	private $product_to_tax_class = array();

	public function __construct() {
		$this->setup();
	}

	/**
	 * Setup the hooks and filters
	 */
	private function setup() {

		// Override the product tax class
		add_filter( 'woocommerce_product_tax_class', array( $this, 'override_product_tax_class' ), 10, 2 );

		// Override the tax rate
//		add_filter( 'woocommerce_matched_rates', array( $this, 'override_tax_rate' ), 10, 2 );
		add_filter( 'woocommerce_find_rates', array( $this, 'override_tax_rate' ), 10, 2 );

		// Override the rate code
		add_filter( 'woocommerce_rate_code', array( $this, 'override_rate_code' ), 10, 2 );

		// Override the rate label
		add_filter( 'woocommerce_rate_label', array( $this, 'override_rate_label'), 10, 2 );

	}

	/**
	 * Format a raw tax class to a sanitized tax class
	 *
	 * @param $raw_tax_class
	 *
	 * @return string
	 */
	public function clean_tax_class( $raw_tax_class ) {
		return 'taxamo_' . sanitize_title( $raw_tax_class );
	}

	/**
	 * Map a tax class to a product ID
	 *
	 * @param int $product_id
	 * @param String $product_type
	 *
	 * @return bool
	 */
	public function add_product_tax_class( $product_id, $product_type ) {
		if ( ! isset( $this->product_to_tax_class[ $product_id ] ) ) {
			$this->product_to_tax_class[ $product_id ] = $this->clean_tax_class( $product_type );

			return true;
		}

		return false;
	}

	/**
	 * Add a new tax rate
	 *
	 * @param String $product_type
	 * @param float $rate
	 * @param String $label
	 *
	 * @return bool
	 */
	public function add_tax_rate( $product_type, $rate, $label ) {

		$clean_slug = $this->clean_tax_class( $product_type );

		if ( ! isset( $this->tax_rates[ $clean_slug ] ) ) {
			$this->tax_rates[ $clean_slug ] = array(
				$clean_slug => array(
					'rate'     => number_format( floatval( $rate ), 4 ),
					'label'    => $label,
					'shipping' => 'yes',
					'compound' => 'no'
				)
			);

			return true;
		}

		return false;
	}

	/**
	 * Override the WooCommerce product tax class with the new Taxamo tax class
	 *
	 * @param String $tax_class
	 * @param WC_Product $product
	 *
	 * @return string
	 */
	public function override_product_tax_class( $tax_class, $product ) {

		// Check if we got a Taxamo class for this product
		if ( isset( $this->product_to_tax_class[ $product->id ] ) ) {
			$tax_class = $this->product_to_tax_class[ $product->id ];
		}

		return $tax_class;
	}

	/**
	 * Override the WooCommerce tax rate with the new Taxamo tax rate
	 *
	 * @param array $matched_tax_rates
	 * @param array $args
	 *
	 * @return array Correct tax rates
	 */
	public function override_tax_rate( $matched_tax_rates, $args ) {

		// Check if we got a Taxamo rate for this tax class
		if ( isset( $this->tax_rates[ $args['tax_class'] ] ) ) {
			$matched_tax_rates = $this->tax_rates[ $args['tax_class'] ];
		}

		return $matched_tax_rates;
	}

	/**
	 * Override the WooCommerce code string with the custom rate code
	 *
	 * @param String $code_string
	 * @param String $key
	 *
	 * @return String $code_string
	 */
	public function override_rate_code( $code_string, $key ) {

		if ( isset( $this->tax_rates[ $key ] ) ) {
			$code_string = strtoupper( $this->tax_rates[ $key ][ $key ]['label'] . '|' . $this->tax_rates[ $key ][ $key ]['rate'] );
		}

		return $code_string;
	}

	/**
	 * Override the WooCommerce rate label with the custom rate label
	 * @param $rate_name
	 * @param $key
	 *
	 * @return mixed
	 */
	public function override_rate_label( $rate_name, $key ) {

		if ( isset( $this->tax_rates[ $key ] ) ) {
			$rate_name = $this->tax_rates[ $key ][ $key ]['label'];
		}

		return $rate_name;
	}

}