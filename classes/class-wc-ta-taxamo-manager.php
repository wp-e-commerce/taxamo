<?php

/**
 * Class WC_TA_Taxamo_Manager
 *
 */
class WC_TA_Taxamo_Manager {

	public function setup() {

		// Store transaction
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'store_transaction' ), 10, 1 );

		// Register payment
		add_action( 'woocommerce_payment_complete', array( $this, 'register_payment' ), 10, 1 );

		// Confirm transaction
		add_action( 'woocommerce_order_status_completed', array( $this, 'confirm_transaction' ), 10, 1 );
	}

	/**
	 * Catch the new order action and update taxes to selected billing country
	 *
	 * @param int $order_id
	 */
	public function store_transaction( $order_id ) {

		// Get the order
		$order = wc_get_order( $order_id );

		// Get the billing country
		$billing_country = WC()->customer->get_country();

		// The cart manager
		$cart_manager = new WC_TA_Cart_Manager();

		// The transaction extra
		$transaction_extra = array();

		// Location confirmation
		if ( true === WC()->session->get( 'wc_ta_location_confirmation', false ) ) {

			// Set force country code
			$transaction_extra['force_country_code'] = $billing_country;

		}

		// Check for VAT number
		$vat_number = get_post_meta( $order_id, WC_TA_Vat_Number_Field::META_KEY, true );
		if ( '' !== $vat_number ) {
			// Set VAT number
			$transaction_extra['buyer_tax_number'] = $vat_number;
		}

		// Setup request
		$request_store_transaction = new WC_TA_Request_Store_Transaction( $billing_country, $cart_manager->get_items_from_cart(), $order->billing_first_name . ' ' . $order->billing_last_name, $order->billing_email, $order_id, $transaction_extra );

		// Do request
		if ( $request_store_transaction->do_request() ) {

			// Get the body
			$response_body = $request_store_transaction->get_response_body();

			if ( isset( $response_body->transaction ) ) {

				// Attach transaction to order
				update_post_meta( $order_id, 'taxamo_transaction_key', $response_body->transaction->key );
			}

		} else {

			/**
			 * @todo Better error handling. Check if AJAX is updated correctly is country doesn't match first try but does second try.
			 * Block order button
			 */

			wc_add_notice( $request_store_transaction->get_error_message(), 'error' );

		}
	}

	/**
	 * Register the payment in Taxamo
	 *
	 * @param int $order_id
	 */
	public function register_payment( $order_id ) {

		// Get the order
		$order = wc_get_order( $order_id );

		// Get payment method title
		$payment_method_title = get_post_meta( $order_id, '_payment_method_title', true );

		// Get the Taxamo transaction key
		$transaction_key = get_post_meta( $order_id, 'taxamo_transaction_key', true );

		$request_register_payment = new WC_TA_Request_Register_Payment( $transaction_key, $order->get_total(), $payment_method_title );

		// Do request
		if ( ! $request_register_payment->do_request() ) {
			/**
			 * @todo Better error handling e.g. trigger error.
			 */
		}

	}

	/**
	 * Confirm the transaction
	 *
	 * @param $order_id
	 */
	public function confirm_transaction( $order_id ) {
		// Get the order
		$order = wc_get_order( $order_id );

		// Get the Taxamo transaction key
		$transaction_key = get_post_meta( $order_id, 'taxamo_transaction_key', true );

		// Setup the Request
		$request_confirm_transaction = new WC_TA_Request_Confirm_Transaction( $transaction_key );

		// Do request
		$request_confirm_transaction->do_request();

	}

}