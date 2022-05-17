<?php
namespace Cedcommerce\Product\ProductUpload;
if ( ! class_exists( 'Ced_Product_Upload' ) ) {
	class Ced_Product_Upload {

		/**
		 * The ID of this plugin.
		 *
		 * @since    1.0.0
		 * @var      string    $_instance    The ID of this plugin.
		 */
		public static $_instance;
		/**
		 * Saved data at the global settings.
		 *
		 * @since    2.0.8
		 * @var      string    $renderDataOnGlobalSettings    variable to hold all saved data.
		 */
		private $renderDataOnGlobalSettings;
		/**
		 * The saved cedEtsy Data.
		 *
		 * @since    2.0.8
		 * @var      string    $saved_etsy_details    All saved data.
		 */
		private $saved_etsy_details;
		/**
		 * Hold the Woocommerce product. 
		 *
		 * @since    2.0.8
		 * @var      string    $ced_product    Wocommerce product. 
		 */
		public  $ced_product;
		/**
		 * The listing ID of uploaded product.
		 *
		 * @since    1.0.0
		 * @var      string    $l_id    The listing ID of the product.
		 */
		private $l_id;
		/**
		 * Active Etsy shopName.
		 *
		 * @since    1.0.0
		 * @var      string    $shop_name  Etsy shopName.
		 */
		public $shop_name;
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

		public function __construct( $shop_name = '' ) {
			$this->shop_name                  = $shop_name;
			$this->renderDataOnGlobalSettings = get_option( 'ced_etsy_global_settings', array() );
			$this->saved_etsy_details         = get_option( 'ced_etsy_details', array() );
		}


		/**
		 * ********************************************
		 * Function for products data to be uploaded.
		 * ********************************************
		 *
		 * @since 1.0.0
		 *
		 * @param array  $prodIDs Checked Product ids
		 * @param string $shopName Active Shop Name
		 */

		public function prepareDataForUploading( $proIDs = array(), $shop_name = '' ) {
			if ( ! is_array( $proIDs ) ) {
				$proIDs = array( $proIDs );
			}
			if ( is_array( $proIDs ) && ! empty( $proIDs ) ) {
				$shop_name = trim( $shop_name );
				self::prepareItems( $proIDs, $shop_name );
				$response = $this->uploadResponse;
				return $response;

			}
		}

		/**
		 * *****************************************************
		 * Function for preparing product data to be uploaded.
		 * *****************************************************
		 *
		 * @since 1.0.0
		 *
		 * @param array  $prodIDs Checked Product ids
		 * @param string $shopName Active Shop Name
		 *
		 * @return Uploaded Ids
		 */
		private function prepareItems( $proIDs = array(), $shop_name = '' ) {
			if ( '' == $shop_name || empty( $shop_name ) ) {
				return;
			}

			foreach ( $proIDs as $key => $pr_id ) {

				/**
				 * ********************************************
				 *  Get Post meta check if alreay got uploaded.
				 * ********************************************
				 * 
				 * @since 2.0.8
				 */
				
				$listing_id = get_post_meta( $pr_id, '_ced_etsy_listing_id_' . $shop_name, true );
				if ( $listing_id ) {
					continue;
				}

				$this->ced_product = wc_get_product( absint( $pr_id ) );
				$pro_type          = $this->ced_product->get_type();
				$alreadyUploaded   = false;
				if ( 'variable' == $pro_type ) {
					$attributes = $this->ced_product->get_variation_attributes();
					if ( count( $attributes ) > 2 ) {
						$error                = array();
						$error['msg']         = 'Varition attributes cannot be more than 2 . Etsy accepts variations using two attributes only.';
						$this->uploadResponse = $error;
						return $this->uploadResponse;
					}
					$preparedData = $this->ced_etsy_get_formatted_data( $pr_id, $shop_name );
					if (isset($preparedData['msg'])) {
						$this->uploadResponse = $preparedData['msg'];
						continue;
					}
					if ( 'Profile Not Assigned' == $preparedData || 'Quantity Cannot Be 0' == $preparedData ) {
						$error                = array();
						$error['msg']         = $preparedData;
						$this->uploadResponse = $error;
						return $this->uploadResponse;
					}
					$this->data = $preparedData;
					self::doupload( $pr_id, $shop_name );
					$response = $this->uploadResponse;
					if ( isset( $response['listing_id'] ) ) {
						$this->l_id = isset( $response['listing_id'] ) ? $response['listing_id'] : '';
						update_post_meta( $pr_id, '_ced_etsy_listing_id_' . $shop_name, $this->l_id );
						update_post_meta( $pr_id, '_ced_etsy_url_' . $shop_name, $response['url'] );
						// $this->ced_update_uploaded_images( $pr_id, $shop_name );
						$var_response = $this->update_variation_sku_to_etsy( $this->l_id, $pr_id, $shop_name );
						if ( ! isset( $var_response['results'] ) ) {
							$this->prepareDataForDelete( array( $pr_id ), $shop_name );
							foreach ( $var_response as $key => $v_id ) {
								$error                = array();
								$error['msg']         = isset( $key ) ? ucwords( str_replace( '_', ' ', $key ) ) : '';
								$this->uploadResponse = $error;
								return $this->uploadResponse;

							}
						}
					}
				} elseif ( 'simple' == $pro_type ) {
					$this->data = $this->ced_etsy_get_formatted_data( $pr_id, $shop_name );
					if (isset($preparedData['msg'])) {
						$this->uploadResponse = $preparedData['msg'];
						continue;
					}
					if ( 'Profile Not Assigned' == $preparedData || 'Quantity Cannot Be 0' == $preparedData ) {
						$error                = array();
						$error['msg']         = $preparedData;
						$this->uploadResponse = $error;
						return $this->uploadResponse;
					}
					self::doupload( $pr_id, $shop_name );
					$response = $this->uploadResponse;
					if ( isset( $response['listing_id'] ) ) {
						update_post_meta( $pr_id, '_ced_etsy_listing_id_' . $shop_name, $response['listing_id'] );
						update_post_meta( $pr_id, '_ced_etsy_url_' . $shop_name, $response['url'] );
						// $this->update_sku_to_etsy( $this->l_id, $pr_id, $shop_name );
						// $this->ced_etsy_upload_attributes( $this->l_id, $pr_id, $shop_name );
						// $this->ced_update_uploaded_images( $pr_id, $shop_name );
					}
				}
			}
			return $this->uploadResponse;
		}

		/**
		 * *************************
		 * Update uploaded images.
		 * *************************
		 *
		 * @since 2.0.8
		 *
		 * @param array  $p_id Checked Product ids
		 * @param string $shopName Active Shop Name
		 *
		 * @return
		 */

		private function ced_update_uploaded_images( $p_id = '', $shop_name = '' ) {
			if ( empty( $p_id ) || empty( $shop_name ) ) {
				return;
			}
			$prnt_img_id = get_post_thumbnail_id( $p_id );
			if ( WC()->version < '3.0.0' ) {
				$attachment_ids = $this->ced_product->get_gallery_attachment_ids();
			} else {
				$attachment_ids = $this->ced_product->get_gallery_image_ids();
			}
			$previous_thum_ids = get_post_meta( $p_id, 'ced_etsy_previous_thumb_ids' . $this->l_id, true );
			if ( empty( $previous_thum_ids ) || ! is_array( $previous_thum_ids ) ) {
				$previous_thum_ids = array();
			}
			$attachment_ids = array_slice( $attachment_ids, 0,9 );
			if ( ! empty( $attachment_ids ) ) {
				foreach ( array_reverse( $attachment_ids ) as $attachment_id ) {
					if ( isset( $previous_thum_ids[ $attachment_id ] ) ) {
						continue;
					}
					$image_result = self::doImageUpload( $this->l_id, $p_id, $attachment_id, $shop_name );
					if ( isset( $image_result['results'][0]['listing_image_id'] ) ) {
						$previous_thum_ids[ $attachment_id ] = $image_result['results'][0]['listing_image_id'];
						update_post_meta( $p_id, 'ced_etsy_previous_thumb_ids' . $this->l_id, $previous_thum_ids );
					}
				}
			}
			if ( ! isset( $previous_thum_ids[ $prnt_img_id ] ) ) {
				$image_result = self::doImageUpload( $this->l_id, $p_id, $prnt_img_id, $shop_name );
				if ( isset( $image_result['results'][0]['listing_image_id'] ) ) {
					$previous_thum_ids[ $prnt_img_id ] = $image_result['results'][0]['listing_image_id'];
					update_post_meta( $p_id, 'ced_etsy_previous_thumb_ids' . $this->l_id, $previous_thum_ids );
				}
			}
		}

		/**
		 * *************************
		 * Prepare file to be upload.
		 * *************************
		 *
		 * @since 2.0.5
		 *
		 * @param array  $p_id Checked Product ids
		 * @param string $shopName Active Shop Name
		 * @param string $listingID Listing ID from Etsy. 
		 *
		 * @return
		 */

		public function prepare_files( $product_id, $shop_name, $listingID ) {
			$downloadable_data = $this->downloadable_data;
			if ( ! empty( $downloadable_data ) ) {
				$count = 0;
				foreach ( $downloadable_data as $data ) {
					if ( $count > 4 ) {
						break;
					}
					$file_data = $data->get_data();
					$this->upload_files( $product_id, $shop_name, $listingID, $file_data, $count );
				}
			}
		}

		/**
		 * *************************
		 * Update uploaded images.
		 * *************************
		 *
		 * @since 2.0.5
		 *
		 * @param array  $p_id Checked Product ids
		 * @param string $shopName Active Shop Name
		 * @param integer $listingID Listing ID of the Product. 
		 * @param array $file_data All files data which is going to be upload. 
		 * @param integer $count number of counts. 
		 *
		 * @return
		 */
		public function upload_files( $product_id, $shop_name, $listingID, $file_data, $count ) {

			$listing_files_uploaded = get_post_meta( $product_id, '_ced_etsy_product_files_uploaded' . $listingID, true );
			if ( empty( $listing_files_uploaded ) ) {
				$listing_files_uploaded = array();
			}
			if ( isset( $listing_files_uploaded[ $file_data['id'] ] ) ) {
				return;
			}
			$params                  = array(
				'listing_id' => (int) $listingID,
				'file'       => $file_data['file'],
				'name'       => (string) $file_data['name'],
				'rank'       => (int) $count + 1,
			);
			$saved_etsy_details      = get_option( 'ced_etsy_details', array() );
			$saved_shop_etsy_details = $saved_etsy_details[ $shop_name ];
			$ced_etsy_keystring      = isset( $saved_shop_etsy_details['details']['ced_etsy_keystring'] ) ? esc_attr( $saved_shop_etsy_details['details']['ced_etsy_keystring'] ) : '';
			$ced_etsy_shared_string  = isset( $saved_shop_etsy_details['details']['ced_etsy_shared_string'] ) ? esc_attr( $saved_shop_etsy_details['details']['ced_etsy_shared_string'] ) : '';

			try {
				$args = array(
					'params' => array(
						'listing_id' => $listingID,
					),
					'data'   => $params,
				);

				$file_type   = explode( '.', $file_data['file'] );
				$file_type   = ! empty( end( $file_type ) ) ? end( $file_type ) : 'jpeg';
				$requestBody = array(
					'action'                 => 'uploadListingFile',
					'ced_etsy_keystring'     => $ced_etsy_keystring,
					'ced_etsy_shared_string' => $ced_etsy_shared_string,
					'saved_etsy_details'     => json_encode( $saved_etsy_details ),
					'data'                   => json_encode( $args ),
					'active_shop'            => $shop_name,
					'file_type'              => $file_type,
					'file_url'               => $file_data['file'],
				);
				$result      = $this->ced_etsy_post_request( $requestBody );
				$result      = !is_array( $result ) ? json_decode( $result, true ) : $result;
				if ( isset( $result['results'] ) ) {
					$listing_files_uploaded[ $file_data['id'] ] = $result['results'][0]['listing_file_id'];
					update_post_meta( $product_id, '_ced_etsy_product_files_uploaded' . $listingID, $listing_files_uploaded );
				}
			} catch ( Exception $e ) {
				$this->error_msg .= 'Message: ' . $product_id . '--' . $e->getMessage();
			}

		}

		/**
		 * ************************************
		 * UPLOAD IMAGED ON THE ETSY SHOP ;)
		 * ************************************
		 *
		 * @since 1.0.0
		 *
		 * @param int    $listingID Product listing ids.
		 * @param int    $product_id Product ids .
		 * @param int    $image_id Image Ids.
		 * @param string $active_shop Active Shop Name
		 *
		 * @return Nothing [Message]
		 */

		private function doImageUpload( $listingID, $product_id, $image_id, $active_shop ) {

			$saved_shop_etsy_details = $this->saved_etsy_details[ $active_shop ];
			$ced_etsy_keystring      = isset( $saved_shop_etsy_details['details']['ced_etsy_keystring'] ) ? esc_attr( $saved_shop_etsy_details['details']['ced_etsy_keystring'] ) : '';
			$ced_etsy_shared_string  = isset( $saved_shop_etsy_details['details']['ced_etsy_shared_string'] ) ? esc_attr( $saved_shop_etsy_details['details']['ced_etsy_shared_string'] ) : '';
			$image_path              = get_attached_file( $image_id );
			$image_url               = wp_get_attachment_url( $image_id );
			$image_data              = file_get_contents( $image_url );
			$image_data              = base64_encode( $image_data );
			try {
				$args = array(
					'params' => array(
						'listing_id' => $listingID,
					),
					'data'   => array(
						'image' => array( '@' . $image_path . ';type=image/jpeg' ),
					),
				);

				$requestBody = array(
					'action'                 => 'uploadListingImage',
					'ced_etsy_keystring'     => $ced_etsy_keystring,
					'ced_etsy_shared_string' => $ced_etsy_shared_string,
					'saved_etsy_details'     => json_encode( $this->saved_etsy_details ),
					'data'                   => json_encode( $args ),
					'image_url'              => $image_url,
					'active_shop'            => $active_shop,
				);
				$result      = $this->ced_etsy_post_request( $requestBody );
				$result      = !is_array( $result ) ? json_decode( $result, true ) : $result;
				return $result;
			} catch ( Exception $e ) {
				$this->error_msg .= 'Message: ' . $product_id . '--' . $e->getMessage();
			}

		}

		/**
		 * ************************************
		 * UPLOAD IMAGED ON THE ETSY SHOP ;)
		 * ************************************
		 *
		 * @since 1.0.0
		 *
		 * @param array $product_ids All  product ID. 
		 * @param array $shop_name Active shop name of Etsy. 
		 *
		 * @return Array. 
		 */

		public function update_images_on_etsy( $product_ids = array(), $shop_name = '' ) {

			if ( ! is_array( $product_ids ) ) {
				$product_ids = array( $product_ids );
			}
			
			if ( is_array( $product_ids ) && ! empty( $product_ids ) ) {
				foreach ( $product_ids as $key => $value ) {

					$listing_id = get_post_meta( $value, '_ced_etsy_listing_id_' . $shop_name, true );
					$image_id   = get_post_thumbnail_id( $value );
					$product    = wc_get_product( $value );
					if ( empty( $listing_id ) ) {
						return;
					}

					$client = ced_etsy_getOauthClientObject( $shop_name );
					$params = array();
					// Get all the listing Images form Etsy
					$success            = $client->CallAPI( "https://openapi.etsy.com/v2/listings/{$listing_id}/images", 'GET', $params, array( 'FailOnAccessError' => true ), $all_listing_images );
					$all_listing_images = json_decode( json_encode( $all_listing_images, true ), true );
					// All images arreay form Etsy
					$etsy_images = isset( $all_listing_images['results'] ) ? $all_listing_images['results'] : '';
					if ( ! empty( $etsy_images ) ) {
						// This is last Image which have to delete
						$etsy_images = array_shift( $etsy_images );
					}

					$main_image_id = isset( $etsy_images['listing_image_id'] ) ? $etsy_images['listing_image_id'] : '';
					if ( ! empty( $main_image_id ) ) {
						$success                = $client->CallAPI( "https://openapi.etsy.com/v2/listings/{$listing_id}/images/{$main_image_id}", 'DELETE', $params, array( 'FailOnAccessError' => true ), $deleted_image_result );
						$deleted_image_result   = json_decode( json_encode( $deleted_image_result, true ), true );
						$deleted_img_listing_id = isset( $deleted_image_result['params']['listing_image_id'] ) ? $deleted_image_result['params']['listing_image_id'] : '';
					}

					if ( empty( $deleted_image_result ) ) {
						return false;
					}
					$previous_thum_ids = get_post_meta( $value, 'ced_etsy_previous_thumb_ids' . $listing_id, true );
					if ( empty( $previous_thum_ids ) || ! is_array( $previous_thum_ids ) ) {
						$previous_thum_ids = array();
					}

					$image_id_to_unset = array_search( $deleted_img_listing_id, $previous_thum_ids );
					if ( $image_id_to_unset ) {
						unset( $previous_thum_ids[ $image_id_to_unset ] );
						update_post_meta( $value, 'ced_etsy_previous_thumb_ids' . $listing_id, $previous_thum_ids );
					}

					$attachment_ids = $product->get_gallery_image_ids();
					if ( ! empty( $attachment_ids ) ) {
						foreach ( array_reverse( $attachment_ids ) as $attachment_id ) {
							if ( isset( $previous_thum_ids[ $attachment_id ] ) && ! empty( $previous_thum_ids[ $attachment_id ] ) ) {
								continue;
							}
							$image_result = self::doImageUpload( $listing_id, $value, $attachment_id, $shop_name );
							if ( isset( $image_result['results'][0]['listing_image_id'] ) ) {
								$previous_thum_ids[ $attachment_id ] = $image_result['results'][0]['listing_image_id'];
								update_post_meta( $value, 'ced_etsy_previous_thumb_ids' . $listing_id, $previous_thum_ids );
							}
						}
					}

					if ( ! isset( $previous_thum_ids[ $image_id ] ) || empty( $previous_thum_ids[ $image_id ] ) ) {
						$image_result = self::doImageUpload( $listing_id, $value, $image_id, $shop_name );
						if ( isset( $image_result['results'][0]['listing_image_id'] ) ) {
							$previous_thum_ids[ $image_id ] = $image_result['results'][0]['listing_image_id'];
							update_post_meta( $value, 'ced_etsy_previous_thumb_ids' . $listing_id, $previous_thum_ids );
						}
					}
					return $value;
				}
			}
		}



		/**
		 * *************************************************************
		 * Function for preparing variation product data for preview
		 * *************************************************************
		 *
		 * @since 1.0.0
		 *
		 * @param array $prodIDs Product ids
		 *
		 * @return Product Varitions Ids
		 */

		public function getVaritionDataForPreview( $proIDs ) {

			$proVariations   = array();
			$product         = wc_get_product( $proIDs );
			$variations      = $product->get_available_variations();
			$attribute       = $product->get_variation_attributes();
			$attribule_array = array();
			foreach ( $attribute as $name => $value ) {
				$attribule_array['name']           = str_replace( 'pa_', '', $name );
				$attribule_array['options']        = array_values( $value );
				$proVariations['tier_variation'][] = $attribule_array;
			}
			foreach ( $variations as $index => $variation ) {
				$variationSku    = $variation['sku'];
				$variation_array = array();
				$tier_index      = array();
				foreach ( $variation['attributes'] as $attri_value ) {
					foreach ( $proVariations['tier_variation'] as $tier_key => $tier_value ) {
						foreach ( $tier_value['options'] as $k => $v ) {
							if ( $attri_value == $v ) {
								$tier_index[] = $k;
							}
						}
					}
				}
				$price                            = $variation['display_price'];
				$variation_array['tier_index']    = $tier_index;
				$variation_array['stock']         = $variation['max_qty'];
				$variation_array['price']         = $price;
				$variation_array['variation_sku'] = $variation['sku'];
				$proVariations['variation'][]     = $variation_array;
			}
			return $proVariations;
		}

		 /**
		  * *********************************
		  * Update product SKU To Etsy Shop
		  * *********************************
		  *
		  * @since 1.0.0
		  *
		  * @param array $listing_id Product lsting  ids.
		  * @param array $productid Product ids.
		  * @param array $active_shop Active shopName.
		  *
		  * @return Product Varitions Ids
		  */

		private function update_sku_to_etsy( $listing_id, $productid = '', $active_shop ) {
			$profileData                = $this->getProfileAssignedData( $productid, $active_shop );
			$client                     = ced_etsy_getOauthClientObject( $active_shop );
			$params                     = array( 'listing_id' => (int) $listing_id );
			$success                    = $client->CallAPI( "https://openapi.etsy.com/v2/listings/{$listing_id}/inventory", 'GET', $params, array( 'FailOnAccessError' => true ), $inventory_details );
			$inventory_details          = json_decode( json_encode( $inventory_details ), true );
			$product_json               = $inventory_details['results']['products'];
			$sku                        = get_post_meta( $productid, '_sku', true );
			$quantity                   = get_post_meta( $productid, '_ced_etsy_stock', true );
			$price_at_product_lvl       = get_post_meta( $productid, '_ced_etsy_price', true );
			$markuptype_at_product_lvl  = get_post_meta( $productid, '_ced_etsy_markup_type', true );
			$markupValue_at_product_lvl = get_post_meta( $productid, '_ced_etsy_markup_value', true );
			$markuptype_at_profile_lvl  = $this->fetchMetaValueOfProduct( $productid, '_ced_etsy_markup_type' );
			$markupValue_at_profile_lvl = $this->fetchMetaValueOfProduct( $productid, '_ced_etsy_markup_value' );
			$price_at_profile_lvl       = $this->fetchMetaValueOfProduct( $productid, '_ced_etsy_price' );

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
					$price = get_post_meta( $productid, '_price', true );
				}
				$price = (float) $price;
				if ( 'Percentage_Increased' == $markuptype_at_profile_lvl ) {
					$price = $price + ( ( (float) $markupValue_at_profile_lvl / 100 ) * $price );
				} else {
					$price = $price + (float) $markupValue_at_profile_lvl;
				}
			}

			if ( '' == $quantity ) {
				$quantity = $this->fetchMetaValueOfProduct( $productid, '_ced_etsy_stock' );

			}
			if ( '' == $quantity ) {
				$quantity = get_post_meta( $productid, '_stock', true );
			}

			$manage_stock = get_post_meta( $productid, '_manage_stock', true );
			$stock_status = get_post_meta( $productid, '_stock_status', true );

			if ( trim( $stock_status ) == 'instock' && trim( $manage_stock ) == 'no' && '' == $quantity ) {
				$quantity = 1;
			}

			if ( $quantity < 1 ) {
				$quantity = 0;
			}
			if ( empty( $sku ) ) {
				$sku = (string) $productid;
			}
			$product_json[0]['offerings'][0]['quantity'] = (int) $quantity;
			$product_json[0]['offerings'][0]['price']    = (float) $price;
			$product_json[0]['sku']                      = $sku;

			$params            = array( 'products' => json_encode( $product_json ) );
			$success           = $client->CallAPI( "https://openapi.etsy.com/v2/listings/$listing_id/inventory", 'PUT', $params, array( 'FailOnAccessError' => true ), $inventory_details );
			$inventory_details = json_decode( json_encode( $inventory_details ), true );
		}


		 /**
		  * ****************************************
		  * Upload product attribute to Etsy shop
		  * ****************************************
		  *
		  * @since 1.0.0
		  *
		  * @param int    $listing_id Product lsting  ids.
		  * @param int    $productId Product ids.
		  * @param string $shop_name Active shopName.
		  *
		  * @return Nothing[Updating only Uploaded attribute ids]
		  */

		private function ced_etsy_upload_attributes( $listing_id, $productId, $shop_name ) {
			if ( isset( $productId ) ) {
				if ( isset( $listing_id ) ) {
					$client = ced_etsy_getOauthClientObject( $shop_name );
					$this->getProfileAssignedData( $productId, $shop_name );
					$category_id = (int) $this->fetchMetaValueOfProduct( $productId, '_umb_etsy_category' );
					if ( isset( $category_id ) ) {
						$params                    = array( 'taxonomy_id' => $category_id );
						$success                   = $client->CallAPI( "https://openapi.etsy.com/v2/taxonomy/seller/{$category_id}/properties", 'GET', $params, array( 'FailOnAccessError' => true ), $getTaxonomyNodeProperties );
						$getTaxonomyNodeProperties = json_decode( json_encode( $getTaxonomyNodeProperties ), true );
						$getTaxonomyNodeProperties = $getTaxonomyNodeProperties['results'];
						if ( isset( $getTaxonomyNodeProperties ) && is_array( $getTaxonomyNodeProperties ) && ! empty( $getTaxonomyNodeProperties ) ) {
							$attribute_meta_data = get_post_meta( $productId, 'ced_etsy_attribute_data', true );
							foreach ( $getTaxonomyNodeProperties as $key => $value ) {
								$property = ! empty( $attribute_meta_data[ ( '_ced_etsy_property_id_' . $value['property_id'] ) ] ) ? $attribute_meta_data[ ( '_ced_etsy_property_id_' . $value['property_id'] ) ] : 0;
								if ( empty( $property ) ) {
									$property = $this->fetchMetaValueOfProduct( $productId, '_ced_etsy_property_id_' . $value['property_id'] );
								}
								foreach ( $value['possible_values'] as $tax_value ) {
									if ( $tax_value['name'] == $property ) {
										$property = $tax_value['value_id'];
										break;
									}
								}

								if ( isset( $property ) && ! empty( $property ) ) {
									$property_id[ $value['property_id'] ] = $property;
								}
							}
						}
						if ( isset( $property_id ) && ! empty( $property_id ) ) {
							foreach ( $property_id as $key => $value ) {

								$property_id_to_listing = (int) $key;
								$value_ids              = (int) $value;

								$params   = array(
									'listing_id'  => (int) $listing_id,
									'property_id' => (int) $property_id_to_listing,
									'value_ids'   => array( (int) $value_ids ),
									'values'      => array( (string) $value_ids ),
								);
								$success  = $client->CallAPI( "https://openapi.etsy.com/v2/listings/{$listing_id}/attributes/{$property_id_to_listing}", 'PUT', $params, array( 'FailOnAccessError' => true ), $response );
								$response = json_decode( json_encode( $response ), true );
							}
						}
						update_post_meta( $productId, 'ced_etsy_attribute_uploaded', 'true' );
					}
				}
			}
		}



		/**
		 * *********************************************
		 * PREPARE DATA FOR UPDATING DATA TO ETSY SHOP
		 * *********************************************
		 *
		 * @since 1.0.0
		 *
		 * @param array  $proIDs Product lsting  ids.
		 * @param string $shop_name Active shopName.
		 *
		 * @return Nothing[Updating only Uploaded attribute ids]
		 */

		public function prepareDataForUpdating( $proIDs = array(), $shop_name ) {
			if ( ! is_array( $proIDs ) ) {
				$proIDs = array( $proIDs );
			}

			foreach ( $proIDs as $key => $product_id ) {
				$listing_id  = get_post_meta( $product_id, '_ced_etsy_listing_id_' . $shop_name, true );
				$profileData = $this->getProfileAssignedData( $product_id, $shop_name );
				if ( 'false' == $profileData && ! $isPreview ) {
					return array( 'msg' => 'Profile Not Assigned' );
				}

				$this->is_downloadable   = false;
				$this->downloadable_data = array();
				$arguements = $this->ced_etsy_get_formatted_data( $product_id, $shop_name );
				// print_r( json_encode( $arguements ) );			
				$shop_id    = get_etsy_shop_id( $shop_name );
				$action     = "application/shops/{$shop_id}/listings/{$listing_id}";
				do_action( 'ced_etsy_refresh_token', $shop_name );
				$result     = etsy_request()->put( $action, $arguements, $shop_name );
				if (isset( $result['listing_id'] ) ) {
					$product    = wc_get_product( $product_id ); 
					$pro_type   = $product->get_type();
					if ( 'variable' === $pro_type ) {
						$result = $this->update_variation_sku_to_etsy( $result['listing_id'], $product_id, $shop_name );
					}
				}
				if ( isset( $result['exception'] ) ) {
					$error        = array();
					$error['msg'] = isset( $result['exception']['last_response'] ) ? $result['exception']['last_response'] : 'some error occured';
					return $error;
				} else {

					if ( $this->is_downloadable ) {
						$digital_response = $this->prepare_files( $product_id, $shop_name, $listing_id );
					}

					$this->ced_etsy_upload_attributes( $listing_id, $product_id, $shop_name );
				}
				return $result;
			}
			return $this->uploadResponse;
		}

		 /**
		  * *****************************************************
		  * PREPARING DATA FOR UPDATING INVENTORY TO ETSY SHOP
		  * *****************************************************
		  *
		  * @since 1.0.0
		  *
		  * @param array   $proIDs Product lsting  ids.
		  * @param string  $shop_name Active shopName.
		  * @param boolean $is_sync condition for is sync.
		  *
		  * @return $response ,
		  */

		public function prepareDataForUpdatingInventory( $proIDs = array(), $shop_name, $is_sync = false ) {
			if ( ! is_array( $proIDs ) ) {
				$proIDs = array( $proIDs );
			}

			if ( is_array( $proIDs ) && ! empty( $proIDs ) ) {
				foreach ( $proIDs as $key => $value ) {
					$listing_id = get_post_meta( $value, '_ced_etsy_listing_id_' . $shop_name, true );
					$product    = wc_get_product( $value );
					if ( ! is_object( $product ) || empty( $listing_id ) ) {
						continue;
					}
					$profileData = $this->getProfileAssignedData( $value, $shop_name );
					if ( $product->get_type() == 'variable' ) {
						$response = $this->update_variation_sku_to_etsy( $listing_id, $value, $shop_name, $is_sync );
					} else {

						$manage_stock      = get_post_meta( $value, '_manage_stock', true );
						$stock_status      = get_post_meta( $value, '_stock_status', true );
						$deactivated       = get_post_meta( $value, 'ced_etsy_deactivated_product' . $shop_name, true );
						$product           = wc_get_product( $value );
						$client            = ced_etsy_getOauthClientObject( $shop_name );
						$params            = array( 'listing_id' => (int) $listing_id );
						$success           = $client->CallAPI( "https://openapi.etsy.com/v2/listings/{$listing_id}/inventory", 'GET', $params, array( 'FailOnAccessError' => true ), $inventory_details );
						$inventory_details = json_decode( json_encode( $inventory_details ), true );

						$product_json = $inventory_details['results']['products'];

						$sku                        = get_post_meta( $value, '_sku', true );
						$quantity                   = get_post_meta( $value, '_ced_etsy_stock', true );
						$price_at_product_lvl       = get_post_meta( $value, '_ced_etsy_price', true );
						$markuptype_at_product_lvl  = get_post_meta( $value, '_ced_etsy_markup_type', true );
						$markupValue_at_product_lvl = get_post_meta( $value, '_ced_etsy_markup_value', true );
						$markuptype_at_profile_lvl  = $this->fetchMetaValueOfProduct( $value, '_ced_etsy_markup_type' );
						$markupValue_at_profile_lvl = $this->fetchMetaValueOfProduct( $value, '_ced_etsy_markup_value' );
						$price_at_profile_lvl       = $this->fetchMetaValueOfProduct( $value, '_ced_etsy_price' );

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
								$price = get_post_meta( $value, '_price', true );
							}
							$price = (float) $price;
							if ( 'Percentage_Increased' == $markuptype_at_profile_lvl ) {
								$price = $price + ( ( (float) $markupValue_at_profile_lvl / 100 ) * $price );
							} else {
								$price = $price + (float) $markupValue_at_profile_lvl;
							}
						}

						if ( '' == $quantity ) {
							$quantity = $this->fetchMetaValueOfProduct( $value, '_ced_etsy_stock' );

						}
						if ( '' == $quantity ) {
							$quantity = get_post_meta( $value, '_stock', true );
						}

						if ( trim( $stock_status ) == 'instock' && trim( $manage_stock ) == 'no' && '' == $quantity ) {
							$quantity = 1;
						}
						if ( $quantity > 0 ) {

							$active = $this->activate_products( array( $value ), $shop_name );
						}

						if ( isset( $quantity ) && $quantity < 1 ) {
							$response = $this->deactivate_products( array( $value ), $shop_name );
						}

						if ( empty( $sku ) ) {
							$sku = (string) $value;
						}

						/*UPDATE PRICE SKU AND INVENTORY OF UPLOADED PRODUCTS*/
						$product_json[0]['offerings'][0]['quantity'] = (int) $quantity;
						$product_json[0]['offerings'][0]['price']    = (float) $price;
						$product_json[0]['sku']                      = (string) $sku;
						/*SEND REQUESTS TO API FOR INVENTORY UPDATE*/

						$params   = array( 'products' => json_encode( $product_json ) );
						$success  = $client->CallAPI( "https://openapi.etsy.com/v2/listings/{$listing_id}/inventory", 'PUT', $params, array( 'FailOnAccessError' => true ), $inventory_details );
						$response = json_decode( json_encode( $inventory_details ), true );
						if ( isset( $response['results'] ) ) {
							update_post_meta( $value, 'ced_etsy_last_updated' . $shop_name, gmdate( 'l jS \of F Y h:i:s A' ) );
						}
					}
				}
				if ( ! $is_sync ) {
					return $response;
				}
			}

		}


		 /**
		  * ********************************************************
		  * MAKE PRODUCT FROM ACTIVE TO DEACTIVE ON ETSY FROM WOO
		  * ********************************************************
		  *
		  * @since 1.0.0
		  *
		  * @param array  $proIDs Product lsting  ids.
		  * @param string $shop_name Active shopName.
		  *
		  * @return $result ,
		  */


		public function deactivate_products( $proIDs = array(), $shop_name = '' ) {
			if ( ! is_array( $proIDs ) ) {
				$proIDs = array( $proIDs );
			}

			$message                 = '';
			$saved_shop_etsy_details = $this->saved_etsy_details[ $shop_name ];
			$ced_etsy_keystring      = isset( $saved_shop_etsy_details['details']['ced_etsy_keystring'] ) ? esc_attr( $saved_shop_etsy_details['details']['ced_etsy_keystring'] ) : '';
			$ced_etsy_shared_string  = isset( $saved_shop_etsy_details['details']['ced_etsy_shared_string'] ) ? esc_attr( $saved_shop_etsy_details['details']['ced_etsy_shared_string'] ) : '';
			foreach ( $proIDs as $key => $value ) {
				$listing_id = get_post_meta( $value, '_ced_etsy_listing_id_' . $shop_name, true );
				$data       = array( 'state' => 'inactive' );
				$args       = array(
					'params' => array( 'listing_id' => (int) $listing_id ),
					'data'   => $data,
				);

				$requestBody = array(
					'action'                 => 'updateListing',
					'ced_etsy_keystring'     => $ced_etsy_keystring,
					'ced_etsy_shared_string' => $ced_etsy_shared_string,
					'saved_etsy_details'     => json_encode( $this->saved_etsy_details ),
					'data'                   => json_encode( $args ),
					'active_shop'            => $shop_name,
				);
				$result      = $this->ced_etsy_post_request( $requestBody );
				$result      = !is_array( $result ) ? json_decode( $result, true ) : $result;
				return $result;
			}

		}

		 /**
		  * ********************************************************
		  * MAKE PRODUCT FROM DEACTIVE TO  ACTIVE ON ETSY FROM WOO
		  * ********************************************************
		  *
		  * @since 1.0.0
		  *
		  * @param array  $proIDs Product lsting  ids.
		  * @param string $shop_name Active shopName.
		  *
		  * @return $result ,
		  */

		private function activate_products( $proIDs = array(), $shop_name ) {

			$state = isset( $this->renderDataOnGlobalSettings[ $shop_name ]['product_data']['_ced_etsy_product_list_type'] ) ? $this->renderDataOnGlobalSettings[ $shop_name ]['product_data']['_ced_etsy_product_list_type'] : '';
			if ( 'active' != $state ) {
				return;
			}

			$message                 = '';
			$saved_shop_etsy_details = $this->saved_etsy_details[ $shop_name ];
			$ced_etsy_keystring      = isset( $saved_shop_etsy_details['details']['ced_etsy_keystring'] ) ? esc_attr( $saved_shop_etsy_details['details']['ced_etsy_keystring'] ) : '';
			$ced_etsy_shared_string  = isset( $saved_shop_etsy_details['details']['ced_etsy_shared_string'] ) ? esc_attr( $saved_shop_etsy_details['details']['ced_etsy_shared_string'] ) : '';
			foreach ( $proIDs as $key => $value ) {
				$listing_id  = get_post_meta( $value, '_ced_etsy_listing_id_' . $shop_name, true );
				$data        = array( 'state' => 'active' );
				$args        = array(
					'params' => array( 'listing_id' => (int) $listing_id ),
					'data'   => $data,
				);
				$requestBody = array(
					'action'                 => 'updateListing',
					'ced_etsy_keystring'     => $ced_etsy_keystring,
					'ced_etsy_shared_string' => $ced_etsy_shared_string,
					'saved_etsy_details'     => json_encode( $this->saved_etsy_details ),
					'data'                   => json_encode( $args ),
					'active_shop'            => $shop_name,
				);
				$result      = $this->ced_etsy_post_request( $requestBody );
				$result      = !is_array( $result ) ? json_decode( $result, true ) : $result;
				return $result;
			}

		}

		 /**
		  * *******************************
		  * DELETE THE LISTINGS FORM ETSY
		  * *******************************
		  *
		  * @since 1.0.0
		  *
		  * @param array  $proIDs Product lsting  ids.
		  * @param string $shop_name Active shopName.
		  *
		  * @return $reponse deleted product ids ,
		  */

		public function prepareDataForDelete( $proIDs = array(), $shop_name ) {
			if ( ! is_array( $proIDs ) ) {
				$proIDs = array( $proIDs );
			}
			$message = array();
			foreach ( $proIDs as $key => $value ) {
				$listing_id = get_post_meta( $value, '_ced_etsy_listing_id_' . $shop_name, true );
				do_action( 'ced_etsy_refresh_token', $shop_name );
				$action        = "application/listings/{$listing_id}";
				$response      =  etsy_request()->delete( $action, $shop_name );
				if ( !isset( $response['error'] ) ) {
					delete_post_meta( $value, '_ced_etsy_listing_id_' . $shop_name );
					delete_post_meta( $value, '_ced_etsy_url_' . $shop_name );
				}
				return $response;
			}
		}

		 /**
		  * *****************************************
		  * GET FORMATTED DATA FOR UPLOAD PRODUCTS
		  * *****************************************
		  *
		  * @since 1.0.0
		  *
		  * @param array  $proIDs Product lsting  ids.
		  * @param string $shop_name Active shopName.
		  * @param bool   $isPreview boolean.
		  *
		  * @return $arguments all possible arguments .
		  */

		private function ced_etsy_get_formatted_data( $product_id = array(), $shop_name = '' ) {
			$profileData = $this->getProfileAssignedData( $product_id, $shop_name );
			if ( 'false' == $profileData && ! $isPreview ) {
				return 'Profile Not Assigned';
			}

			$this->is_downloadable   = false;
			$this->downloadable_data = array();

			$product            = wc_get_product( $product_id );
			$productData        = $product->get_data();
			$product_type       = $product->get_type();

			$this->is_downloadable = isset( $productData['downloadable'] ) ? $productData['downloadable'] : 0;
			if ( $this->is_downloadable ) {
				$this->downloadable_data = $productData['downloads'];
			}

			$etsy_data_field = $this->renderDataOnGlobalSettings[$shop_name]['product_data'];
			$pro_data        = array();
			foreach ( $etsy_data_field as $meta_key => $value ) {
				// Prouct Edit Level.
				$pro_val = get_post_meta( $product_id, $meta_key, true );
				// Profile level.
				if ( empty( $pro_val ) ) {
					$pro_val = $this->fetchMetaValueOfProduct( $product_id, $meta_key );
				}
				// Global Settings.
				if ( empty( $pro_val ) ) {
					$pro_val = $value['default'];
				}

				$pro_data[str_replace( '_ced_etsy_', ' ', $meta_key)] = $pro_val;
			}

			$productTitle         = !empty( $pro_data['title'] ) ? $pro_data['title'] : $productData['name'];
			$productPrefix        = !empty( $pro_data['title_pre'] ) ? $pro_data['title_pre'] : '';
			$productPostfix       = !empty( $pro_data['title_post'] ) ? $pro_data['title_post'] : '';
			$DesPrefix            = !empty( $pro_data['description_pre'] ) ? $pro_data['description_pre'] : '';
			$DesPostfix           = !empty( $pro_data['description_post'] ) ? $pro_data['description_post'] : '';
			$productTitle         = $productPrefix . ' ' . $productTitle . ' ' . $productPostfix;
			$productDescription   = !empty( $pro_data['description'] ) ? $pro_data['description'] : $DesPrefix . $productData['description'] . '</br>' . $productData['short_description'] . $DesPostfix;
			$materials            = !empty( $pro_data['materials'] ) ? $pro_data['materials'] : '';
			$tags                 = !empty( $pro_data['_ced_etsy_tags'] ) ? $pro_data['tags'] : '';
			$who_made             = !empty( $pro_data['who_made'] ) ? $pro_data['who_made'] : 'i_did';
			$recipient            = !empty( $pro_data['recipient'] ) ? $pro_data['recipient'] : '';
			$occasion             = !empty( $pro_data['occasion'] ) ? $pro_data['occasion'] : '';
			$when_made            = !empty( $pro_data['when_made'] ) ? $pro_data['when_made'] : '2020_2021';
			$listing_type    = !empty( $pro_data['product_list_type'] ) ? $pro_data['product_list_type'] : 'draft';
			$shop_section_id      = !empty( $pro_data['shop_section'] ) ? $pro_data['shop_section'] : '';
			$is_supply            = !empty( $pro_data['product_supply'] ) ? $pro_data['product_supply'] : 0;
			$processing_min       = !empty( $pro_data['processing_min'] ) ? $pro_data['processing_min'] : 1;
			$processing_max       = !empty( $pro_data['processing_max'] ) ? $pro_data['processing_max'] : 3;
			$shipping_t_id   = !empty( $pro_data['shipping_profile'] ) ? $pro_data['shipping_profile'] : '';
			$pro_price         = get_post_meta( $product_id, '_ced_etsy_price', true );

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
					$varId           = $variations['0']['variation_id'];
					$pro_qty = 1;
				}
			} else {
				$manage_stock    = get_post_meta( $product_id, '_manage_stock', true );
				$stock_status    = get_post_meta( $product_id, '_stock_status', true );
				$pro_qty = get_post_meta( $product_id, '_ced_etsy_stock', true );

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

			if ( ! empty( $tags ) ) {
				$explode_tags = explode( ',', $tags );
				if ( is_array( $explode_tags ) ) {
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
				}
			}

			if ( empty( $tags ) ) {
				$current_tag = get_the_terms( $product_id, 'product_tag' );
				if ( is_array( $current_tag ) ) {
					$tags = array();
					foreach ( $current_tag as $key_tags => $tag ) {
						$tag_name = str_replace( ' ', '-', $tag->name );
						$tag_name = preg_replace( '/[^A-Za-z0-9\-]/', '', $tag_name );
						$tag_name = str_replace( '-', ' ', $tag_name );
						if ( $key_tags <= 12 && strlen( $tag_name ) <= 20 ) {
							$tags[ $key_tags ] = $tag_name;
						}
					}
					$tags = implode( ',', array_filter( array_values( array_unique( $tags ) ) ) );
				}
			}

			if ( ! empty( $tags ) ) {
				$tags = array( $tags );
			}

			if ( ! empty( $materials ) ) {
				$materials = array( str_replace( ' ', ',', $materials ) );
			}

			if ( isset( $is_supply ) && ! empty( $is_supply ) && 'true' == $is_supply ) {
				$is_supply = 1;
			}
			if ( isset( $is_supply ) && ! empty( $is_supply ) && 'false' == $is_supply ) {
				$is_supply = 0;
			}

			$shipping_t_id = 172257960844;
			if (empty($shipping_t_id)) {
				$error['msg'] = 'Shipping profile is not selected';
				return $error;
			}

			$category_id = $this->fetchMetaValueOfProduct( $product_id, '_umb_etsy_category' );
			$arguements = array(
				'title'                => trim( ucwords( strtolower( strtoupper( $productTitle ) ) ) ),
				'description'          => strip_tags( $productDescription ),
				'shipping_profile_id'  => doubleval( 171887176577 ),
				'shop_section_id'      => (int) 37380807,
				'taxonomy_id'          => (int) $category_id,
				'who_made'             => ! empty( $who_made ) ? $who_made : 'i_did',
				'is_supply'            => (bool) $is_supply,
				'when_made'            => ! empty( $when_made ) ? $when_made : '2020_2021',
				'quantity'             => (int) $pro_qty,
				'price'                => (float) $pro_price,
				'non_taxable'          => 0,
				'state'                => $listing_type,
				'processing_min'       => ! empty( $processing_min ) ? (int) $processing_min : 1,
				'processing_max'       => ! empty( $processing_max ) ? (int) $processing_max : 3,
				'materials'            => ! empty( $materials ) ? (int) $materials : '',
				'tags'                 => ! empty( $tags ) ? (int) $tags : '',
				'recipient'            => ! empty( $recipient ) ? (int) $recipient : '',
				'occasion'             => ! empty( $occasion ) ? (int) $occasion : '',
				'listing_type'         => 'physical',
				'language'             => 'en',
				'image_ids'            => array( 3842639609, 3849518812 ),
			);
			return $arguements;
		}

		/**
		 * *****************************************
		 * GET ASSIGNED PRODUCT DATA FROM PROFILES
		 * *****************************************
		 *
		 * @since 1.0.0
		 *
		 * @param array  $proId Product lsting  ids.
		 * @param string $shopId Active shopName.
		 *
		 * @link  http://www.cedcommerce.com/
		 * @return $profile_data assigined profile data .
		 */

		private function getProfileAssignedData( $proId, $shopId ) {

			$data = wc_get_product( $proId );
			$type = $data->get_type();
			if ( 'variation' == $type ) {
				$proId = $data->get_parent_id();
			}

			global $wpdb;
			$productData = wc_get_product( $proId );
			$product     = $productData->get_data();
			$category_id = isset( $product['category_ids'] ) ? $product['category_ids'] : array();
			$profile_id  = get_post_meta( $proId, 'ced_etsy_profile_assigned' . $shopId, true );
			if ( ! empty( $profile_id ) ) {
				$profile_id = $profile_id;
			} else {
				foreach ( $category_id as $key => $value ) {
					$profile_id = get_term_meta( $value, 'ced_etsy_profile_id_' . $shopId, true );

					if ( ! empty( $profile_id ) ) {
						break;

					}
				}
			}

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
		 * *****************************************
		 * UPDATE VARIATION SKU TO ETSY SHOP
		 * *****************************************
		 *
		 * @since 1.0.0
		 *
		 * @param array  $listing_id Product lsting  ids.
		 * @param array  $productId Product  ids.
		 * @param string $shopId Active shopName.
		 *
		 * @link  http://www.cedcommerce.com/
		 * @return $reponse
		 */

		private function update_variation_sku_to_etsy( $listing_id, $productId, $shop_name, $is_sync = false ) {
			// $client                                = ced_etsy_getOauthClientObject( $shop_name );
			// $taxonomy_id                           = (int) $this->fetchMetaValueOfProduct( $productId, '_umb_etsy_category' );
			// $shop_id                               = get_etsy_shop_id( $shop_name );
			// do_action( 'ced_etsy_refresh_token', $shop_name );
			// $action                                = "application/shops/{$shop_id}/listings/{$listing_id}/properties";
			// echo "URL :-"."application/shops/{$shop_id}/listings/{$listing_id}/properties";
			// $listing_properties                    = etsy_request()->get( $action, $shop_name );
			// echo "<pre>";
			// print_r( $listing_properties );
			// $params                                = array( 'taxonomy_id' => $taxonomy_id );
			// $success                               = $client->CallAPI( "https://openapi.etsy.com/v2/taxonomy/seller/{$taxonomy_id}/properties", 'GET', $params, array( 'FailOnAccessError' => true ), $variation_category_attribute );
			// return;

			// var_dump( "application/listings/{$listing_id}/properties/{$taxonomy_id}" );
			// $action = "application/listings/{$listing_id}/inventory";
			// // https://openapi.etsy.com/v3/application/listings/{listing_id}/
			// do_action('ced_etsy_refresh_token', $shop_name );
			// $properties = etsy_request()->get( $action, $shop_name );
			// echo "<pre>";
			// print_r( $properties );
			// die('Something');

			// $variation_category_attribute          = json_decode( json_encode( $variation_category_attribute ), true );
			// $variation_category_attribute_property = $variation_category_attribute['results'];

			// Update Listing Propertiese.

			// $shop_id = get_etsy_shop_id( $shop_name );
			// $action = "application/shops/{$shop_id}/listings/{$listing_id}/properties/514";
			// $params = array(
			// 	'property_id' => '514',
			// 	'property_name' => 'Check Variation',

			// );

			// ini_set('display_errors','1');
			// ini_set('display_startup_errors','1');
			// error_reporting( E_ALL );



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
			$_product                              = wc_get_product( $productId );
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
			$parent_sku  = get_post_meta( $productId, '_sku', true );
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
						$profileDataForvariation = $this->getProfileAssignedData( $productId, $shop_name );
						$variation_key_value     = $this->fetchMetaValueOfProduct( $product_id, '_ced_etsy_variation_property_id_' . $variation_category_attribute_property_value['property_id'], true );
						$ReplacedAttributes      = get_option( 'ced_etsy_replaced_attributes', array() );
						if ( isset( $ReplacedAttributes[ $variation_key_value ] ) && ! empty( $ReplacedAttributes[ $variation_key_value ] ) ) {
							$variation_key_value = $ReplacedAttributes[ $variation_key_value ];
						}

						$property_name = $variation_category_attribute_property_value['name'];
						$property_id   = $variation_category_attribute_property_value['property_id'];
						// echo "Poperty ID :-{$property_id} <br>";
						// continue;
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

			// echo "<pre>";
			// print_r( json_encode( $final_attribute_variation_final ) );

			// $params = array(
			// 	'products'             => array( 'products' => $final_attribute_variation_final ),
			// 	'price_on_property'    => array( $setPropertyIds ),
			// 	'quantity_on_property' => array( $setPropertyIds ),
			// 	'sku_on_property'      => array( $setPropertyIds ),
			// 	'listing_id'           => $listing_id,
			// );
			// print_r( json_encode( array('products'=> $final_attribute_variation_final) ) );
			// Update Liting Offerings.
			// echo "URL :-". $action . "<br>";
			// die($action);
			// print_r( json_encode( array( 'products'=> $final_attribute_variation_final ) ) );
			do_action( 'ced_etsy_refresh_token', $shop_name );
			$response = etsy_request()->put( "application/listings/{$listing_id}/inventory", array( 'products'=> $final_attribute_variation_final ), $shop_name );

			// echo "<pre> Response :---------------";
			// print_r( $response );
			// die('Updateing Invenvotory');

			// if ( ! isset( $response['results'] ) ) {

			// 	$saved_etsy_details      = get_option( 'ced_etsy_details', array() );
			// 	$saved_shop_etsy_details = $saved_etsy_details[ $shop_name ];
			// 	$ced_etsy_keystring      = isset( $saved_shop_etsy_details['details']['ced_etsy_keystring'] ) ? esc_attr( $saved_shop_etsy_details['details']['ced_etsy_keystring'] ) : '';
			// 	$ced_etsy_shared_string  = isset( $saved_shop_etsy_details['details']['ced_etsy_shared_string'] ) ? esc_attr( $saved_shop_etsy_details['details']['ced_etsy_shared_string'] ) : '';
			// 	$requestBody             = array(
			// 		'action'                 => 'updateInventory',
			// 		'ced_etsy_keystring'     => $ced_etsy_keystring,
			// 		'ced_etsy_shared_string' => $ced_etsy_shared_string,
			// 		'saved_etsy_details'     => json_encode( $saved_etsy_details ),
			// 		'listing_id'             => $listing_id,
			// 		'product'                => json_encode( $final_attribute_variation_final ),
			// 		'price_on_property'      => $setPropertyIds,
			// 		'quantity_on_property'   => $setPropertyIds,
			// 		'active_shop'            => $shop_name,
			// 	);
			// 	$result                  = $this->ced_etsy_post_request( $requestBody );
			// 	if ( !is_array( $result ) ) {
			// 		$result = json_decode( $result, true );
			// 	}
			// 	if ( isset( $result['exception'] ) || ( isset( $result['status'] ) && 201 == $result['status'] ) ) {
			// 		$response     = array();
			// 		$response_msg = isset( $result['message']['lastResponse'] ) ? $result['message']['lastResponse'] : '';
			// 		if ( empty( $response_msg ) ) {
			// 			$response_msg = isset( $result['exception']['last_response'] ) ? $result['exception']['last_response'] : '';
			// 		}
			// 		if ( empty( $response_msg ) ) {
			// 			$response_msg = isset( $result['message'] ) ? $result['message'] : 'some_error_occured';
			// 		}
			// 		$response[ $response_msg ] = '';
			// 	}
			// }
			if ( isset( $response['listing_id'] ) ) {
				update_post_meta( $productId, 'ced_etsy_last_updated' . $shop_name, gmdate( 'l jS \of F Y h:i:s A' ) );
			}
			if ( ! $is_sync ) {
				return $response;
			}

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


		/**
		 * ****************************************************
		 * UPLOADING THE VARIABLE AND SIMPLE PROUCT TO ETSY
		 * ****************************************************
		 *
		 * @since 1.0.0
		 *
		 * @param int    $product_id Product  ids.
		 * @param string $shop_name Active shop Name.
		 *
		 * @link  http://www.cedcommerce.com/
		 * @return Uploaded product Ids.
		 */

		private function doupload( $product_id, $shop_name ) {
			do_action( 'ced_etsy_refresh_token', $shop_name );
			$shop_id  = get_etsy_shop_id( $shop_name );
			$response = etsy_request()->post( "application/shops/{$shop_id}/listings", $this->data, $shop_name );
			/**
			 * ************************************************
			 *  Update post meta after uploading the Products. 
			 * ************************************************
			 * 
			 * @since 2.0.8
			 */
			if ( isset( $response['listing_id'] ) ) {
				update_post_meta( $product_id, '_ced_etsy_listing_id_' . $shop_name, $response['listing_id'] );
				update_post_meta( $product_id, '_ced_etsy_url_' . $shop_name, $response['url'] );
			}

			if ( isset( $response['error'] ) ) {
				$error                = array();
				$error['msg']         = isset( $response['error'] ) ? $response['error'] : 'some error occured';
				$this->uploadResponse = $error;
			} else {
				$this->uploadResponse = $response;
			}
		}


		/**
		 * ****************************
		 *  Curl Request to Cedcommerce
		 * ****************************
		 *
		 * @since 2.0.8
		 *
		 * @param array()  $request Requesting to cedcommerce server for post request over Etsy.
		 *
		 * @link  http://www.cedcommerce.com/
		 * @return array. 
		 */

		private function ced_etsy_post_request( $requestBody = '' ) {
			if ('' == $requestBody || empty( $requestBody) || null == $requestBody ) {
				return;
			}
			$response = wp_remote_post( 'http://3.129.122.174/demo/ced-manage-etsy.php',
						array(
							'method'      => 'POST',
							'timeout'     => 5,
							'redirection' => 5,
							'headers'     => array(),
							'body'        => $requestBody,
							'sslverify'   => false,
							'data_format' => 'body',
						)
			);
			// Response body.
			$body         = wp_remote_retrieve_body( $response );
			$responceData = ( ! is_wp_error( $response ) ) ? json_decode( $body, true ) : null;
			return $responceData;
		}

	}
}
