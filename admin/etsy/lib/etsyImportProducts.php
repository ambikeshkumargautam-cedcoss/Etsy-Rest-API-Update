<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( ! class_exists( 'Class_Ced_Etsy_Import_Product' ) ) {

	class Class_Ced_Etsy_Import_Product {


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
		 * ****************************************************
		 * IMPORT PRODUCT BY BULK ACTION OR IMPORT LINK (TEXT)
		 * ****************************************************
		 *
		 * @since    2.0.0
		 */
		public function ced_etsy_import_products( $listing_id = '', $shop_name = '' ) {

			if ( empty( $listing_id ) || empty( $shop_name ) ) {
				return;
			}
			$renderDataOnGlobalSettings = get_option( 'ced_etsy_global_settings', '' );
			$language                   = isset( $renderDataOnGlobalSettings[ $shop_name ]['product_data']['_etsy_language']['default'] ) ? $renderDataOnGlobalSettings[ $shop_name ]['product_data']['_etsy_language']['default'] : 'en';

			$saved_etsy_details = get_option( 'ced_etsy_details', true );
			$shopDetails        = $saved_etsy_details[ $shop_name ];
			$shop_id            = $shopDetails['details']['shop_id'];
			$client             = ced_etsy_getOauthClientObject( $shop_name );
			$offset             = get_option( 'ced_etsy_get_import_offset', 0 );

			if ( empty( $offset ) ) {
				$offset = 0;
			}
			$params  = array(
				'listing_id' => $listing_id,
			);
			$success = $client->CallAPI( 'https://openapi.etsy.com/v2/listings/' . $listing_id . '?language=' . $language, 'GET', $params, array( 'FailOnAccessError' => true ), $listings_details );

			$response = json_decode( json_encode( $listings_details ), true );
			if ( isset( $response['results'][0] ) ) {
				$this->get_listing_details( $response['results'][0], $shop_name, $shop_id );
			}
		}

		/**
		 * ******************************************************************************
		 *  IMPORT PRODUCT BY SCHEDULER [AUTO IMPORT PRODUCT AND UPDATE THE INVENTORY]
		 * ******************************************************************************
		 *
		 * @since    2.0.0
		 */

		public function get_listing_details_auto_upload( $product = array(), $shop_name = '', $shop_id = '' ) {

			$listing_id = isset( $product['listing_id'] ) ? $product['listing_id'] : '';
			if ( empty( $listing_id ) ) {
				return;
			}

			$if_product_exists = etsy_get_product_id_by_shopname_and_listing_id( $shop_name, $listing_id );
			if ( ! empty( $if_product_exists ) ) {
				return;

				/**
				 * ******************************************************
				 *   Updating Changes From Etsy To Woocommerce products.
				 * ******************************************************
				 */
				$product_sync_etsy_to_woo = get_option( 'ced_etsy_global_settings', '' );
				$product_sync_etsy_to_woo = $product_sync_etsy_to_woo[ $shop_name ]['ced_etsy_sync_product_etsy_to_woo_info'];

				if ( false ) {

					$product_id = $if_product_exists;
					 $post      = array(
						 'ID'           => esc_sql( $product_id ),
						 'post_content' => wp_kses_post( $product['description'] ),
						 'post_title'   => wp_strip_all_tags( $product['title'] ),
					 );
					 $parent_id = wp_update_post( $post, true );
					 update_post_meta( $product_id, '_weight', $product['item_weight'] );
					 update_post_meta( $product_id, '_length', $product['item_length'] );
					 update_post_meta( $product_id, '_width', $product['item_width'] );
					 update_post_meta( $product_id, '_height', $product['item_height'] );

					 if ( isset( $product['sku'][0] ) ) {
						 update_post_meta( $product_id, '_sku', $product['sku'][0] );
					 } else {
						 update_post_meta( $product_id, '_sku', $product['listing_id'] );
					 }

					 if ( $product['quantity'] > 0 ) {
						 update_post_meta( $product_id, '_stock_status', 'instock' );
						 update_post_meta( $product_id, '_manage_stock', 'yes' );
						 update_post_meta( $product_id, '_stock', $product['quantity'] );
					 } else {
						 update_post_meta( $product_id, '_stock_status', 'outofstock' );
					 }
					 update_post_meta( $product_id, '_regular_price', $product['price'] );
					 update_post_meta( $product_id, '_price', $product['price'] );

					 $product_type = wc_get_product( $parent_id );
					 if ( $product_type->is_type( 'variable' ) ) {
						 $client = ced_etsy_getOauthClientObject( $shop_name );
						 $params = array( 'listing_id' => $listing_id );
						 // Call for get inventory
						 $response = $client->CallAPI( 'https://openapi.etsy.com/v2/listings/' . $listing_id . '/inventory', 'GET', $params, array( 'FailOnAccessError' => true ), $listings_variable );

							$listings_variable       = json_decode( json_encode( $listings_variable, true ), true );
							$etsy_variation_products = $listings_variable['results']['products'];
						 foreach ( $etsy_variation_products as $key => $value ) {
							 $variation_id = $value['product_id'];
							 update_post_meta( $variation_id, '_reguler_price', $value['offerings'][0]['price']['currency_formatted_raw'] );
							 update_post_meta( $variation_id, '_stock', $value['offerings'][0]['quantity'] );
						 }
					 }
				} else {
					return;
				}
			}

			$client           = ced_etsy_getOauthClientObject( $shop_name );
			$params           = array( 'listing_id' => $listing_id );
			$response         = $client->CallAPI( 'https://openapi.etsy.com/v2/listings/' . $listing_id . '/inventory', 'GET', $params, array( 'FailOnAccessError' => true ), $listings_details );
			$images           = $client->CallAPI( 'https://openapi.etsy.com/v2/listings/' . $listing_id . '/images', 'GET', $params, array( 'FailOnAccessError' => true ), $images_details );
			$listings_details = json_decode( json_encode( $listings_details ), true );
			$image_details    = json_decode( json_encode( $images_details ), true );

			if ( ! empty( $listings_details ) ) {
				if ( isset( $listings_details['results']['products'] ) && count( $listings_details['results']['products'] ) > 1 ) {
					$this->create_variable_product( $product, $listings_details, $image_details, $shop_name );
				} else {
					$this->create_simple_product( $product, $listings_details, $image_details, $shop_name );
				}
			} else {
				return;
			}

		}

		/**
		 * *************************************
		 * Etsy get_listing_details.
		 * *************************************
		 *
		 * @since    2.0.0
		 */
		private function get_listing_details( $product = array(), $shop_name = '', $shop_id = '' ) {

			$listing_id = isset( $product['listing_id'] ) ? $product['listing_id'] : '';
			if ( empty( $listing_id ) ) {
				return;
			}
			$if_product_exists = etsy_get_product_id_by_shopname_and_listing_id( $shop_name, $listing_id );
			if ( ! empty( $if_product_exists ) ) {
				return;
			}

			$client = ced_etsy_getOauthClientObject( $shop_name );
			$params = array( 'listing_id' => $listing_id );
			// Call for get inventory
			$response = $client->CallAPI( 'https://openapi.etsy.com/v2/listings/' . $listing_id . '/inventory', 'GET', $params, array( 'FailOnAccessError' => true ), $listings_details );

			// Call for get image details.
			$images = $client->CallAPI( 'https://openapi.etsy.com/v2/listings/' . $listing_id . '/images', 'GET', $params, array( 'FailOnAccessError' => true ), $images_details );

			$listings_details = json_decode( json_encode( $listings_details ), true );
			$image_details    = json_decode( json_encode( $images_details ), true );
			if ( isset( $listings_details['results']['products'] ) && count( $listings_details['results']['products'] ) > 1 ) {
				$this->create_variable_product( $product, $listings_details, $image_details, $shop_name );
			} else {
				$this->create_simple_product( $product, $listings_details, $image_details, $shop_name );
			}
		}

		/**
		 * *****************************************
		 * CREATING SIMPLE PRODUCT IN WOOCOMMERCE
		 * *****************************************
		 *
		 * @since 2.0.0
		 */
		private function create_simple_product( $product = '', $product_details = '', $image_details = '', $shop_name = '' ) {

			$product_id = wp_insert_post(
				array(
					'post_title'   => $product['title'],
					'post_status'  => 'publish',
					'post_type'    => 'product',
					'post_content' => $product['description'],
				)
			);

			$this->insert_product_tags( $product_id, $product );
			$this->insert_product_category( $product_id, $product, $product_details );

			$_weight = isset( $product['item_weight'] ) ? $product['item_weight'] : 0;
			$_length = isset( $product['item_length'] ) ? $product['item_length'] : 0;
			$_width  = isset( $product['item_width'] ) ? $product['item_width'] : 0;
			$_height = isset( $product['item_height'] ) ? $product['item_height'] : 0;

			update_post_meta( $product_id, '_weight', $_weight );
			update_post_meta( $product_id, '_length', $_length );
			update_post_meta( $product_id, '_width', $_width );
			update_post_meta( $product_id, '_height', $_height );

			wp_set_object_terms( $product_id, 'simple', 'product_type' );
			update_post_meta( $product_id, '_visibility', 'visible' );
			update_post_meta( $product_id, '_ced_etsy_listing_id_' . $shop_name, $product['listing_id'] );
			update_post_meta( $product_id, '_ced_etsy_url_' . $shop_name, $product['url'] );
			update_post_meta( $product_id, 'ced_etsy_product_data', $product );
			update_post_meta( $product_id, 'ced_etsy_product_inventory', $product_details );
			if ( isset( $product['sku'][0] ) ) {
				update_post_meta( $product_id, '_sku', $product['sku'][0] );
			} else {
				update_post_meta( $product_id, '_sku', $product['listing_id'] );
			}
			update_post_meta( $product_id, '_stock_status', 'instock' );

			if ( $product['quantity'] > 0 ) {
				update_post_meta( $product_id, '_stock_status', 'instock' );
				update_post_meta( $product_id, '_manage_stock', 'yes' );
				update_post_meta( $product_id, '_stock', $product['quantity'] );
			} else {
				update_post_meta( $product_id, '_stock_status', 'outofstock' );
			}
			update_post_meta( $product_id, '_regular_price', $product['price'] );
			update_post_meta( $product_id, '_price', $product['price'] );

			if ( isset( $image_details['results'] ) ) {
				$this->create_product_images( $product_id, $image_details['results'] );
			}

		}

		/**
		 * *****************************************
		 * CREATING VARIABLE PRODUCT IN WOOCOMMERCE
		 * *****************************************
		 *
		 * @since 2.0.0
		 */

		private function create_variable_product( $product = '', $product_details = '', $image_details = '', $shop_name = '' ) {

			$etsy_variation_products = $product_details['results']['products'];
			$product_id              = wp_insert_post(
				array(
					'post_title'   => $product['title'],
					'post_status'  => 'publish',
					'post_type'    => 'product',
					'post_content' => $product['description'],
				)
			);

			$_weight = isset( $product['item_weight'] ) ? $product['item_weight'] : 0;
			$_length = isset( $product['item_length'] ) ? $product['item_length'] : 0;
			$_width  = isset( $product['item_width'] ) ? $product['item_width'] : 0;
			$_height = isset( $product['item_height'] ) ? $product['item_height'] : 0;

			wp_set_object_terms( $product_id, 'variable', 'product_type' );
			update_post_meta( $product_id, '_visibility', 'visible' );
			update_post_meta( $product_id, '_ced_etsy_listing_id_' . $shop_name, $product['listing_id'] );
			update_post_meta( $product_id, '_ced_etsy_url_' . $shop_name, $product['url'] );
			update_post_meta( $product_id, 'ced_etsy_product_data', $product );
			update_post_meta( $product_id, 'ced_etsy_product_inventory', $product_details );
			update_post_meta( $product_id, '_sku', $product['listing_id'] );
			update_post_meta( $product_id, '_stock_status', 'instock' );
			update_post_meta( $product_id, '_weight', $_weight );
			update_post_meta( $product_id, '_length', $_length );
			update_post_meta( $product_id, '_width', $_width );
			update_post_meta( $product_id, '_height', $_height );

			foreach ( $etsy_variation_products[0]['property_values'] as $key => $value ) {
				$avaliable_variation_attributes[] = $value['property_name'];
			}
			$attr_value = array();
			foreach ( $etsy_variation_products as $key => $value ) {
				foreach ( $value['property_values'] as $key1 => $value1 ) {
					$variations[ $key ]['attributes'][ $value1['property_name'] ] = $value1['values'][0];
					$attr_value[ $value1['property_name'] ][]                     = $value1['values'][0];
				}
				$variations[ $key ]['price']    = $value['offerings'][0]['price']['currency_formatted_raw'];
				$variations[ $key ]['quantity'] = $value['offerings'][0]['quantity'];
				$variations[ $key ]['sku']      = $value['sku'];
			}

			foreach ( $avaliable_variation_attributes as $key => $value ) {
				$data['attribute_names'][]    = $value;
				$data['attribute_position'][] = $key;
				$values                       = array();
				foreach ( $variations as $key1 => $value1 ) {
					$values[] = $value1['attributes'][ $value ];
				}
				$values                         = array_unique( $values );
				$data['attribute_values'][]     = implode( '|', $values );
				$data['attribute_visibility'][] = 1;
				$data['attribute_variation'][]  = 1;
			}
			if ( isset( $data['attribute_names'], $data['attribute_values'] ) ) {
				$attribute_names         = $data['attribute_names'];
				$attribute_values        = $data['attribute_values'];
				$attribute_visibility    = isset( $data['attribute_visibility'] ) ? $data['attribute_visibility'] : array();
				$attribute_variation     = isset( $data['attribute_variation'] ) ? $data['attribute_variation'] : array();
				$attribute_position      = $data['attribute_position'];
				$attribute_names_max_key = max( array_keys( $attribute_names ) );

				for ( $i = 0; $i <= $attribute_names_max_key; $i++ ) {
					if ( empty( $attribute_names[ $i ] ) || ! isset( $attribute_values[ $i ] ) ) {
						continue;
					}
					$attribute_id   = 0;
					$attribute_name = wc_clean( $attribute_names[ $i ] );

					if ( 'pa_' === substr( $attribute_name, 0, 3 ) ) {
						$attribute_id = wc_attribute_taxonomy_id_by_name( $attribute_name );
					}

					$options = isset( $attribute_values[ $i ] ) ? $attribute_values[ $i ] : '';

					if ( is_array( $options ) ) {
						$options = wp_parse_id_list( $options );
					} else {
						$options = 0 < $attribute_id ? wc_sanitize_textarea( wc_sanitize_term_text_based( $options ) ) : wc_sanitize_textarea( $options );
						$options = wc_get_text_attributes( $options );
					}

					if ( empty( $options ) ) {
						continue;
					}

					$attribute = new WC_Product_Attribute();
					$attribute->set_id( $attribute_id );
					$attribute->set_name( $attribute_name );
					$attribute->set_options( $options );
					$attribute->set_position( $attribute_position[ $i ] );
					$attribute->set_visible( isset( $attribute_visibility[ $i ] ) );
					$attribute->set_variation( isset( $attribute_variation[ $i ] ) );
					$attributes[] = $attribute;
				}
			}

			$product_type = 'variable';
			$classname    = WC_Product_Factory::get_product_classname( $product_id, $product_type );
			$_product     = new $classname( $product_id );
			$_product->set_attributes( $attributes );
			$_product->save();

			$this->insert_product_category( $product_id, $product, $product_details );
			$this->insert_product_tags( $product_id, $product );
			$this->insert_product_variations( $product_id, $variations, $avaliable_variation_attributes );

			if ( isset( $image_details['results'] ) ) {
				$this->create_product_images( $product_id, $image_details['results'] );
			}
		}


		/**
		 * *********************************************
		 * INSERTING PRODUCT CATEGORY IN WOO FROM ETSY
		 * *********************************************
		 *
		 * @since 2.0.0
		 */
		private function insert_product_category( $product_id = '', $listing_details = '', $inventory_details = '' ) {
			if ( isset( $listing_details['taxonomy_path'][0] ) ) {
				$categoryPath = $listing_details['taxonomy_path'];
			} else {
				$categoryPath = array( $listing_details['taxonomy_path'] );
			}
			$term_ids  = array();
			$parent_id = '';
			foreach ( $categoryPath as $key => $value ) {
				if ( empty( $value ) ) {
					continue;
				}
				$term = wp_insert_term(
					$value,
					'product_cat',
					array(
						'description' => $value,
						'parent'      => $parent_id,
					)
				);
				if ( isset( $term->error_data['term_exists'] ) ) {

					$term_id = $term->error_data['term_exists'];
				} elseif ( isset( $term['term_id'] ) ) {
					$term_id = $term['term_id'];
				}
				$term_ids[] = $term_id;

				$parent_id = ! empty( $term_id ) ? $term_id : '';
			}
			wp_set_object_terms( $product_id, array_unique( $term_ids ), 'product_cat' );

		}

		/**
		 * *********************************************
		 * INSERTING PRODUCT TAG IN WOO FROM ETSY
		 * *********************************************
		 *
		 * @since 2.0.0
		 */
		private function insert_product_tags( $product_id = '', $listing_details = '' ) {

			if ( isset( $listing_details['tags'][0] ) ) {
				$tagsPath = $listing_details['tags'];
			} else {
				$tagsPath = array( $listing_details['tags'] );
			}
			$term_ids = array();
			foreach ( $tagsPath as $key => $value ) {
				if ( empty( $value ) ) {
					continue;
				}
				$term = wp_insert_term(
					$value,
					'product_tag',
					array(
						'description' => $value,
					)
				);
				if ( isset( $term->error_data['term_exists'] ) ) {
					$term_ids[] = $term->error_data['term_exists'];
				} elseif ( isset( $term['term_id'] ) ) {
					$term_ids[] = $term['term_id'];
				}
			}
			wp_set_object_terms( $product_id, array_unique( $term_ids ), 'product_tag' );
		}

		/**
		 * *********************************************
		 * INSERTING PRODUCT VARIATIONS IN WOO FROM ETSY
		 * *********************************************
		 *
		 * @since 2.0.0
		 */

		private function insert_product_variations( $post_id, $variations, $available_attributes ) {
			$parent_qty = 0;
			foreach ( $variations as $index => $variation ) {
				$variation_post = array(
					'post_title'  => 'Variation #' . $index . ' of ' . count( $variations ) . ' for product#' . $post_id,
					'post_name'   => 'product-' . $post_id . '-variation-' . $index,
					'post_status' => 'publish',
					'post_parent' => $post_id,
					'post_type'   => 'product_variation',
					'guid'        => home_url() . '/?product_variation=product-' . $post_id . '-variation-' . $index,
				);

				$variation_post_id = wp_insert_post( $variation_post );

				foreach ( $available_attributes as $key => $value ) {
					$values = array();
					foreach ( $variations as $key1 => $value1 ) {
						$values[] = $value1['attributes'][ $value ];
					}
					$values          = array_unique( $values );
					$array[ $value ] = array_values( $values );
					$newvalues       = array();
					foreach ( $values as $key => $mwvalue ) {
						$newvalues[] = $mwvalue;
					}
					wp_set_object_terms( $variation_post_id, $newvalues, $value );
				}

				foreach ( $variation['attributes'] as $attribute => $value ) {
					$attr = strtolower( $attribute );
					$attr = str_replace( ' ', '-', $attr );
					$attr = sanitize_title( $attr );

					update_post_meta( $variation_post_id, 'attribute_' . $attr, $value );

					$thedata = array(
						$attr => array(
							'name'         => $value,
							'value'        => '',
							'is_visible'   => '1',
							'is_variation' => '1',
							'is_taxonomy'  => '1',
						),
					);

					update_post_meta( $variation_post_id, '_product_attributes', $thedata );

				}
				if ( $variation['quantity'] > 0 ) {
					$parent_qty = $parent_qty + (int) $variation['quantity'];
					update_post_meta( $variation_post_id, '_stock_status', 'instock' );
					update_post_meta( $variation_post_id, '_stock', $variation['quantity'] );
					update_post_meta( $variation_post_id, '_manage_stock', 'yes' );
					update_post_meta( $post_id, '_stock_status', 'instock' );
				} else {
					update_post_meta( $variation_post_id, '_stock_status', 'outofstock' );
				}

				update_post_meta( $variation_post_id, '_price', str_replace( ',', '', $variation['price'] ) );
				update_post_meta( $variation_post_id, '_regular_price', str_replace( ',', '', $variation['price'] ) );
				update_post_meta( $variation_post_id, '_sku', str_replace( ',', '', $variation['sku'] ) );

			}

			if ( $parent_qty > 0 ) {
				update_post_meta( $post_id, '_stock', (int) $parent_qty );
			}
		}

		/**
		 * *********************************************
		 * INSERTING PRODUCT IMAGES IN WOO FROM ETSY
		 * *********************************************
		 *
		 * @since 2.0.0
		 */

		private function create_product_images( $product_id, $images = array() ) {

			foreach ( $images as $key1 => $value1 ) {
				$image_url  = $value1['url_fullxfull'];
				$image_name = explode( '/', $image_url );
				$image_name = $image_name[ count( $image_name ) - 1 ];

				$upload_dir       = wp_upload_dir();
				$image_url        = str_replace( 'https', 'http', $image_url );
				$image_data       = file_get_contents( $image_url );
				$unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name );
				$filename         = basename( $unique_file_name );
				if ( wp_mkdir_p( $upload_dir['path'] ) ) {
					$file = $upload_dir['path'] . '/' . $filename;
				} else {
					$file = $upload_dir['basedir'] . '/' . $filename;
				}
				file_put_contents( $file, $image_data );
				$wp_filetype = wp_check_filetype( $filename, null );
				$attachment  = array(
					'post_mime_type' => $wp_filetype['type'],
					'post_title'     => sanitize_file_name( $filename ),
					'post_content'   => '',
					'post_status'    => 'inherit',
				);
				$attach_id   = wp_insert_attachment( $attachment, $file, $product_id );
				require_once ABSPATH . 'wp-admin/includes/image.php';
				$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
				wp_update_attachment_metadata( $attach_id, $attach_data );
				if ( 0 == $key1 ) {
					set_post_thumbnail( $product_id, $attach_id );
				} else {
					$image_ids[] = $attach_id;
				}
			}

			// PRODUCT GALLERY IMAGES
			if ( ! empty( $image_ids ) ) {
				update_post_meta( $product_id, '_product_image_gallery', implode( ',', $image_ids ) );

			}
		}
	}
}
