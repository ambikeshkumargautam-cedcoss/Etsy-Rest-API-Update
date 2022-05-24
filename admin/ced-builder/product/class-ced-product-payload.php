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
    public $ced_global_settings;
    public $isProfileAssignedToProduct;
    public $profile_data;
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
        return 'simple';
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
            $this->isProfileAssignedToProduct = true;
            $profile_data                     = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_etsy_profiles WHERE `id`=%s ", $profile_id ), 'ARRAY_A' );
            if ( is_array( $profile_data ) ) {
                $profile_data = isset( $profile_data[0] ) ? $profile_data[0] : $profile_data;
                $profile_data = isset( $profile_data['profile_data'] ) ? json_decode( $profile_data['profile_data'], true ) : array();
            }
        } else {
            $this->isProfileAssignedToProduct = false;
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
        // print_r( $this->ced_global_settings['product_data'] );
        $etsy_data_field    = isset( $this->ced_global_settings['product_data'] ) ? $this->ced_global_settings['product_data'] : $this->ced_global_settings[$shop_name]['product_data'];
        $pro_data = array();
        foreach ( $etsy_data_field as $meta_key => $value ) {
            $pro_val = get_post_meta( $product_id, $meta_key, true );
            if ( empty( $pro_val ) ) {
                $pro_val = $this->fetchMetaValueOfProduct( $product_id, $meta_key );
            }
            if ( empty( $pro_val ) ) {
                $pro_val = $value['default'];
            }
            $pro_data[str_replace( '_ced_etsy_', ' ', $meta_key)] = !empty( $pro_val ) ? $pro_val : '';
        }

        $productTitle         = !empty( $pro_data['title'] ) ? $pro_data['title'] : $productData['name'];
        $productTitle         = $pro_data['title_pre'] . ' ' . $productTitle . ' ' . $pro_data['title_post'];
        $productDescription   = !empty( $pro_data['description'] ) ? $pro_data['description'] : $pro_data['description_pre'] . $productData['description'] . '</br>' . $pro_data['description_post'];
        $pro_price            = get_post_meta( $product_id, '_ced_etsy_price', true );
        $price_at_profile_lvl = $this->fetchMetaValueOfProduct( $product_id, '_ced_etsy_price' );

        // Price
        if ( ! empty( $pro_price ) ) {
            $markuptype_at_product_lvl = get_post_meta( $product_id, '_ced_etsy_markup_type', true );
            $markupValue               = (float) get_post_meta( $product_id, '_ced_etsy_markup_value', true );
            if ( 'Percentage_Increased' == $markuptype_at_product_lvl ) {
                $pro_price = (float) $pro_price + ( ( (float) $markupValue / 100 ) * (float) $pro_price );
            } else {
                $pro_price = (float) $pro_price + (float) $markupValue;
            }
        } else {
            if ( empty( $price_at_profile_lvl ) ) {
                $price_at_profile_lvl = (float) $productData['price'];
            }
            $markuptype_at_profile_lvl = $this->fetchMetaValueOfProduct( $product_id, '_ced_etsy_markup_type' );
            $markupValue               = (float) $this->fetchMetaValueOfProduct( $product_id, '_ced_etsy_markup_value' );
            if ( 'Percentage_Increased' == $markuptype_at_profile_lvl ) {
                $pro_price = (float) $price_at_profile_lvl + ( ( (float) $markupValue / 100 ) * (float) $price_at_profile_lvl );
            } else {
                $pro_price = (float) $price_at_profile_lvl + (float) $markupValue;
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
                $pro_qty = $this->fetchMetaValueOfProduct( $product_id, '_ced_etsy_stock' );
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

        $pro_data['shipping_profile'] = 172257960844;
        if (empty($pro_data['shipping_profile'] )) {
            $error['msg'] = 'Shipping profile is not selected';
            return $error;
        }

        $category_id = $this->fetchMetaValueOfProduct( $product_id, '_umb_etsy_category' );
        $arguements = array(
            'title'                => trim( ucwords( strtolower( strtoupper( $productTitle ) ) ) ),
            'description'          => strip_tags( $productDescription ),
            'shipping_profile_id'  => doubleval( 171887176577 ),
            'shop_section_id'      => (int) 37380807 /*!empty( $pro_data['shop_section'] ) ? $pro_data['shop_section'] : ''*/,
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
        $variation_category_attribute_property = array(
           array(
                'property_id' => '513',
                'name' => 'Custom1',
                'display_name' => 'Custom Property',
                'is_required' => 0,
                'supports_attributes' => 0,
                'supports_variations' => 1,
                'is_multivalued' => 0,
                'scales' => array(),
                'possible_values' => array(),
                'selected_values' => array(),

            ),
            array(
                    'property_id' => '514',
                    'name' => 'Custom1',
                    'display_name' => 'Custom Property',
                    'is_required' => 0,
                    'supports_attributes' => 0,
                    'supports_variations' => 1,
                    'is_multivalued' => 0,
                    'scales' => array(),
                    'possible_values' => array(),
                    'selected_values' => array(),

            ),
        );
        $_product                   = wc_get_product( $product_id );
        $variations                 = $_product->get_available_variations();
        $variationProductAttributes = $_product->get_variation_attributes();
        $extra_price                = $variations['0']['display_regular_price'];
        $setPropertyIds             = array();
        $possible_combinations      = array();
        $attributes                 = wc_list_pluck( $_product->get_attributes(), 'get_slugs' );
        if ( ! empty( $attributes ) ) {
            $possible_combinations = array_values( wc_array_cartesian( $attributes ) );
            $com_to_be_prepared    = array();
            foreach ( $possible_combinations as $po_attr => $po_values ) {
                $att_name_po = '';
                $po_values   = array_reverse( $po_values );

                foreach ( $po_values as $kk => $po_value ) {
                    if ( ! isset($variationProductAttributes[$kk]) ) {
                    continue;
                    }
                    $att_name_po .= $po_value . '~';
                }
                $com_to_be_prepared[ trim( strtolower( $att_name_po ) ) ] = trim( strtolower( $att_name_po ) );
            }
        }
        $parent_sku  = get_post_meta( $product_id, '_sku', true );
        $combo_count = count( $possible_combinations );
        $var_ids     = array();
        foreach ( $variations as $variation ) {
            $product_id          = $variation['variation_id'];
            $var_product         = wc_get_product( $product_id );
            $productAttributes   = $var_product->get_variation_attributes();
            $attribute_one       = '';
            $attribute_two       = '';
            $attribute_one_value = '';
            $attribute_two_value = '';
            $count               = 0;
            foreach ( $productAttributes as $name => $attr_values ) {
                $name         = str_replace( 'attribute_', '', $name );
                $term         = get_term_by( 'slug', $attr_values, $name );
                $product_term = $attr_values;
                if ( is_object( $term ) ) {
                    $product_term = $term->name;
                }
                if ( 0 == $count ) {
                    $attribute_one       = wc_attribute_label( $name );
                    $attribute_one_value = $product_term;
                } else {
                    $attribute_two       = wc_attribute_label( $name );
                    $attribute_two_value = $product_term;
                }
                ++$count;
            }

            $attribute_one_mapped = false;
            $attribute_two_mapped = false;

            $final_etsy_product_property_valuedem = array();
            $var_att_array                        = '';
            foreach ( $variation_category_attribute_property as $variation_category_attribute_property_key => $variation_category_attribute_property_value ) {
                if ( isset( $variation_category_attribute_property ) ) {
                    $variation_key_value     = $this->fetchMetaValueOfProduct( $product_id, '_ced_etsy_variation_property_id_' . $variation_category_attribute_property_value['property_id'], true );
                    $ReplacedAttributes      = get_option( 'ced_etsy_replaced_attributes', array() );
                    if ( isset( $ReplacedAttributes[ $variation_key_value ] ) && ! empty( $ReplacedAttributes[ $variation_key_value ] ) ) {
                        $variation_key_value = $ReplacedAttributes[ $variation_key_value ];
                    }

                    $property_name = $variation_category_attribute_property_value['name'];
                    $property_id   = $variation_category_attribute_property_value['property_id'];
                    if ( empty( $variation_key_value ) && count( $productAttributes ) > 1 && ( 513 == $property_id || '513' == $property_id ) && ! $attribute_one_mapped ) {
                        $property_name       = $attribute_one;
                        $variation_key_value = $attribute_one_value;
                    }

                    if ( empty( $variation_key_value ) && count( $productAttributes ) > 1 && ( 514 == $property_id || '514' == $property_id ) && ! $attribute_two_mapped ) {
                        $property_name       = $attribute_two;
                        $variation_key_value = $attribute_two_value;
                    }

                    if ( empty( $variation_key_value ) && count( $productAttributes ) == 1 && ( 514 == $property_id || '514' == $property_id ) && ! $attribute_one_mapped ) {
                        $property_name       = $attribute_one;
                        $variation_key_value = $attribute_one_value;
                    }
                    if ( isset( $variation_key_value ) && ! empty( $variation_key_value ) ) {
                        if ( ! $attribute_one_mapped ) {
                            $attribute_one_mapped = true;
                            $property_id_one      = $property_id;
                            $property_name_one    = ucwords( str_replace( array( 'attribute_pa_', 'attribute_' ), array( '', '' ), $property_name ) );
                        } else {
                            $attribute_two_mapped = true;
                            $property_id_two      = $property_id;
                            $property_name_two    = ucwords( str_replace( array( 'attribute_pa_', 'attribute_' ), array( '', '' ), $property_name ) );
                        }
                        $final_attribute_variation         = array();
                        $final_etsy_product_property_value = array();
                        $var_att_array                    .= $variation_key_value . '~';
                        $setPropertyIds[]                  = (int) $property_id;
                        $var_ids[]                         = isset( $variation['variation_id'] ) ? $variation['variation_id'] : ''; 
                        $final_etsy_product_property_value = array(
                            'property_id'   => (int) $variation_category_attribute_property_value['property_id'],
                            'value_ids'     => array( 513, 514 ),
                            'property_name' => ucwords( str_replace( array( 'attribute_pa_', 'attribute_' ), array( '', '' ), $property_name ) ),
                            'values'        => array( ucwords( strtolower( $variation_key_value ) ) ),
                        );

                        $price                      = $variation['display_price'];
                        $extra_price                = $variation['display_price'];
                        $manage_stock               = get_post_meta( $variation['variation_id'], '_manage_stock', true );
                        $stock_status               = get_post_meta( $variation['variation_id'], '_stock_status', true );
                        $pro_qty            = get_post_meta( $variation['variation_id'], '_ced_etsy_stock', true );
                        $price_at_product_lvl       = get_post_meta( $variation['variation_id'], '_ced_etsy_price', true );
                        $markuptype_at_product_lvl  = get_post_meta( $variation['variation_id'], '_ced_etsy_markup_type', true );
                        $markupValue_at_product_lvl = get_post_meta( $variation['variation_id'], '_ced_etsy_markup_value', true );
                        $markuptype_at_profile_lvl  = $this->fetchMetaValueOfProduct( $product_id, '_ced_etsy_markup_type' );
                        $markupValue_at_profile_lvl = $this->fetchMetaValueOfProduct( $product_id, '_ced_etsy_markup_value' );
                        $price_at_profile_lvl       = $this->fetchMetaValueOfProduct( $product_id, '_ced_etsy_price' );

                        // Price
                        if ( ! empty( $price_at_product_lvl ) ) {
                            $price = (float) $price_at_product_lvl;
                            if ( 'Percentage_Increased' == $markuptype_at_product_lvl ) {
                                $price = $price + ( ( (float) $markupValue_at_product_lvl / 100 ) * $price );
                            } else {
                                $price = $price + (float) $markupValue_at_product_lvl;
                            }
                        } else {
                            $price = $price_at_profile_lvl;
                            if ( empty( $price ) ) {
                                $price = trim( $variation['display_price'] );
                            }
                            $price = (float) $price;
                            if ( 'Percentage_Increased' == $markuptype_at_profile_lvl ) {
                                $price = $price + ( ( (float) $markupValue_at_profile_lvl / 100 ) * $price );
                            } else {
                                $price = $price + (float) $markupValue_at_profile_lvl;
                            }
                        }

                        if ( '' == $pro_qty ) {
                            $pro_qty = $this->fetchMetaValueOfProduct( $product_id, '_ced_etsy_stock' );
                        }
                        if ( '' == $pro_qty ) {
                            $pro_qty = get_post_meta( $variation['variation_id'], '_stock', true );
                        }

                        if ( trim( $stock_status ) == 'instock' && trim( $manage_stock ) == 'no' && '' == $pro_qty ) {
                            $pro_qty = 1;
                        }

                        if ( $pro_qty < 1 ) {
                            $pro_qty = 0;
                        }

                        $variation_max_qty                      = $pro_qty;
                        $final_etsy_product_property_valuedem[] = $final_etsy_product_property_value;
                        if ( $variation_max_qty <= 0 ) {
                            $product_enable    = 0;
                            $variation_max_qty = 0;
                        } else {
                            $product_enable = 1;
                        }

                        $final_etsy_product_offering = array(
                            array(
                                'price'      => (float) $price,
                                'quantity'   => (int) $variation_max_qty,
                                'is_enabled' => $product_enable,
                            ),
                        );

                        $var_sku = $variation['sku'];
                        if ( empty( $var_sku ) || strlen( $var_sku ) > 32 || $parent_sku == $var_sku ) {
                            $var_sku = (string) $variation['variation_id'];
                        }

                        $final_attribute_variation = array(
                            'sku'             => $var_sku,
                            'property_values' => $final_etsy_product_property_valuedem,
                            'offerings'       => $final_etsy_product_offering,
                        );
                    }
                }
            }

            if ( isset( $com_to_be_prepared[ strtolower( $var_att_array ) ] ) ) {
                unset( $com_to_be_prepared[ strtolower( $var_att_array ) ] );
            }

            $final_attribute_variation_final[] = isset( $final_attribute_variation ) ? $final_attribute_variation : '';
        }
        goto aftervariable;
        if ( count( $final_attribute_variation_final ) && ( count( $final_attribute_variation_final ) != $combo_count ) && count( $variationProductAttributes ) == 2 ) {
            $remaining_combination = $com_to_be_prepared;
            foreach ( $remaining_combination as $combination ) {
                $property_values_remaining = array_values( array_filter( explode( '~', $combination ) ) );
                if ( isset( $property_values_remaining[1] ) ) {
                    $final_attribute_variation_final[] = array(

                        'sku'             => '',
                        'property_values' => array(
                            array(
                                'property_id'   => (int) $property_id_one,
                                'value_ids'   => array( 513, 514 ),
                                'property_name' => $property_name_one,
                                'values'        => array(
                                    isset( $property_values_remaining[0] ) ? ucwords( strtolower( $property_values_remaining[0] ) ) : '',
                                ),
                            ),
                            array(
                                'property_id'   => (int) $property_id_two,
                                'value_ids'   => array(),
                                'property_name' => $property_name_two,
                                'values'        => array(
                                    isset( $property_values_remaining[1] ) ? ucwords( strtolower( $property_values_remaining[1] ) ) : '',
                                ),
                            ),
                        ),
                        'offerings'       => array(
                            array(
                                'price'      => (float) $extra_price,
                                'quantity'   => 0,
                                'is_enabled' => 0,
                            ),
                        ),

                    );
                } elseif ( isset( $property_values_remaining[0] ) ) {
                    $final_attribute_variation_final[] = array(

                        'sku'             => '',
                        'property_values' => array(
                            array(
                                'value_ids'   => array( 513, 514 ),
                                'property_id'   => (int) $property_id_one,
                                'property_name' => $property_name_one,
                                'values'        => array(
                                    isset( $property_values_remaining[0] ) ? ucwords( strtolower( $property_values_remaining[0] ) ) : '',
                                ),
                            ),

                        ),
                        'offerings'       => array(
                            array(
                                'price'      => (float) $extra_price,
                                'quantity'   => 0,
                                'is_enabled' => 0,
                            ),
                        ),

                    );
                }
            }
        }
        aftervariable:
        if ( isset( $setPropertyIds ) && is_array( $setPropertyIds ) && ! empty( $setPropertyIds ) ) {
            $setPropertyIds = array_unique( $setPropertyIds );
            $setPropertyIds = implode( ',', $setPropertyIds );
        }
        return $final_attribute_variation_final;
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

    private function fetchMetaValueOfProduct( $product_id, $metaKey, $is_variation = false ) {

        if ( isset( $this->isProfileAssignedToProduct ) && $this->isProfileAssignedToProduct ) {

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
