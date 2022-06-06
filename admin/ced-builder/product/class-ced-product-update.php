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
class Ced_Product_Update {

   /**
    * Listing ID variable
    *
    * @var int
    */
    public $listing_id;

    /**
     * Listing ID variable
     *
     * @var int
     */
     public $product_id;

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
     * Set the value of a property which is not exist in Class. 
     *
     * @since    1.0.0
     */
    public function __construct( $product_id= '', $shop_name = '' ){
        $this->shop_name  = $shop_name;
        $this->product_id = !is_array( $product_id ) ? array( $product_id ) : $product_id;
        if (!empty( $this->shop_name ) && $this->product_id ) {
            $this->listing_id = get_post_meta( $this->product_id, '_ced_etsy_listing_id_'.$this->shop_name, true );
        }
    }

     /**
      * ***********************
      * UPDATE PRODUCT TO ETSY
      * ***********************
      *
      * @since 1.0.0
      *
      * @param array   $product_ids Product lsting  ids.
      * @param string  $shop_name Active shopName.
      *
      * @return $response ,
      */
    public function ced_etsy_update_product( $product_ids = array(), $shop_name = '' ) {
        if ( ! is_array( $product_ids ) ) {
            $product_ids = array( $product_ids );
        }
        $shop_name   = empty( $shop_name ) ? $this->shop_name : $shop_name;
        $product_ids = empty( $product_ids ) ? $this->product_id : $product_ids;
        if (!is_array( $product_ids) ) {
            $this->response['error'] = 'Ids Not set to valid formate';
            return $this->response;
        }
        foreach ( $product_ids as $product_id ) {
            if (empty( $this->listing_id )) {
                $this->listing_id = get_post_meta( $product_id, '_ced_etsy_listing_id_' . $shop_name, true );
            }
            $payload    = new \Cedcommerce\Product\Ced_Product_Payload( $shop_name, $product_id );
            $arguements = $payload->ced_etsy_get_formatted_data( $product_id, $shop_name );
            if ( !is_array( $arguements ) ){
                return $arguements;
            }
            $shop_id = get_etsy_shop_id( $shop_name );
            $action  = "application/shops/{$shop_id}/listings/{$this->listing_id}";
            do_action( 'ced_etsy_refresh_token', $shop_name );
            $this->response  = etsy_request()->put( $action, $arguements, $shop_name );
            if (isset( $this->response['listing_id'] ) ) {
                $product    = wc_get_product( $product_id ); 
                $pro_type   = $product->get_type();
                if ( 'variable' === $pro_type ) {
                    $arguements     = $payload->ced_variation_details( $product_id, $product_id, $shop_name );
                    $this->response = $this->ced_update_variation_to_etsy( $this->response['listing_id'], $arguements, $shop_name );
                }
            }
            if ( isset( $this->response['error'] ) ) {
                $error        = array();
                $error['error'] = isset( $this->response['error'] ) ? $this->response['error'] : 'some error occured';
                return $error;
            }
            return $this->response;
        }
    }

     /**
      * ***********************************
      * UPDATE LISTING OFFERINGS TO ETSY
      * ***********************************
      *
      * @since 1.0.0
      *
      * @param array   $product_ids Product lsting  ids.
      * @param string  $shop_name Active shopName.
      *
      * @return $response ,
      */
    public function ced_update_variation_to_etsy( $listing_id = '', $payload = '', $shop_name ='' ) {
        do_action( 'ced_etsy_refresh_token', $shop_name );
        $response = etsy_request()->put( "application/listings/{$listing_id}/inventory", array( 'products'=> $payload ), $shop_name );
        return $response;
    }


    /**
     * ***************************
     * UPDATE IMAGES ON ETSY SHOP
     * ***************************
     *
     * @since 1.0.0
     *
     * @param array $product_ids All  product ID. 
     * @param array $shop_name Active shop name of Etsy. 
     *
     * @return Array. 
     */
    public function ced_update_images_on_etsy( $product_ids = array(), $shop_name = '' ) {
        if ( ! is_array( $product_ids ) ) {
            $product_ids = array( $product_ids );
        }
        $shop_id = get_etsy_shop_id( $shop_name );
        if ( is_array( $product_ids ) && ! empty( $product_ids ) ) {
            foreach ( $product_ids as $pr_id ) {
                $listing_id        = get_post_meta( $pr_id, '_ced_etsy_listing_id_' . $shop_name, true );
                $previous_thum_ids = get_post_meta( $pr_id, 'ced_etsy_previous_thumb_ids' . $listing_id, true );
                foreach ( $previous_thum_ids as $attachment_id => $listing_image_id ){
                    // Get all the listing Images form Etsy
                     do_action( 'ced_etsy_refresh_token', $shop_name );
                     $action   = "application/shops/{$shop_id}/listings/{$listing_id}/images/{$listing_image_id}";
                     $response =  etsy_request()->delete( $action , $shop_name );
                     if( isset( $response['error'] ) ){
                        $error        = array();
                        $error['error'] = isset( $response['error'] ) ? $response['error'] : 'some error occured';
                        return $error;
                     }
                     
                }
                delete_post_meta( $pr_id, 'ced_etsy_previous_thumb_ids' . $listing_id );
                // Upload Images back to Etsy.
                $upload   = new \Cedcommerce\Product\Ced_Product_Upload();
                $upload->ced_etsy_prep_and_upload_img( $pr_id, $shop_name );
            }
        }
        return $pr_id;
    }

}
