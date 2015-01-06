<?php

class WC_TA_Transaction_Manager {

	private function build_transaction_lines( $items ) {

		// Transaction lines
		$transaction_lines = array();

		// Lo0p
		if ( count( $items ) > 0 ) {
			foreach ( $items as $item_key => $item ) {

				// The transaction line
				$transaction_line = array();

				// Get the product
				$product = wc_get_product( $item['id'] );

				// Set the product type
				$type = 'default';

				// Check if this is a virtual product
				if ( $product->is_virtual() ) {
					$type = 'e-service';
				} else {
					$transaction_line['informative'] = true;
				}

				// Check if this is an e-book
				$is_ebook = $product->__get( 'ebook' );
				if ( 'yes' === $is_ebook ) {
					$type = 'e-book';
				}

				// Set the product type
				$transaction_line['product_type'] = $type;

				// Custom ID
				$transaction_line['custom_id'] = "" . $item_key;

				// Quantity
				$transaction_line['quantity'] = $item['quantity'];

				// Price
				if ( $product->is_taxable() ) {

					$price_include_tax = get_option( 'woocommerce_prices_include_tax' );

					if ( 'yes' === $price_include_tax ) {
						$transaction_line['total_amount'] = $product->get_price();
					} else {
						$transaction_line['amount'] = $product->get_price();
					}

					// Get the base tax rates
					if ( method_exists( 'WC_Tax', 'get_base_tax_rates' ) ) {
						$base_tax_rates = WC_Tax::get_base_tax_rates( $product->get_tax_class() );
					}else {
						$base_tax_rates = WC_Tax::get_shop_base_rate( $product->get_tax_class() );
					}

					$base_tax_rate  = array_shift( $base_tax_rates );

					// Set the tax rate
					$transaction_line['tax_rate'] = $base_tax_rate['rate'];

					// Set the tax amount
//					$transaction_line['tax_amount'] = array_shift( WC_Tax::calc_tax( $product->get_price() * $item['quantity'], $base_tax_rates, ( 'yes' === $price_include_tax ) ) );

				} else {
					$transaction_line['amount']   = $product->get_price();
					$transaction_line['tax_rate'] = 0;
//					$transaction_line['tax_amount'] = 0;
				}

				// What do we want? Floats! When do we want them? Now!

				// Check if amount is set
				if ( isset( $transaction_line['amount'] ) ) {
					$transaction_line['amount'] = floatval( $transaction_line['amount'] );
				}

				// Check if total_amount is set
				if ( isset( $transaction_line['total_amount'] ) ) {
					$transaction_line['total_amount'] = floatval( $transaction_line['total_amount'] );
				}

				// Float the tax rate
				$transaction_line['tax_rate'] = floatval( $transaction_line['tax_rate'] );

//				$transaction_line['tax_amount'] = floatval( $transaction_line['tax_amount'] );

				// Add transaction line to transaction lines
				$transaction_lines[] = $transaction_line;
			}
		}

		return $transaction_lines;
	}

	/**
	 * Get a transaction array from the current cart
	 *
	 * @param String $country
	 * @param array $items
	 * @param null|String $buyer_name
	 * @param null|String $buyer_email
	 * @param null|String $custom_transaction_id
	 * @param array $transaction_extra
	 *
	 * @return array
	 */
	public function build_transaction( $country, $items, $buyer_name = null, $buyer_email = null, $custom_transaction_id = null, $transaction_extra = array() ) {

		// The transaction
		$transaction = array(
			'currency_code'        => get_woocommerce_currency(),
			'billing_country_code' => $country,
//			'test'                 => true,
			'buyer_ip'             => $_SERVER['REMOTE_ADDR'],
		);

		// Add buyer name
		if ( null !== $buyer_name ) {
			$transaction['buyer_name'] = $buyer_name;
		}

		// Add buyer email
		if ( null !== $buyer_email ) {
			$transaction['buyer_email'] = $buyer_email;
		}

		// Add custom transaction ID
		if ( null !== $custom_transaction_id ) {
			$transaction['custom_id'] = '' . $custom_transaction_id;
		}

//		if ( null !== $tax_number ) {
//			$transaction['buyer_tax_number'] = $tax_number;
//		}

		// Merge transaction extra values
		if ( is_array( $transaction_extra ) && count( $transaction_extra ) > 0 ) {
			$transaction = array_merge( $transaction, $transaction_extra );
		}

		// Get the transaction lines
		$transaction['transaction_lines'] = $this->build_transaction_lines( $items );

		return $transaction;
	}

}