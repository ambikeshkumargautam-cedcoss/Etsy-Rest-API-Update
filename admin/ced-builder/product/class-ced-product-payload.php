<?php
/**
 * Product Data Payload For Upload Update and all.
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
class Ced_Product_Payload {

   /**
    * Listing ID variable
    *
    * @var int
    */
   public $listing_id;
    /**
     * Ced Etsy global settings
     *
     * @var array
     */
    public $ced_global_settings;
    /**
     * Profile assign flag
     *
     * @var bool
     */
    public $is_profile_assing;
    /**
     * Mapped profile data.
     *
     * @var int
     */
    public $profile_data;
    /**
     * Is product type dowloadable or not.
     *
     * @var array
     */
    public $is_downloadable;
    /**
     * Downloadable file data.
     *
     * @var string
     */
    public $downloadable_data;
    /**
     * Product Type variable
     *
     * @var int
     */
    public $product_type;

    /**
     * Etsy shop name.
     * @var string
     */
    public $shop_name;

    /**
     * Product ID.
     * @var int
     */
    public $product;

    /**
     * Etsy Payload response.
     * @var string
     */
    public $response;

   /**
     * ********************************************************
     * SET SETTINGS VALUE AND ETSY CREDS TO MANAGE API REQUEST
     * ********************************************************
     *
     * @since 1.0.0
     *
     */

   public function __construct( $shop_name = '', $product_id= '' , $listing_id = '' ) {
       $this->ced_global_settings = get_option( 'ced_etsy_global_settings', array() );
       $this->saved_etsy_details  = get_option( 'ced_etsy_details', array() );
       $this->shop_name           = $shop_name;
       $this->product_id          = $product_id;
       $this->listing_id          = $listing_id;
       if ( $this->shop_name ) {
           $this->ced_global_settings = isset( $this->ced_global_settings[$this->shop_name] ) ? $this->ced_global_settings[$this->shop_name] : $this->ced_global_settings;
           $this->saved_etsy_details  = isset( $this->saved_etsy_details[$this->shop_name] ) ? $this->saved_etsy_details[$this->shop_name] : $this->saved_etsy_details;
       }
   }

    /**
     * Get value of an property which isn't exist in this class. 
     *
     * @param array  $property_name Get result by Defferent names.
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
     * @param array  $proId Product lsting  ids.
     * @since    1.0.0
     */
    public function __set( $name , $value ){
        if ( 'e_shop' === $name || 's_n' === $name || 'shop' === $name ) {
            $this->shop_name = $value;
        }
        if ('type' === $name || 'p_type' === $name || 'wc_type' === $name ) {
            $this->product_type = $value;
        }
    }

    /**
     * **********************************************
     * Get Woocommerce Product Data, Type, Parent ID.
     * **********************************************
     *
     * @since 1.0.0
     *
     * @param string  $pr_id Product lsting  ids.
     * @param string $shop_name Active shopName.
     *
     * @link  http://www.cedcommerce.com/
     * @return string Woo product type.
     */

    public function ced_pro_type( $pr_id = '' ){
        if (empty( $pr_id )) {
            $pr_id = $this->product_id;
        }
        $wc_product    = wc_get_product( $pr_id );
        $this->product = $wc_product->get_data();
        $type          = $wc_product->get_type();
        if ('variable' === $type ) {
            $this->product_type       = 'variable';
            $this->parent_id = $wc_product->get_parent_id();
            return $this->product_type;
        }
        $this->product_type = 'simple';
        return $this->product_type;
    }

    /**
     * *****************************************
     * GET ASSIGNED PRODUCT DATA FROM PROFILES
     * *****************************************
     *
     * @since 1.0.0
     *
     * @param array  $product_id Product lsting  ids.
     * @param string $shop_name Active Etsy shopName.
     *
     * @link  http://www.cedcommerce.com/
     * @return $profile_data assigined profile data .
     */

    public function ced_etsy_check_profile( $product_id = array(), $shop_name = ''){
        if ( 'variable' == $this->ced_pro_type( $product_id ) ) {
            $product_id = $this->parent_id;
        }
        $category_id = isset( $this->product['category_ids'] ) ? $this->product['category_ids'] : array();
        $profile_id  = get_post_meta( $product_id, 'ced_etsy_profile_assigned' . $shop_name, true );
        if ( ! empty( $profile_id ) ) {
            $profile_id = $profile_id;
        } else {
            foreach ( $category_id as $key => $value ) {
                $profile_id = get_term_meta( $value, 'ced_etsy_profile_id_' . $shop_name, true );

                if ( ! empty( $profile_id ) ) {
                    break;

                }
            }
        }
        global $wpdb;
        if ( isset( $profile_id ) && ! empty( $profile_id ) ) {
            $this->is_profile_assing = true;
            $profile_data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_etsy_profiles WHERE `id`=%s ", $profile_id ), 'ARRAY_A' );
            if ( is_array( $profile_data ) ) {
                $profile_data = isset( $profile_data[0] ) ? $profile_data[0] : $profile_data;
                $profile_data = isset( $profile_data['profile_data'] ) ? json_decode( $profile_data['profile_data'], true ) : array();
            }
        } else {
            $this->is_profile_assing = false;
            return 'false';
        }
        $this->profile_data = isset( $profile_data ) ? $profile_data : '';
        return $this->profile_data;
    }


    /**
     * ************************************
     * Parse Tags remove special character.
     * ************************************
     *
     * @since 1.0.0
     *
     * @param array  $tags Etsy tags.
     *
     * @return array parsed tags.
     */
    private function ced_etsy_tags( $tags = array() ){
        $tags = array();
        foreach ( $explode_tags as $key_tags => $tag_name ) {
            $tag_name = str_replace( ' ', '-', $tag_name );
            $tag_name = preg_replace( '/[^A-Za-z0-9\-]/', '', $tag_name );
            $tag_name = str_replace( '-', ' ', $tag_name );
            if ( $key_tags <= 12 && strlen( $tag_name ) <= 20 ) {
                $tags[ $key_tags ] = $tag_name;
            }
        }
        $tags = implode( ',', array_filter( array_values( array_unique( $tags ) ) ) );
        $tags = isset( $tags ) ? array( $tags ) : array();
        return $tags;
    }




    /**
     * **********************************************
     * GET FORMATTED DATA FOR UPLOAD/UPDATE PRODUCTS
     * **********************************************
     *
     * @since 1.0.0
     *
     * @param int  $product_id Woo Product ids.
     * @param string $shop_name Active etsy shop name.
     *
     * @return $arguments all possible arguments .
     */
    public function ced_etsy_get_formatted_data( $product_id = '', $shop_name = '' ) {
        $profile_data = $this->ced_etsy_check_profile( $product_id, $shop_name );
        if ( 'false' == $profile_data ) {
            return 'Profile Not Assigned';
        }
        
        $product            = wc_get_product( $product_id );
        $productData        = $product->get_data();
        $product_type       = $product->get_type();
        
        /**
         * ***************************
         *  GET DIGITAL PRODUCT DATA
         * ***************************
         */
        $this->is_downloadable = isset( $productData['downloadable'] ) ? $productData['downloadable'] : 0;
        if ( $this->is_downloadable ) {
          $this->downloadable_data = isset( $productData['downloads'] ) ? $productData['downloads'] : array();
      }
       #Global settings values.
      $etsy_data_field    = isset( $this->ced_global_settings['product_data'] ) ? $this->ced_global_settings['product_data'] : $this->ced_global_settings[$shop_name]['product_data'];
      $pro_data = array();
      foreach ( $etsy_data_field as $meta_key => $value ) {

        $pro_val = get_post_meta( $product_id, $meta_key, true );
        if ( empty( $pro_val ) ) {
                #Check if product meta key is set in profile.
            $pro_val = $this->fetch_meta_value( $product_id, $meta_key );
        }
            #Check if product meta key is set in global settings.
        if ( empty( $pro_val ) ) {
            $pro_val = $value['default'];
        }
        $pro_data[trim( str_replace( '_ced_etsy_', ' ', $meta_key) )] = !empty( $pro_val ) ? $pro_val : '';
    }

    $productTitle        = !empty( $pro_data['title'] ) ? $pro_data['title'] : $productData['name'];
    $productTitle        = $pro_data['title_pre'] . ' ' . $productTitle . ' ' . $pro_data['title_post'];
    $product_description = !empty( $pro_data['description'] ) ? $pro_data['description'] : $pro_data['description_pre'] . $productData['description'] . '</br>' . $pro_data['description_post'];
    $pro_price           = get_post_meta( $product_id, '_ced_etsy_price', true );
    $pr_pf_l             = $this->fetch_meta_value( $product_id, '_ced_etsy_price' );

        // Price
    if ( ! empty( $pro_price ) ) {
        $m_ty_pr_l = get_post_meta( $product_id, '_ced_etsy_markup_type', true );
        $markupValue               = (float) get_post_meta( $product_id, '_ced_etsy_markup_value', true );
        if ( 'Percentage_Increased' == $m_ty_pr_l ) {
            $pro_price = (float) $pro_price + ( ( (float) $markupValue / 100 ) * (float) $pro_price );
        } else {
            $pro_price = (float) $pro_price + (float) $markupValue;
        }
    } else {
        if ( empty( $pr_pf_l ) ) {
            $pr_pf_l = (float) $productData['price'];
        }
        $m_t_p_l = $this->fetch_meta_value( $product_id, '_ced_etsy_markup_type' );
        $markupValue               = (float) $this->fetch_meta_value( $product_id, '_ced_etsy_markup_value' );
        if ( 'Percentage_Increased' == $m_t_p_l ) {
            $pro_price = (float) $pr_pf_l + ( ( (float) $markupValue / 100 ) * (float) $pr_pf_l );
        } else {
            $pro_price = (float) $pr_pf_l + (float) $markupValue;
        }
    }

    if ( 'variable' == $product_type ) {
        $variations = $product->get_available_variations();
        if ( isset( $variations['0']['display_regular_price'] ) ) {
            $pro_price    = $variations['0']['display_regular_price'];
            $varId        = $variations['0']['variation_id'];
            $pro_qty = 1;
        }
    } else {
        $manage_stock = get_post_meta( $product_id, '_manage_stock', true );
        $stock_status = get_post_meta( $product_id, '_stock_status', true );
        $pro_qty      = get_post_meta( $product_id, '_ced_etsy_stock', true );
        if ( '' == $pro_qty ) {
            $pro_qty = $this->fetch_meta_value( $product_id, '_ced_etsy_stock' );
        }
        if ( '' == $pro_qty ) {
            $pro_qty = get_post_meta( $product_id, '_stock', true );
        }
        if ( trim( $stock_status ) == 'instock' && trim( $manage_stock ) == 'no' && '' == $pro_qty ) {
            $pro_qty = 1;
        }
    }

    if ( ! empty( $pro_data['_ced_etsy_tags'] )) {
        $explode_tags = explode( ',', $pro_data['_ced_etsy_tags'] );
        if ( is_array( $explode_tags ) ) {
         $tags = $this->ced_etsy_tags( $explode_tags );
     }
 }
 if ( empty( $tags ) ) {
    $current_tag = get_the_terms( $product_id, 'product_tag' );
    if ( is_array( $current_tag ) ) {
        $tags = $this->ced_etsy_tags( $current_tag );
    }
}

if ( ! empty( $pro_data['materials'] ) ) {
    $materials = array( str_replace( ' ', ',', $materials ) );
}

$category_id               = $this->fetch_meta_value( $product_id, '_umb_etsy_category' );
$selected_shipping_profile = get_option( 'ced_etsy_shipping_profiles_'. $shop_name, array() );
$shipping_profile_id       = array_search( $category_id, $selected_shipping_profile );

if ( empty( $shipping_profile_id ) ) {
    $error['msg'] = 'Shipping profile is not selected';
    return $error;
}
// $shipping_profile_id = 172257960844;

$arguements = array(
    'title'                => trim( ucwords( strtolower( strtoupper( $productTitle ) ) ) ),
    'description'          => strip_tags( $product_description ),
    'shipping_profile_id'  => doubleval( $shipping_profile_id ),
    'shop_section_id'      => (int)!empty( $pro_data['shop_section'] ) ? $pro_data['shop_section'] : '',
    'taxonomy_id'          => (int) $category_id,
    'who_made'             => !empty ( $pro_data['who_made'] ) ? $pro_data['who_made'] : 'i_did',
    'is_supply'            => !empty( $pro_data['product_supply'] ) ? $prod_data['product_supply'] : ( 'true' == $pro_data['product_supply'] ? 1 : 0 ),
    'when_made'            => !empty( $pro_data['when_made'] ) ? $pro_data['when_made'] : 'made_to_order',
    'quantity'             => (int) $pro_qty,
    'price'                => (float) $pro_price,
    'non_taxable'          => 0,
    'state'                => !empty( $pro_data['product_list_type'] ) ? $pro_data['product_list_type'] : 'draft',
    'processing_min'       => !empty( $pro_data['processing_min'] ) ? $pro_data['processing_min'] : 1,
    'processing_max'       => !empty( $pro_data['processing_max'] ) ? $pro_data['processing_max'] : 3,
    'materials'            => !empty( $materials ) ? (int) $materials : array(),
    'tags'                 => !empty( $tags ) ? (int) $tags : array(),
    'recipient'            => !empty( $pro_data['recipient'] ) ? (int) $pro_data['recipient'] : '',
    'occasion'             => !empty( $pro_data['occasion'] ) ? (int) $pro_data['occasion'] : '',
    'listing_type'         => 'physical',
    'language'             => 'en',
    'image_ids'            => array( 3842639609, 3849518812 ),
);
return $arguements;
}

    /**
     * *****************************************
     * GET VARIATION DATA TO UPDATE ON ETSY
     * *****************************************
     *
     * @since 1.0.0
     *
     * @param string $product_id Product lsting  ids.
     * @param string $shop_name Product  ids.
     * @param string $is_sync Active shopName.
     *
     * @link  http://www.cedcommerce.com/
     * @return $reponse
     */

    public function ced_variation_details( $product_id = '', $shop_name = '', $is_sync = false ) {
        $property_ids = array( 513, 514 );
        $product      = wc_get_product( $product_id );
        $variations   = $product->get_available_variations();
        $attributes   = array();
        foreach ($variations as $variation ) {
            
            $attribute_one_mapped = false;
            $attribute_two_mapped = false;
            $var_product          = wc_get_product( $variation['variation_id'] );
            $attributes           = $var_product->get_variation_attributes();
            $count                = 0;
            $property_values      = array();
            $offerings            = array();

            // Attributes as it's slug and attribute values.
            foreach ($attributes as $property_name => $property_value ) {
                $property_id = 513;
                if ( $count > 0) {
                    $property_id = 514;
                }
                $property_values[] = array(
                    'property_id'   => (int) $property_id,
                    'value_ids'     => array( $property_id ),
                    'property_name' => ucwords( str_replace( array( 'attribute_pa_', 'attribute_' ), array( '', '' ), $property_name ) ),
                    'values'        => array( ucwords( strtolower( $property_value ) ) ),

                );
                $count++;
            }


            $price        = $variation['display_price'];
            $manage_stock = get_post_meta( $variation['variation_id'], '_manage_stock', true );
            $stock_status = get_post_meta( $variation['variation_id'], '_stock_status', true );
            $var_quantity = get_post_meta( $variation['variation_id'], '_ced_etsy_stock', true );
            $pr_pr_l      = get_post_meta( $variation['variation_id'], '_ced_etsy_price', true );
            $m_ty_pr_l    = get_post_meta( $variation['variation_id'], '_ced_etsy_markup_type', true );
            $mrkp_v_p_l   = get_post_meta( $variation['variation_id'], '_ced_etsy_markup_value', true );
            $m_t_p_l      = $this->fetch_meta_value( $product_id, '_ced_etsy_markup_type' );
            $m_val_p_l    = $this->fetch_meta_value( $product_id, '_ced_etsy_markup_value' );
            $pr_pf_l      = $this->fetch_meta_value( $product_id, '_ced_etsy_price' );

            // Price
            if ( ! empty( $pr_pr_l ) ) {
                $price = (float) $pr_pr_l;
                if ( 'Percentage_Increased' == $m_ty_pr_l ) {
                    $price = $price + ( ( (float) $mrkp_v_p_l / 100 ) * $price );
                } else {
                    $price = $price + (float) $mrkp_v_p_l;
                }
            } else {
                $price = $pr_pf_l;
                if ( empty( $price ) ) {
                    $price = trim( $variation['display_price'] );
                }
                $price = (float) $price;
                if ( 'Percentage_Increased' == $m_t_p_l ) {
                    $price = $price + ( ( (float) $m_val_p_l / 100 ) * $price );
                } else {
                    $price = $price + (float) $m_val_p_l;
                }
            }

            if ( '' == $var_quantity ) {
                $var_quantity = $this->fetch_meta_value( $product_id, '_ced_etsy_stock' );
            }
            if ( '' == $var_quantity ) {
                $var_quantity = get_post_meta( $variation['variation_id'], '_stock', true );
            }

            if ( trim( $stock_status ) == 'instock' && trim( $manage_stock ) == 'no' && '' == $var_quantity ) {
                $var_quantity = $this->fetch_meta_value( $product_id, '_ced_etsy_default_stock' );
                if ( '' == $var_quantity ) {
                    $var_quantity = 1;
                }
            }

            if ( $var_quantity < 1 ) {
                $var_quantity = 0;
            }

            $var_sku = $variation['sku'];
            if ( empty( $var_sku ) || strlen( $var_sku ) > 32 || $parent_sku == $var_sku ) {
                $var_sku = (string) $variation['variation_id'];
            }

            $offerings = array(
                array(
                    'price'      => (float) $price,
                    'quantity'   => (int) $var_quantity,
                    'is_enabled' => 1,
                ),
            );
            $variation_info = array(
                'sku'             => $var_sku,
                'property_values' => $property_values,
                'offerings'       => $offerings,
            );
            $offer_info[]   = $variation_info;
            $property_ids[] = $property_id;
        }

        $property_ids = array_unique( $property_ids );
        $property_ids = implode( ',', $property_ids );
        $payload = array(
            'products'            => $offer_info,
            'price_on_property'   => $property_ids,
            'quantity_on_property'=> $property_ids,
            'sku_on_property'     => $property_ids,
        );
        return $payload;
    }


    /**
     * *************************************************************************************************************
     * This function fetches meta value of a product in accordance with profile assigned and meta value available.
     * *************************************************************************************************************
     *
     * @since 1.0.0
     *
     * @param int    $product_id Product  ids.
     * @param string $metaKey meta key name .
     * @param bool   $is_variation variation or not.
     *
     * @link  http://www.cedcommerce.com/
     * @return $meta data
     */

    private function fetch_meta_value( $product_id, $metaKey, $is_variation = false ) {

        if ( isset( $this->is_profile_assing ) && $this->is_profile_assing ) {

            $_product = wc_get_product( $product_id );
            if ( ! is_object( $_product ) ) {
                return false;
            }
            if ( WC()->version < '3.0.0' ) {
                if ( 'variation' == $_product->product_type ) {
                    $parentId = $_product->parent->id;
                } else {
                    $parentId = '0';
                }
            } else {
                if ( 'variation' == $_product->get_type() ) {
                    $parentId = $_product->get_parent_id();
                } else {
                    $parentId = '0';
                }
            }

            if ( ! empty( $this->profile_data ) && isset( $this->profile_data[ $metaKey ] ) ) {
                $profileData     = $this->profile_data[ $metaKey ];
                $tempProfileData = $this->profile_data[ $metaKey ];
                if ( isset( $tempProfileData['default'] ) && ! empty( $tempProfileData['default'] ) && ! empty( $tempProfileData['default'] ) && ! is_null( $tempProfileData['default'] ) ) {
                    $value = $tempProfileData['default'];
                } elseif ( isset( $tempProfileData['metakey'] ) ) {
                    if ( strpos( $tempProfileData['metakey'], 'umb_pattr_' ) !== false ) {

                        $wooAttribute = explode( 'umb_pattr_', $tempProfileData['metakey'] );
                        $wooAttribute = end( $wooAttribute );

                        if ( WC()->version < '3.0.0' ) {
                            if ( 'variation' == $_product->product_type ) {
                                $attributes = $_product->get_variation_attributes();
                                if ( isset( $attributes[ 'attribute_pa_' . $wooAttribute ] ) && ! empty( $attributes[ 'attribute_pa_' . $wooAttribute ] ) ) {
                                    $wooAttributeValue = $attributes[ 'attribute_pa_' . $wooAttribute ];
                                    if ( '0' != $parentId ) {
                                        $product_terms = get_the_terms( $parentId, 'pa_' . $wooAttribute );
                                    } else {
                                        $product_terms = get_the_terms( $product_id, 'pa_' . $wooAttribute );
                                    }
                                } else {
                                    $wooAttributeValue = $_product->get_attribute( 'pa_' . $wooAttribute );
                                    $wooAttributeValue = explode( ',', $wooAttributeValue );
                                    $wooAttributeValue = $wooAttributeValue[0];

                                    if ( '0' != $parentId ) {
                                        $product_terms = get_the_terms( $parentId, 'pa_' . $wooAttribute );
                                    } else {
                                        $product_terms = get_the_terms( $product_id, 'pa_' . $wooAttribute );
                                    }
                                }

                                if ( is_array( $product_terms ) && ! empty( $product_terms ) ) {
                                    foreach ( $product_terms as $tempkey => $tempvalue ) {
                                        if ( $tempvalue->slug == $wooAttributeValue ) {
                                            $wooAttributeValue = $tempvalue->name;
                                            break;
                                        }
                                    }
                                    if ( isset( $wooAttributeValue ) && ! empty( $wooAttributeValue ) ) {
                                        $value = $wooAttributeValue;
                                    } else {
                                        $value = get_post_meta( $product_id, $metaKey, true );
                                    }
                                } else {
                                    $value = get_post_meta( $product_id, $metaKey, true );
                                }
                            } else {
                                $wooAttributeValue = $_product->get_attribute( 'pa_' . $wooAttribute );
                                $product_terms     = get_the_terms( $product_id, 'pa_' . $wooAttribute );
                                if ( is_array( $product_terms ) && ! empty( $product_terms ) ) {
                                    foreach ( $product_terms as $tempkey => $tempvalue ) {
                                        if ( $tempvalue->slug == $wooAttributeValue ) {
                                            $wooAttributeValue = $tempvalue->name;
                                            break;
                                        }
                                    }
                                    if ( isset( $wooAttributeValue ) && ! empty( $wooAttributeValue ) ) {
                                        $value = $wooAttributeValue;
                                    } else {
                                        $value = get_post_meta( $product_id, $metaKey, true );
                                    }
                                } else {
                                    $value = get_post_meta( $product_id, $metaKey, true );
                                }
                            }
                        } else {
                            if ( 'variation' == $_product->get_type() ) {

                                $attributes = $_product->get_variation_attributes();
                                if ( isset( $attributes[ 'attribute_pa_' . $wooAttribute ] ) && ! empty( $attributes[ 'attribute_pa_' . $wooAttribute ] ) ) {

                                    $wooAttributeValue = $attributes[ 'attribute_pa_' . $wooAttribute ];
                                    if ( '0' != $parentId ) {
                                        $product_terms = get_the_terms( $parentId, 'pa_' . $wooAttribute );
                                    } else {
                                        $product_terms = get_the_terms( $product_id, 'pa_' . $wooAttribute );
                                    }
                                } elseif ( isset( $attributes[ 'attribute_' . $wooAttribute ] ) && ! empty( $attributes[ 'attribute_' . $wooAttribute ] ) ) {

                                    $wooAttributeValue = $attributes[ 'attribute_' . $wooAttribute ];

                                    if ( '0' != $parentId ) {
                                        $product_terms = get_the_terms( $parentId, 'pa_' . $wooAttribute );
                                    } else {
                                        $product_terms = get_the_terms( $product_id, 'pa_' . $wooAttribute );
                                    }
                                } else {

                                    $wooAttributeValue = $_product->get_attribute( 'pa_' . $wooAttribute );

                                    $wooAttributeValue = explode( ',', $wooAttributeValue );
                                    $wooAttributeValue = $wooAttributeValue[0];

                                    if ( '0' != $parentId ) {
                                        $product_terms = get_the_terms( $parentId, 'pa_' . $wooAttribute );
                                    } else {
                                        $product_terms = get_the_terms( $product_id, 'pa_' . $wooAttribute );
                                    }
                                }

                                if ( is_array( $product_terms ) && ! empty( $product_terms ) ) {
                                    foreach ( $product_terms as $tempkey => $tempvalue ) {
                                        if ( $tempvalue->slug == $wooAttributeValue ) {
                                            $wooAttributeValue = $tempvalue->name;
                                            break;
                                        }
                                    }
                                    if ( isset( $wooAttributeValue ) && ! empty( $wooAttributeValue ) ) {
                                        $value = $wooAttributeValue;
                                    } else {
                                        $value = get_post_meta( $product_id, $metaKey, true );
                                    }
                                } elseif ( isset( $wooAttributeValue ) && ! empty( $wooAttributeValue ) ) {
                                    $value = $wooAttributeValue;
                                } else {
                                    $value = get_post_meta( $product_id, $metaKey, true );
                                }
                            } else {
                                $wooAttributeValue = $_product->get_attribute( 'pa_' . $wooAttribute );
                                $product_terms     = get_the_terms( $product_id, 'pa_' . $wooAttribute );
                                if ( is_array( $product_terms ) && ! empty( $product_terms ) ) {
                                    foreach ( $product_terms as $tempkey => $tempvalue ) {
                                        if ( $tempvalue->slug == $wooAttributeValue ) {
                                            $wooAttributeValue = $tempvalue->name;
                                            break;
                                        }
                                    }
                                    if ( isset( $wooAttributeValue ) && ! empty( $wooAttributeValue ) ) {
                                        $value = $wooAttributeValue;
                                    } else {
                                        $value = get_post_meta( $product_id, $metaKey, true );
                                    }
                                } else {
                                    $value = get_post_meta( $product_id, $metaKey, true );
                                }
                            }
                        }
                    } else {

                        $value = get_post_meta( $product_id, $tempProfileData['metakey'], true );
                        if ( '_thumbnail_id' == $tempProfileData['metakey'] ) {
                            $value = wp_get_attachment_image_url( get_post_meta( $product_id, '_thumbnail_id', true ), 'thumbnail' ) ? wp_get_attachment_image_url( get_post_meta( $product_id, '_thumbnail_id', true ), 'thumbnail' ) : '';
                        }
                        if ( ! isset( $value ) || empty( $value ) || '' == $value || is_null( $value ) || '0' == $value || 'null' == $value ) {
                            if ( '0' != $parentId ) {

                                $value = get_post_meta( $parentId, $tempProfileData['metakey'], true );
                                if ( '_thumbnail_id' == $tempProfileData['metakey'] ) {
                                    $value = wp_get_attachment_image_url( get_post_meta( $parentId, '_thumbnail_id', true ), 'thumbnail' ) ? wp_get_attachment_image_url( get_post_meta( $parentId, '_thumbnail_id', true ), 'thumbnail' ) : '';
                                }

                                if ( ! isset( $value ) || empty( $value ) || '' == $value || is_null( $value ) ) {
                                    $value = get_post_meta( $product_id, $metaKey, true );

                                }
                            } else {
                                $value = get_post_meta( $product_id, $metaKey, true );
                            }
                        }
                    }
                } else {
                    $value = get_post_meta( $product_id, $metaKey, true );
                }
            } else {
                $value = get_post_meta( $product_id, $metaKey, true );
            }
        } else {
            $value = get_post_meta( $product_id, $metaKey, true );
        }

        return $value;
    }

}
