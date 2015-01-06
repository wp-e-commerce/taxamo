<?php

class WC_TA_Admin_Ebook {

	/**
	 * The setup method
	 */
	public function setup() {
		add_filter( 'product_type_options', array( $this, 'display_ebook_field' ) );
		add_action( 'woocommerce_process_product_meta_simple', array( $this, 'save_ebook_field' ) );
	}

	/**
	 * Display the E-Book field
	 *
	 * @param $options
	 *
	 * @return mixed
	 */
	public function display_ebook_field( $options ) {
		$options['ebook'] = array(
			'id'            => '_ebook',
			'wrapper_class' => 'show_if_simple',
			'label'         => __( 'E-Book', 'woocommerce-taxamo' ),
			'description'   => __( 'E-books are always virtual products but may have different tax rules.', 'woocommerce-taxamo' ),
			'default'       => 'no'
		);

		return $options;
	}

	/**
	 * Save the E-Book field
	 *
	 * @param $post_id
	 */
	public function save_ebook_field( $post_id ) {
		$is_ebook = isset( $_POST['_ebook'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_ebook', $is_ebook );

		// If it's an ebook, it's also a virtual product
		if ( 'yes' == $is_ebook ) {
			update_post_meta( $post_id, '_virtual', 'yes' );
		}
	}

}