<?php

class WC_TA_Cart_Manager {

	/**
	 * Get the formatted items in current cart ready for transaction lines
	 *
	 * @return array
	 */
	public function get_items_from_cart() {

		// The items
		$items = array();

		// Loop through cart items
		$cart = WC()->cart->get_cart();
		if ( count( $cart ) > 0 ) {
			foreach ( $cart as $cart_key => $cart_item ) {
				$items[ $cart_key ] = array(
					'id'       => $cart_item['product_id'],
					'quantity' => $cart_item['quantity']
				);
			}
		}

		return $items;
	}

}