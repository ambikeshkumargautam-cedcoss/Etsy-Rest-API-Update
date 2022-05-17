<?php
namespace Cedcommerce\Product;
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( ! class_exists( 'Class_Ced_Etsy_Category' ) ) {

	class Ced_Product_Category{


		public static $_instance;

		/**
		 * Ced_Etsy_Config Instance.
		 *
		 * Ensures only one instance of Ced_Etsy_Config is loaded or can be loaded.
		 *
		 * @since 1.0.0
		 * @static
		 */
		public static function get_instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Etsy getting seller taxonomies
		 *
		 * @since    1.0.0
		 */
		public function getEtsyCategories( $shop_name = '' ) {
			do_action( 'ced_etsy_refresh_token', $shop_name );
			$categories = etsy_request()->get( "application/seller-taxonomy/nodes", $shop_name );
			return $categories;
		}
	}
}
