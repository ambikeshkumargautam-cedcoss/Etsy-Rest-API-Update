<?php
namespace Cedcommerce\Product\ProductCategory;
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( ! class_exists( 'Class_Ced_Etsy_Category' ) ) {

	class Class_Ced_Etsy_Category {


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
			$client     = ced_etsy_getOauthClientObject( $shop_name );
			$success    = $client->CallAPI( 'https://openapi.etsy.com/v2/taxonomy/seller/get', 'GET', array(), array( 'FailOnAccessError' => true ), $categories );
			$categories = json_decode( json_encode( $categories ), true );
			return $categories;
		}
	}
}
