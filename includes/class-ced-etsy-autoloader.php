<?php
/**
 * Cedcommerce Autoloader.
 *
 * @package WooCommerce-etsy-inegration\includes
 * @version 2.0.8
 */

defined( 'ABSPATH' ) || exit;

class CedEtsyAutoloader {

	/**
	 * Path to the includes directory.
	 *
	 * @var string
	 */
	private $include_path = '';

	/**
	 * The Constructor.
	 */
	public function __construct() {
		if ( function_exists( '__autoload' ) ) {
			spl_autoload_register( '__autoload' );
		}
		spl_autoload_register( array( $this, 'autoload' ) );
		$this->include_path = CED_ETSY_DIRPATH;

	}

	/**
	 * Take a class name and turn it into a file name.
	 *
	 * @param  string $class Class name.
	 * @return string
	 */
	private function ced_etsy_get_file_name_from_class( $class ) {
		if ( strpos( $class, '\\' ) !== false ) {
			$ced_name = strstr( $class , '\\' );
			$s        = 'class-' . strtolower( str_replace( '\\', '', $ced_name ) ) . '.php';
			$s        = str_replace( '_', '-'  , $s );
		} else {
			$s = 'class-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';
		}
		return $s;
	}

	/**
	 * Include a class file.
	 *
	 * @param  string $path File path.
	 * @return bool Successful or not.
	 */
	private function ced_load_file( $path ) {
		if ( $path && is_readable( $path ) ) {
			include_once $path;
			return true;
		}
		return false;
	}


	/**
	 * Auto-loading cedcommerce classes for reduce memory consumption.
	 *
	 * @param string $class Class name.
	 */
	public function autoload( $class ) {
		if ( 0 !== strpos( $class, 'Ced_' ) ) {
			return;
		}
		$file = $this->ced_etsy_get_file_name_from_class( $class );
		$path = '';
		if (
			   0 === strpos( strtolower($class), 'ced_pro' ) || 0 === strpos( strtolower($class), 'ced_cat' )) {
			$path = $this->include_path . 'admin/ced-builder/product/';
		}elseif ( 0 === strpos( strtolower($class), 'ced_ord' )) {
			$path = $this->include_path . 'admin/ced-builder/order/';
		} elseif ( 0 === strpos( strtolower($class), 'ced_etsy_m' ) ) {
			$path = $this->include_path . 'admin/lib/';
		} elseif ( 0 === strpos( strtolower($class), 'woocommmerce_etsy_integration_admin' ) ) {
			$path = $this->include_path . 'admin/';
		}
		if ( empty( $path ) || ! $this->ced_load_file( $path . $file ) ) {
			$this->ced_load_file( $this->include_path . $file );
		}
	}


}







