<?php

/**
 * Class WC_TA_Checkout_Vat
 *
 * Handle all the checkout related VAT stuff
 */
class WC_TA_Checkout_Vat {

	/**
	 * Setup the class
	 */
	public function setup() {

		// Update the taxes on the checkout page whenever the order review template part is refreshed
		add_action( 'woocommerce_checkout_update_order_review', array(
			$this,
			'update_taxes_on_update_order_review'
		), 10, 1 );

		// Update the taxes in the checkout process when the checkout is processed
		add_action( 'woocommerce_checkout_process', array( $this, 'update_taxes_on_check_process' ), 10 );

		// Update the taxes when the line taxes are calculated in the admin
		add_filter( 'woocommerce_ajax_calc_line_taxes', array( $this, 'update_taxes_on_calc_line_taxes' ), 10, 3 );

		// Self-certification
		add_action( 'woocommerce_review_order_before_submit', array( $this, 'location_confirmation' ) );

		// Set default customer location
		add_filter( 'woocommerce_customer_default_location', array( $this, 'set_default_customer_location' ), 10, 1 );
	}

	/**
	 * Update taxes in cart
	 *
	 * @param object $transaction
	 * @param String $country_code
	 */
	private function update_taxes_in_cart( $transaction, $country_code ) {

		// The transaction lines
		$transaction_lines = $transaction->transaction_lines;

		// Check if there's a valid buyer tax number
		$valid_tax_number = false;
		if ( isset( $transaction->buyer_tax_number_valid ) && true === $transaction->buyer_tax_number_valid ) {
			$valid_tax_number = true;
		}

		// Check
		if ( count( $transaction_lines ) > 0 ) {

			// Create tax manager object
			$tax_manager = new WC_TA_Tax_Manager();

			// Loop
			foreach ( $transaction_lines as $tl ) {

				// Fetch the cart item linked to the transaction line
				$cart_item = WC()->cart->get_cart_item( $tl->custom_id );

				// Set all VAT to 0 if we've got a valid tax number because it's a B2B sale
				if ( true === $valid_tax_number ) {
					$tax_manager->add_product_tax_class( $cart_item['product_id'], $tl->product_type );

					// Add the new tax rate for this transaction line
					$tax_manager->add_tax_rate( $tl->product_type, 0.00, 'European VAT' );

					continue;
				}

				// Don't modify tax rates for default products
				if ( 'default' == $tl->product_type ) {
					continue;
				}

				// Add the new product class for this product
				$tax_manager->add_product_tax_class( $cart_item['product_id'], $tl->product_type );

				// Add the new tax rate for this transaction line
				$tax_manager->add_tax_rate( $tl->product_type, $tl->tax_rate, strtoupper( 'VAT ' . $country_code . ' ' . $tl->product_type ) );
			}
		}

	}

	/**
	 * Catch the update order review action and update taxes to selected billing country
	 *
	 * @param $post_data
	 */
	public function update_taxes_on_update_order_review( $post_data ) {

		// Parse the string
		parse_str( $post_data, $post_arr );

		// The billing country
		$billing_country = sanitize_text_field( $post_arr['billing_country'] );

		// Transaction extra's
		$transaction_extra = array();

		// Check for self declaration
		if ( 'yes' === WC_TA_Integration::$enable_self_declaration && isset( $post_arr['location_confirmation'] ) && 'on' === $post_arr['location_confirmation'] ) {
			// Set the location confirmation to true
			WC()->session->set( 'wc_ta_location_confirmation', true );

			// Set force country code
			$transaction_extra['force_country_code'] = $billing_country;
		}

		// Check for VAT number
		if ( isset( $post_arr['vat_number'] ) && '' !== $post_arr['vat_number'] ) {
			// Set VAT number
			$transaction_extra['buyer_tax_number'] = $post_arr['vat_number'];
		}

		// The cart manager
		$cart_manager = new WC_TA_Cart_Manager();

		// Setup request
		$request_calculate_tax = new WC_TA_Request_Calculate_Tax( $billing_country, $cart_manager->get_items_from_cart(), $transaction_extra );

		// Do request
		if ( $request_calculate_tax->do_request() ) {

			// Get the body
			$response_body = $request_calculate_tax->get_response_body();

			if ( isset( $response_body->transaction ) ) {

				// Update the taxes in cart based on transaction lines
				$this->update_taxes_in_cart( $response_body->transaction, $response_body->transaction->tax_country_code );
			}

		} else {

			/**
			 * @todo Better error handling. Check if AJAX is updated correctly is country doesn't match first try but does second try.
			 * Block order button
			 */

			wc_add_notice( $request_calculate_tax->get_error_message(), 'error' );

		}

	}

