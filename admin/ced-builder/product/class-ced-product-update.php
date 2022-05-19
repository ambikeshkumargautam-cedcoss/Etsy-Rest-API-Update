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
     * Ced_Product_Update constructor.
     */
    public function __construct() {

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
     * Set the value of a property which is not exist in Class. 
     *
     * @since    1.0.0
     */
    public function __invoke( $shop_name = '', $product_id= '' ){
        $this->shop_name  = $shop_name;
        $this->product_id = $product_id;
        $this->listing_id = get_post_meta( $product_id, '_ced_etsy_listing_id_'.$this->shop_name, true );
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
        if ( ! is_array( $proIDs ) ) {
            $proIDs = array( $proIDs );
        }

        foreach ( $product_ids as $product_id ) {
            $listing_id  = get_post_meta( $product_id, '_ced_etsy_listing_id_' . $shop_name, true );
            $profileData = $this->getProfileAssignedData( $product_id, $shop_name );
            if ( 'false' == $profileData && ! $isPreview ) {
                return array( 'msg' => 'Profile Not Assigned' );
            }
            $payload    = new \Cedcommerce\Product\Ced_Product_Payload( $shop_name, $product_id );
            $arguements = $payload->ced_etsy_get_formatted_data( $product_id, $shop_name );
            $shop_id    = get_etsy_shop_id( $shop_name );
            $action     = "application/shops/{$shop_id}/listings/{$listing_id}";
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
            if ( isset( $this->response['exception'] ) ) {
                $error        = array();
                $error['msg'] = isset( $this->response['exception']['last_response'] ) ? $this->response['exception']['last_response'] : 'some error occured';
                return $error;
            }
            return $this->response;
        }
        return $this->uploadResponse;
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
}
