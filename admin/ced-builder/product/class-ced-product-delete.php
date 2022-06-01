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
    public function __construct( $shop_name = '', $product_id = '' ) {
        $this->shop_name = isset( $shop_name ) ? $shop_name : '';
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
        if (!is_array( $product_ids ) ) {
            $product_ids = array( $product_ids );
        }

        foreach( $product_ids as $product_id ) {
            if( empty( $product_id ) ) {
                continue;
            }
            $listing_id = get_post_meta( $product_id, '_ced_etsy_listing_id_'.$shop_name , true );
            // $listing_id = 1236576305;
            if ($listing_id) {
                // die($listing_id.$shop_name);
                do_action( 'ced_etsy_refresh_token', $shop_name );
                $action    = "application/listings/{$listing_id}";
                $response  =  etsy_request()->delete( $action , $shop_name );
                if (empty( $response ) || null == $response ) {
                    $response = array();
                }
                if ( !isset( $response['error'] ) ) {
                    delete_post_meta( $product_id, '_ced_etsy_listing_id_' . $shop_name );
                    delete_post_meta( $product_id, '_ced_etsy_url_' . $shop_name );
                    delete_post_meta( $product_id, '_ced_etsy_product_files_uploaded' . $listing_id );
                }
                return $response;
            }
        }
    }
}
