<?php
namespace Cedcommerce\Product;
use Cedcommerce\EtsyManager\Ced_Etsy_Request as Etsy_Request;
if ( ! class_exists( 'Ced_Product_Upload' ) ) {
	class Ced_Product_Upload extends Etsy_Request {

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
		 * @var      string    $global_settings    variable to hold all saved data.
		 */
		private $global_settings;
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
			$this->shop_name          = $shop_name;
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

		public function prepare_for_upload( $pro_ids = array(), $shop_name = '' ) {
			if ( ! is_array( $pro_ids ) ) {
				$pro_ids = array( $pro_ids );
			}
			if ( is_array( $pro_ids ) && ! empty( $pro_ids ) ) {
				$shop_name = trim( $shop_name );
				self::prepare_items( $pro_ids, $shop_name );
				$response = $this->upload_response;
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
		private function prepare_items( $pro_ids = array(), $shop_name = '' ) {
			if ( '' == $shop_name || empty( $shop_name ) ) {
				return;
			}

			foreach ( $pro_ids as $key => $pr_id ) {

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
				$delete_instance   = new \Cedcommerce\Product\Ced_Product_Delete();
				$payload           = new \Cedcommerce\Product\Ced_Product_Payload( $shop_name, $pr_id );
				if ( 'variable' == $pro_type ) {

					$attributes = $this->ced_product->get_variation_attributes();
					if ( count( $attributes ) > 2 ) {
						$error                = array();
						$error['error']       = 'Varition attributes cannot be more than 2 . Etsy accepts variations using two attributes only.';
						$this->upload_response = $error;
						return $this->upload_response;
					}
					$this->data = $payload->ced_etsy_get_formatted_data( $pr_id, $shop_name );
					self::doupload( $pr_id, $shop_name );
					$response = $this->upload_response;
					if ( isset( $response['listing_id'] ) ) {
						$this->l_id = isset( $response['listing_id'] ) ? $response['listing_id'] : '';
						update_post_meta( $pr_id, '_ced_etsy_listing_id_' . $shop_name, $this->l_id );
						update_post_meta( $pr_id, '_ced_etsy_url_' . $shop_name, $response['url'] );
						$offerings_payload = $payload->ced_variation_details( $pr_id, $shop_name );
						$var_response      = $this->update_variation_sku_to_etsy( $pr_id, $this->l_id, $shop_name, $offerings_payload, false );
						if ( ! isset( $var_response['listing_id'] ) ) {
							$delete_instance->ced_etsy_delete_product( array( $pr_id ), $shop_name );
							$this->upload_response = isset( $var_response ) ? $var_response : 'Some error occured!';
						}
					}
				} elseif ( 'simple' == $pro_type ) {
					$this->data = $payload->ced_etsy_get_formatted_data( $pr_id, $shop_name );
					if (isset($this->data['msg'])) {
						$this->upload_response = $this->data['msg'];
						continue;
					}
					if ( 'Profile Not Assigned' == $this->data || 'Quantity Cannot Be 0' == $this->data ) {
						$error                = array();
						$error['msg']         = $this->data;
						$this->upload_response = $error;
						return $this->upload_response;
					}
					self::doupload( $pr_id, $shop_name );
					$response = $this->upload_response;
					if ( isset( $response['listing_id'] ) ) {
						update_post_meta( $pr_id, '_ced_etsy_listing_id_' . $shop_name, $response['listing_id'] );
						update_post_meta( $pr_id, '_ced_etsy_url_' . $shop_name, $response['url'] );
						$this->l_id = $response['listing_id'];
						/**
						 * ********************************************
						 *  Upload Product Images to Etsy.
						 * ********************************************
						 * 
						 * @since 2.0.8
						 */
						$this->ced_etsy_prep_and_upload_img( $pr_id, $shop_name );
						/**
						 * ***********************
						 * 	UPLOAD DIGITAL FILES
						 * ***********************
						 */
						if ( $payload->is_downloadable ) {
							$this->ced_upload_downloadable( $pr_id, $shop_name, $response['listing_id'], $payload->downloadable_data );
						}
					}
				}
			}
			return $this->upload_response;
		}

		/**
		 * ***************************
		 * Upload downloadable files
		 * ***************************
		 *
		 * @since 2.0.8
		 *
		 * @param array  $p_id Checked Product ids
		 * @param string $shopName Active Shop Name
		 *
		 * @return
		 */
		private function ced_upload_downloadable( $p_id='', $shop_name = '', $l_id= '', $downloadable_data= array() ) {
			$listing_files_uploaded = get_post_meta( $p_id, '_ced_etsy_product_files_uploaded' . $l_id, true );
			if ( empty( $listing_files_uploaded ) ) {
				$listing_files_uploaded = array();
			}
			if ( ! empty( $downloadable_data ) ) {
				$count = 0;
				foreach ( $downloadable_data as $data ) {
					if ( $count > 4 ) {
						break;
					}
					$file_data = $data->get_data();
					if ( isset( $listing_files_uploaded[ $file_data['id'] ] ) ) {
						continue;
					}
					try {
						$file_path = str_replace( wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'],  $file_data['file'] );
    					do_action( 'ced_etsy_refresh_token', $shop_name );
    					$shop_id  = get_etsy_shop_id( $shop_name );
						$response = parent::ced_etsy_upload_image_and_file( 'file', "application/shops/{$shop_id}/listings/{$l_id}/files", $file_path, $file_data['name'], $shop_name );
						if ( isset( $response['listing_file_id'] ) ) {
							$listing_files_uploaded[ $file_data['id'] ] = $response['listing_file_id'];
							update_post_meta( $p_id, '_ced_etsy_product_files_uploaded' . $l_id, $listing_files_uploaded );
						}
					} catch ( Exception $e ) {
						$this->error_msg['msg'] = 'Message:' . $e->getMessage();
						return $this->error_msg;
					}
				}

			}
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
		public function ced_etsy_prep_and_upload_img( $p_id = '', $shop_name = '' ) {
			if ( empty( $p_id ) || empty( $shop_name ) ) {
				return;
			}
			$this->ced_product = isset( $this->ced_product ) ? $this->ced_product : wc_get_product( $p_id );
			$prnt_img_id       = get_post_thumbnail_id( $p_id );
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

					/*
					|=======================
					| UPLOAD GALLERY IMAGES
					|=======================
					*/
					$image_result = self::do_image_upload( $this->l_id, $p_id, $attachment_id, $shop_name );
					if ( isset( $image_result['listing_image_id'] ) ) {
						$previous_thum_ids[ $attachment_id ] = $image_result['listing_image_id'];
						update_post_meta( $p_id, 'ced_etsy_previous_thumb_ids' . $this->l_id, $previous_thum_ids );
					}
				}
			}

			/*
			|===================
			| UPLOAD MAIN IMAGE
			|===================
			*/
			if ( ! isset( $previous_thum_ids[ $prnt_img_id ] ) ) {
				$image_result = self::do_image_upload( $this->l_id, $p_id, $prnt_img_id, $shop_name );
				if ( isset( $image_result['listing_image_id'] ) ) {
					$previous_thum_ids[ $prnt_img_id ] = $image_result['listing_image_id'];
					update_post_meta( $p_id, 'ced_etsy_previous_thumb_ids' . $this->l_id, $previous_thum_ids );
				}
			}
		}

		/**
		 * ************************************
		 * UPLOAD IMAGED ON THE ETSY SHOP ;)
		 * ************************************
		 *
		 * @since 1.0.0
		 *
		 * @param int    $l_id Product listing ids.
		 * @param int    $pr_id Product ids .
		 * @param int    $img_id Image Ids.
		 * @param string $shop_name Active Shop Name
		 *
		 * @return Nothing [Message]
		 */

		public function do_image_upload( $l_id, $pr_id, $img_id, $shop_name ) {
			$image_path = get_attached_file( $img_id );
			$image_name = basename( $image_path );
			try {
				do_action( 'ced_etsy_refresh_token', $shop_name );
				$shop_id  = get_etsy_shop_id( $shop_name );
				$response = parent::ced_etsy_upload_image_and_file( 'image', "application/shops/{$shop_id}/listings/{$l_id}/images", $image_path, $image_name, $shop_name );
				return $this->ced_etsy_parse_response($response);
			} catch ( Exception $e ) {
				$this->error_msg .= 'Message: ' . $pr_id . '--' . $e->getMessage();
			}

		}

		public function ced_etsy_parse_response( $json ){
			return json_decode( $json,true );
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
					$this->getProfileAssignedData( $productId, $shop_name );
					$category_id = (int) $this->fetch_meta_value( $productId, '_umb_etsy_category' );
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
									$property = $this->fetch_meta_value( $productId, '_ced_etsy_property_id_' . $value['property_id'] );
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

		private function update_variation_sku_to_etsy( $product_id = '', $listing_id = '', $shop_name = '', $offerings_payload = '', $is_sync = false ) {

			// echo "<pre>";
			// print_r( json_encode( $offerings_payload  ));
			// die();
			// print_r( json_encode( $offerings_payload ) );
			// echo "Shop Name :-". get_etsy_shop_id( $shop_name );
			// echo "<br>";
			// echo "Listind id". $listing_id ;
			// return;

			do_action( 'ced_etsy_refresh_token', $shop_name );
			$response = parent::put( "application/listings/{$listing_id}/inventory", $offerings_payload, $shop_name );
			var_dump( $response );
			if ( isset( $response['listing_id'] ) ) {
				update_post_meta( $product_id, 'ced_etsy_last_updated' . $shop_name, gmdate( 'l jS \of F Y h:i:s A' ) );
			}
			if ( ! $is_sync ) {
				return $response;
			}
			return $response;
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

		public function doupload( $product_id, $shop_name ) {
			do_action( 'ced_etsy_refresh_token', $shop_name );
			$shop_id  = get_etsy_shop_id( $shop_name );
			$response = parent::post( "application/shops/{$shop_id}/listings", $this->data, $shop_name );			
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
				$error['error']         = isset( $response['error'] ) ? $response['error'] : 'some error occured';
				$this->upload_response = $error;
			} else {
				$this->upload_response = $response;
			}
		}

	}
}
