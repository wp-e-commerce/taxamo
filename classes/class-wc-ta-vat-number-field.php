<?php

class WC_TA_Vat_Number_Field {

	const META_KEY = 'vat_number';

	/**
	 * Setup
	 *
	 * @since 1.0
	 */
	public function setup() {
		add_action( 'woocommerce_after_checkout_billing_form', array( $this, 'print_field' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_field' ) );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'display_field' ), 10, 1 );
	}

	/**
	 * Print the VAT field
	 *
	 * @since 1.0
	 */
	public function print_field() {
		woocommerce_form_field( 'vat_number', array(
			'label'       => __( 'VAT Number', 'taxamo-wpec' ),
			'description' => __( 'European companies can fill in their VAT number here to be exempt of VAT.', 'taxamo-wpec' ),
			'class' => array( 'update_totals_on_change' )
		), '' );
	}

	/**
	 * Save the VAT number to the order
	 *
	 * @param $order_id
	 */
	public function save_field( $order_id ) {
		if ( ! empty( $_POST['vat_number'] ) ) {

			// Save the VAT number
			update_post_meta( $order_id, self::META_KEY, sanitize_text_field( $_POST['vat_number'] ) );

			// Reset the customer VAT exempt state
			WC()->customer->set_is_vat_exempt( false );
		}
	}

	/**
	 * Display the VAT field in the backend
	 *
	 * @param $order
	 */
	public function display_field( $order ) {
		$vat_number = get_post_meta( $order->id, self::META_KEY, true );
		if ( '' != $vat_number ) {
			echo '<p><strong style="display:block;">' . __( 'VAT number', 'taxamo-wpec' ) . ':</strong> ' . $vat_number . '</p>';
		}
	}

}