<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Class WC_EB_Autoloader
 *
 * @since 1.0.0
 */
class WC_TA_Autoloader {

	/**
	 * The extension class prefix
	 */
	const PREFIX = 'WC_TA_';

	/**
	 * @var string The classes path
	 */
	private $path;

	/**
	 * @var string the file prefix
	 */
	private $file_prefix;

	/**
	 * The Constructor
	 */
	public function __construct() {
		$this->path        = plugin_dir_path( __FILE__ );
		$this->file_prefix = strtolower( str_replace( '_', '-', self::PREFIX ) );
	}

	/**
	 * Autoloader load method. Load the class.
	 *
	 * @param $class_name
	 */
	public function load( $class_name ) {

		// Only autoload our WooCommerce Extension classes
		if ( 0 === strpos( $class_name, self::PREFIX ) ) {

			// String to lower
			$class_name = strtolower( $class_name );

			// Format file name
			$file_name = 'class-' . $this->file_prefix . str_ireplace( '_', '-', str_ireplace( self::PREFIX, '', $class_name ) ) . '.php';

			// Setup the file path
			$file_path = $this->path;

			if ( strpos( $class_name, 'wc_ta_request' ) === 0 ) {
				$file_path .= 'requests/';
			}

			// Append file name to class path
			$file_path .= $file_name;

			// Check & load file
			if ( file_exists( $file_path ) ) {
				require_once( $file_path );
			}

		}

	}

}