	/**
	 * Update taxes in the checkout processing process
	 */
	public function update_taxes_on_check_process() {

		$billing_country = isset( $_POST['billing_country'] ) ? sanitize_text_field( $_POST['billing_country'] ) : '';

		// Transaction extra's
		$transaction_extra = array();

		if ( 'yes' === WC_TA_Integration::$enable_self_declaration && isset( $_POST['location_confirmation'] ) && 'on' === $_POST['location_confirmation'] ) {

			// Set the location confirmation to true
			WC()->session->set( 'wc_ta_location_confirmation', true );

			// Set force country code
			$transaction_extra['force_country_code'] = $billing_country;
		}

		// Check for VAT number
		if ( isset( $_POST['vat_number'] ) && '' !== $_POST['vat_number'] ) {
			// Set VAT number
			$transaction_extra['buyer_tax_number'] = $_POST['vat_number'];
		}

		// The cart manager
		$cart_manager = new WC_TA_Cart_Manager();

		// Setup request
		$request_calculate_tax = new WC_TA_Request_Calculate_Tax( $billing_country, $cart_manager->get_items_from_cart(), $transaction_extra );

		// Do request
		if ( $request_calculate_tax->do_request() ) {

			// Get the body
			$response_body = $request_calculate_tax->get_response_body();

			if ( isset( $response_body->transaction ) ) {

				// Update the taxes in cart based on transaction lines
				$this->update_taxes_in_cart( $response_body->transaction, $response_body->transaction->tax_country_code );
			}

		} else {

			/**
			 * @todo Better error handling. We're in the middle of processing the order so adding a notice should abort the process by validation
			 */

			wc_add_notice( $request_calculate_tax->get_error_message(), 'error' );

		}

	}

	/**
	 * Update the taxes when the line taxes are calculated in the admin
	 *
	 * @param array $items
	 * @param int $order_id
	 * @param String $country
	 *
	 * @return array
	 */
	public function update_taxes_on_calc_line_taxes( $items, $order_id, $country ) {
		$formatted_items = array();

		// Check for items
		if ( isset( $items['order_item_id'] ) ) {

			// Get the order
			$order = wc_get_order( $order_id );

			// Loop through items
			foreach ( $items['order_item_id'] as $item_id ) {

				// Get the product ID
				$product_id = $order->get_item_meta( $item_id, '_product_id', true );

				// Format the item so it can be turned into a transaction line
				$formatted_items[ $item_id ] = array(
					'id'       => $product_id,
					'quantity' => $items['order_item_qty'][ $item_id ],
				);
			}

			// Setup request
			$request_calculate_tax = new WC_TA_Request_Calculate_Tax( $country, $formatted_items, array( 'force_country_code' => $country ) );

			// Do request
			if ( $request_calculate_tax->do_request() ) {

				// Get the body
				$response_body = $request_calculate_tax->get_response_body();

				if ( isset( $response_body->transaction ) ) {

					// Check
					if ( count( $response_body->transaction->transaction_lines ) > 0 ) {

						// Create tax manager object
						$tax_manager = new WC_TA_Tax_Manager();

						// Loop
						foreach ( $response_body->transaction->transaction_lines as $tl ) {

							// Don't modify tax rates for default products
							if ( 'default' == $tl->product_type ) {
								continue;
							}

							// Add the new product class for this product
							$tax_manager->add_product_tax_class( $tl->custom_id, $tl->product_type );

							// Add the new tax rate for this transaction line
							$tax_manager->add_tax_rate( $tl->product_type, $tl->tax_rate, strtoupper( 'VAT ' . $country . ' ' . $tl->product_type ) );

							// Add tax class to items
							$items['order_item_tax_class'][ $tl->custom_id ] = $tax_manager->clean_tax_class( $tl->product_type );
						}
					}

				}

			} else {

				/**
				 * @todo Better error handling. We're in the middle of processing the order so adding a notice should abort the process by validation
				 */

				wc_add_notice( $request_calculate_tax->get_error_message(), 'error' );

			}

		}

		return $items;

	}

	/**
	 * Self Declaration fields
	 */
	public function location_confirmation() {

		if ( 'yes' !== WC_TA_Integration::$enable_self_declaration ) {
			return;
		}

		// Get if checked
		$checked = WC()->session->get( 'wc_ta_location_confirmation', false );

		// Reset the checked state of location_confirmation
		WC()->session->set( 'wc_ta_location_confirmation', false );

		wc_get_template( 'location-confirmation-field.php', array(
			'location_confirmation_is_checked' => $checked,
			'countries'                        => WC()->countries->get_countries()
		), 'woocommerce-eu-vat-number', untrailingslashit( plugin_dir_path( WooCommerce_Taxamo::get_plugin_file() ) ) . '/templates/' );

	}

	/**
	 * Retrieve the customer location based on their IP and set as default location.
	 * @param string
	 */
	public function set_default_customer_location( $default ) {
		$ip = isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];

		$request_ip_location = new WC_TA_Request_IP_Location( $ip );

		if ( $request_ip_location->do_request() ) {
			$response_body = $request_ip_location->get_response_body();

			if ( isset( $response_body->country_code ) ) {
				return $response_body->country_code;
			}
		}

		return $default;
	}

}