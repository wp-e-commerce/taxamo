<?php
/*
	Plugin Name: WP eCommerce Taxamo
	Plugin URI: http://www.woothemes.com/
	Description: Use Taxamo services in your WP eCommerce store.
	Version: 1.0
	Author: Justin Sainton
	Author URI: https://wpecommerce.org/
	License: GPL v2+

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * Class Taxamo_WPEC
 *
 * @since 1.0.0
 */
class Taxamo_WPEC {

	const VERSION = '1.0.0';

	const TAXAMO_URL = 'https://wpecommerce.org/taxamo';

	private static $instance = null;

	/**
	 * Get the plugin file
	 *
	 * @static
	 * @since  1.0.0
	 * @access public
	 *
	 * @return String
	 */
	public static function get_plugin_file() {
		return __FILE__;
	}

	/**
	 * Constructor
	 */
	private function __construct() {}

	public static function get_instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new Taxamo_WPEC();
			self::$instance->bootstrap();
		}

		return self::$instance;
	}

	/**
	 * Init the plugin
	 *
	 * @since  1.0.0
	 * @access private
	 *
	 */
	private function init() {

		// Load plugin textdomain
		load_plugin_textdomain( 'taxamo-wpec', false, plugin_dir_path( self::get_plugin_file() ) . 'languages/' );

		// The VAT number Field
		$vat_number_field = new WC_TA_Vat_Number_Field();
		$vat_number_field->setup();

		// Setup the Checkout VAT stuff
		$checkout_vat = new WC_TA_Checkout_Vat();
		$checkout_vat->setup();

		// Setup Taxamo manager
		$taxamo_manager = new WC_TA_Taxamo_Manager();
		$taxamo_manager->setup();

		// Admin only classes
		if ( is_admin() ) {

			// The admin E-Book Field
			$admin_ebook = new WC_TA_Admin_Ebook();
			$admin_ebook->setup();

			// Setup the reports
			$reports = new WC_TA_Reports();
			$reports->setup();

			// Add Taxamo integration fields
			add_filter( 'wpec_tabs', array( $this, 'load_integration' ) );

			// Filter plugin links
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_links' ) );
		}

		// Enqueue scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

	}

	/**
	 * Plugin page links
	 */
	public function plugin_links( $links ) {

		$plugin_links = array(
			'<a href="https://wpecommerce.org/taxamo" target="_blank">' . __( 'Sign Up', 'taxamo-wpec' ) . '</a>',
		);

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Enqueue the VAT field scripts
	 */
	public function enqueue_scripts() {
		if ( wpsc_is_checkout() ) {

			wp_enqueue_script(
				'wpec_taxamo_checkout_js',
				plugins_url( '/assets/js/checkout' . ( ( ! SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', Taxamo_WPEC::get_plugin_file() ),
				array( 'jquery' )
			);

			wp_enqueue_style(
				'wpec_af_post_shop_order_css',
				plugins_url( '/assets/css/wpec-taxamo.css', Taxamo_WPEC::get_plugin_file() ),
				array(),
				'1.0'
			);

		}
	}
}

function wpec_taxamo_instance() {
	return Taxamo_WPEC::get_instance();
}


add_action( 'wpsc_pre_init', 'wpec_taxamo_instance' );