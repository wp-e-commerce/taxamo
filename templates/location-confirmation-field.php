<?php
if ( true === WC()->session->get( 'wc_ta_need_location_confirmation', false ) || true === $location_confirmation_is_checked ) {
	?>
	<p class="form-row location_confirmation terms">
		<label for="location_confirmation" class="checkbox">
			<input type="checkbox" class="input-checkbox"
			       name="location_confirmation" <?php checked( $location_confirmation_is_checked, true ); ?>
			       id="location_confirmation"/>
			<?php printf( __( 'I am established, have my permanent address, or usually reside within <strong>%s</strong>.', 'woocommerce' ), $countries[ WC()->customer->get_country() ] ); ?>
		</label>
	</p>
	<?php
	WC()->session->set( 'wc_ta_need_location_confirmation', false );
}
?>