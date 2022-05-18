<?php
/**
 * Product Delete class to delete the product.
 *
 * @since 1.0.0
 * @package Cedcommmerce\ProductDelete\Class
 */

namespace Cedcommerce\Product;

/**
 * Class ProductDelete
 *
 * @package Cedcommerce\Product.
 */
class Ced_Product_Delete {

   /**
    * Listing ID variable
    *
    * @var int
    */
    public $listing_id;

    /**
     * Etsy shop name.
     * @var string
     */
    public $shop_name;


    /**
     * Etsy Deleted response.
     * @var string
     */
    public $response;

    /**
     * Ced_Product_Delete constructor.
     */
    public function __construct( $product_id = '' ) {
        $this->listing_id = get_post_meta( $product_id, '_ced_etsy_listing_id_'.$this->shop_name, true );
    }

    /**
     * Get value of an property which isn't exist in this class. 
     *
     * @since    1.0.0
     */
    public function __get( $property_name ){
        if ( $property_name === 'result' || $property_name === 'res' || $property_name === 'output' || $property_name === 'etsy_result' ) {
            return $this->response;
        }
    }

    /**
     * Set the value of a property which is not exist in Class. 
     *
     * @since    1.0.0
     */
    public function __set( $name , $value ){
        if ( 'e_shop' === $name || 's_n' === $name || 'shop' === $name ) {
            $this->shop_name = $value;
        }
    }
    /**
     * Delete Listing from Etsy.
     *
     * @return array
     */
    public function ced_etsy_delete_product( $product_ids = array(), $shop_name = '' ) {
        foreach( $product_ids as $product_id ) {
            if( empty( $product_id ) ) {
                continue;
            }
            $listing_id = get_post_meta( $product_id, '_ced_etsy_listing_id_'.$shop_name , true );
            if ($listing_id) {
                do_action( 'ced_etsy_refresh_token', $shop_name );
                $this->response  =  etsy_request()->delete( "application/listings/{$listing_id}", $shop_name );
                if ( !isset( $this->response['error'] ) ) {
                    delete_post_meta( $value, '_ced_etsy_listing_id_' . $shop_name );
                    delete_post_meta( $value, '_ced_etsy_url_' . $shop_name );
                }
                return $this->response;
            }
        }
    }
}
