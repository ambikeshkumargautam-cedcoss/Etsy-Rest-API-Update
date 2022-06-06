<?php
namespace Cedcommerce\Order;
class Ced_Order_Get {

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
	 * Fetch order from Etsy. 
	 *
	 * @since 1.0.0
	 */

	public function get_orders( $shop_name='' ) {
		if (empty( $shop_name )) {
			return;
		}
		$saved_etsy_details = get_option( 'ced_etsy_details', true );
		$shopDetails        = $saved_etsy_details[ $shop_name ];
		$shop_id            = $shopDetails['details']['shop_id'];
		$last_created_order = get_option( 'ced_etsy_last_order_created_time', '' );
		$last_created_order = date_i18n( 'F d, Y h:i', strtotime( $last_created_order ) );
		$current_time       = current_time( 'F-i-j h:i:s' );
		$params             = array(
			'shop_id'  => isset( $shop_id ) ? $shop_id : '',
			'limit'    => 15,
			'page'     => 1,
			'was_paid' => '1',
		);

		$saved_global_settings_data = get_option( 'ced_etsy_global_settings', '' );
		$fetch_order_status         = isset( $saved_global_settings_data[ $shop_name ]['ced_fetch_etsy_order_by_status'] ) ? $saved_global_settings_data[ $shop_name ]['ced_fetch_etsy_order_by_status'] : '';
		if ( 'all' != $fetch_order_status ) {
			$order_status = '/' . $fetch_order_status;
		} else {
			$order_status = '';
		}

		do_action( 'ced_etsy_refresh_token', $shop_name );
		$shop_id = get_etsy_shop_id( $shop_name );
		$result  = etsy_request()->get( "application/shops/$shop_id/receipts/", $shop_name, $params );
		if ( isset( $result['results'] ) && ! empty( $result['results'] ) ) {
			$this->createLocalOrder( $result['results'], $shop_name );
		}
	}

	/*
	*
	*function for creating a local order
	*
	*
	*/
	public function createLocalOrder( $orders, $shopId = '' ) {
		if ( is_array( $orders ) && ! empty( $orders ) ) {
			$address        = array();
			$OrderItemsInfo = array();
			foreach ( $orders as $order ) {
				$receipt_id = isset( $order['receipt_id'] ) ? $order['receipt_id'] : '';
				if ( ! empty( $receipt_id ) ) {
					$params                   = array( 'receipt_id' => (int) $receipt_id );
					$success                  = $client->CallAPI( "https://openapi.etsy.com/v2/receipts/{$receipt_id}/transactions", 'GET', $params, array( 'FailOnAccessError' => true ), $transactions_per_reciept );
					$transactions_per_reciept = json_decode( json_encode( $transactions_per_reciept ), true );
					$ShipToFirstName          = isset( $order['name'] ) ? $order['name'] : '';
					$ShipToAddress1           = isset( $order['first_line'] ) ? $order['first_line'] : '';
					$ShipToAddress2           = isset( $order['second_line'] ) ? $order['second_line'] : '';
					$ShipToCityName           = isset( $order['city'] ) ? $order['city'] : '';

					$ShipToStateCode = isset( $order['state'] ) ? $order['state'] : '';
					$ShipToZipCode   = isset( $order['zip'] ) ? $order['zip'] : '';
					$shiptocountryid = isset( $order['country_id'] ) ? $order['country_id'] : '';

					$message_from_buyer = isset( $order['message_from_buyer'] ) ? $order['message_from_buyer'] : '';
					$gift_message       = isset( $order['gift_message'] ) ? $order['gift_message'] : '';

					if ( isset( $shiptocountryid ) && ! empty( $shiptocountryid ) ) {
						$params       = array( 'country_id' => (int) $shiptocountryid );
						$success      = $client->CallAPI( "https://openapi.etsy.com/v2/countries/{$shiptocountryid}", 'GET', $params, array( 'FailOnAccessError' => true ), $country_name );
						$country_name = json_decode( json_encode( $country_name ), true );
						if ( isset( $country_name['results'] ) && is_array( $country_name['results'] ) ) {
							foreach ( $country_name['results'] as $key => $value ) {
								$is_country = $value['iso_country_code'];
							}
						}
					}

					$exploded_name   = explode( ' ', $ShipToFirstName );
					$ShipToFirstName = isset( $exploded_name[0] ) ? $exploded_name[0] : '';
					$ShipToLastName  = isset( $exploded_name[1] ) ? $exploded_name[1] : '';

					$exploded_names_count = count( $exploded_name );
					if ( $exploded_names_count > 1 ) {
						$ShipToLastName  = array_pop( $exploded_name );
						$ShipToFirstName = implode( ' ', $exploded_name );
					}

					$ShippingAddress = array(
						'first_name' => $ShipToFirstName,
						'last_name'  => $ShipToLastName,
						'address_1'  => $ShipToAddress1,
						'address_2'  => $ShipToAddress2,
						'city'       => $ShipToCityName,
						'state'      => $ShipToStateCode,
						'postcode'   => $ShipToZipCode,
						'country'    => $is_country,
					);

					$BillToFirstName  = $ShipToFirstName;
					$BillEmailAddress = isset( $order['buyer_email'] ) ? $order['buyer_email'] : '';

					$BillingAddress = array(
						'first_name' => $BillToFirstName,
						'last_name'  => $ShipToLastName,
						'email'      => $BillEmailAddress,
						'address_1'  => $ShipToAddress1,
						'address_2'  => $ShipToAddress2,
						'city'       => $ShipToCityName,
						'state'      => $ShipToStateCode,
						'postcode'   => $ShipToZipCode,
						'country'    => $is_country,
					);

					$address['shipping'] = $ShippingAddress;
					$address['billing']  = $BillingAddress;

					$OrderNumber  = isset( $order['receipt_id'] ) ? $order['receipt_id'] : '';
					$order_status = 'processing';
					if ( isset( $transactions_per_reciept['results'] ) && ! empty( $transactions_per_reciept['results'] ) ) {
						$transactions_per_reciept = $transactions_per_reciept['results'];
						$ItemArray                = array();
						foreach ( $transactions_per_reciept as $transaction ) {
							$ID         = false;
							$listing_id = isset( $transaction['listing_id'] ) ? $transaction['listing_id'] : false;
							$OrderedQty = isset( $transaction['quantity'] ) ? $transaction['quantity'] : 1;
							$basePrice  = isset( $transaction['price'] ) ? $transaction['price'] : '';
							$variations = isset( $transaction['variations'] ) ? $transaction['variations'] : array();
							$CancelQty  = 0;
							$sku        = isset( $transaction['product_data']['sku'] ) ? $transaction['product_data']['sku'] : '';
							if ( ! empty( $sku ) ) {
								$ID = get_product_id_by_params( '_sku', $sku );
							}

							if ( ! $ID ) {
								$ID = get_product_id_by_params( '_ced_etsy_listing_id_' . $shopId, $listing_id );
							}

							$item        = array(
								'OrderedQty' => $OrderedQty,
								'CancelQty'  => $CancelQty,
								'UnitPrice'  => $basePrice,
								'Sku'        => $sku,
								'ID'         => $ID,
								'variations' => $variations,
							);
							$ItemArray[] = $item;

						}
					}
				}

				$ShippingAmount   = isset( $order['total_shipping_cost'] ) ? $order['total_shipping_cost'] : 0;
				$ShipService      = isset( $order['shipping_details']['shipping_method'] ) ? $order['shipping_details']['shipping_method'] : '';
				$DiscountedAmount = isset( $order['discount_amt'] ) ? $order['discount_amt'] : 0;
				$gift_wrap_price  = isset( $order['gift_wrap_price'] ) ? $order['gift_wrap_price'] : 0;
				$finalTax         = 1;

				$fees_array = array(
					'Discount'      => 0 - $DiscountedAmount,
					'Gift Wrapping' => $gift_wrap_price,
				);

				$OrderItemsInfo = array(
					'OrderNumber'        => isset( $OrderNumber ) ? $OrderNumber : '',
					'ItemsArray'         => isset( $ItemArray ) ? $ItemArray : '',
					'tax'                => isset( $finalTax ) ? $finalTax : '',
					'ShippingAmount'     => isset( $ShippingAmount ) ? $ShippingAmount : '',
					'ShipService'        => isset( $ShipService ) ? $ShipService : '',
					'DiscountedAmount'   => isset( $DiscountedAmount ) ? $DiscountedAmount : '',
					'message_from_buyer' => isset( $message_from_buyer ) ? $message_from_buyer : '',
					'gift_message'       => isset( $gift_message ) ? $gift_message : '',
					'fees_array'         => $fees_array,
				);
				$orderItems     = isset( $transactions_per_reciept ) ? $transactions_per_reciept : '';

				$merchantOrderId = isset( $OrderNumber ) ? $OrderNumber : '';
				$purchaseOrderId = isset( $OrderNumber ) ? $OrderNumber : '';
				$fulfillmentNode = '';
				$orderDetail     = isset( $order ) ? $order : array();
				$etsyOrderMeta   = array(
					'merchant_order_id' => isset( $merchantOrderId ) ? $merchantOrderId : '',
					'purchaseOrderId'   => isset( $purchaseOrderId ) ? $purchaseOrderId : '',
					'fulfillment_node'  => isset( $fulfillmentNode ) ? $fulfillmentNode : '',
					'order_detail'      => isset( $orderDetail ) ? $orderDetail : '',
					'order_items'       => isset( $orderItems ) ? $orderItems : '',
				);
				$creation_date   = $order['creation_tsz'];
				$order_id        = $this->create_order( $address, $OrderItemsInfo, 'Etsy', $etsyOrderMeta, $creation_date, $shopId );
			}
		}
	}
	
	/*
	*
	*function for creating order in woocommerce
	*
	*
	*/

	public function create_order( $address = array(), $OrderItemsInfo = array(), $frameworkName = 'etsy', $orderMeta = array(), $creation_date = '', $shopId ) {
		$order_id      = '';
		$order_created = false;
		if ( count( $OrderItemsInfo ) ) {

			$OrderNumber = isset( $OrderItemsInfo['OrderNumber'] ) ? $OrderItemsInfo['OrderNumber'] : 0;
			$order_id    = $this->is_etsy_order_exists( $OrderNumber );

			if ( $order_id ) {
				return $order_id;
			}

			if ( count( $OrderItemsInfo ) ) {
				$ItemsArray = isset( $OrderItemsInfo['ItemsArray'] ) ? $OrderItemsInfo['ItemsArray'] : array();
				if ( is_array( $ItemsArray ) ) {
					foreach ( $ItemsArray as $ItemInfo ) {
						$ProID = isset( $ItemInfo['ID'] ) ? intval( $ItemInfo['ID'] ) : 0;
						$Sku   = isset( $ItemInfo['Sku'] ) ? $ItemInfo['Sku'] : '';

						$MfrPartNumber = isset( $ItemInfo['MfrPartNumber'] ) ? $ItemInfo['MfrPartNumber'] : '';
						$Upc           = isset( $ItemInfo['UPCCode'] ) ? $ItemInfo['UPCCode'] : '';
						$Asin          = isset( $ItemInfo['ASIN'] ) ? $ItemInfo['ASIN'] : '';
						$variations    = isset( $ItemInfo['variations'] ) ? $ItemInfo['variations'] : array();
						$params        = array( '_sku' => $Sku );
						if ( ! $ProID && ! empty( $Sku ) ) {
							$ProID = wc_get_product_id_by_sku( $Sku );
						}
						if ( ! $ProID ) {
							$ProID = $Sku;
						}
						$productsToUpdate[]   = $ProID;
						$Qty                  = isset( $ItemInfo['OrderedQty'] ) ? intval( $ItemInfo['OrderedQty'] ) : 0;
						$UnitPrice            = isset( $ItemInfo['UnitPrice'] ) ? floatval( $ItemInfo['UnitPrice'] ) : 0;
						$ExtendUnitPrice      = isset( $ItemInfo['ExtendUnitPrice'] ) ? floatval( $ItemInfo['ExtendUnitPrice'] ) : 0;
						$ExtendShippingCharge = isset( $ItemInfo['ExtendShippingCharge'] ) ? floatval( $ItemInfo['ExtendShippingCharge'] ) : 0;
						$_product             = wc_get_product( $ProID );

						if ( is_wp_error( $_product ) ) {
							continue;
						} elseif ( is_null( $_product ) ) {
							continue;
						} elseif ( ! $_product ) {
							continue;
						} else {
							if ( ! $order_created ) {
								$order_data = array(
									'status'        => apply_filters( 'woocommerce_default_order_status', 'pending' ),
									'customer_note' => $OrderItemsInfo['message_from_buyer'],
									'created_via'   => $frameworkName,
								);

								/* ORDER CREATED IN WOOCOMMERCE */
								$order = wc_create_order( $order_data );

								/* ORDER CREATED IN WOOCOMMERCE */

								if ( is_wp_error( $order ) ) {
									continue;
								} elseif ( false === $order ) {
									continue;
								} else {
									if ( WC()->version < '3.0.0' ) {
										$order_id = $order->id;
									} else {
										$order_id = $order->get_id();
									}
									$order_created = true;
								}
							}

							if ( ! empty( $OrderItemsInfo['gift_message'] ) ) {
								$note = '<b><i>Gift message from buyer :</i></b> ' . $OrderItemsInfo['gift_message'];
								$order->add_order_note( $note );
							}

							update_post_meta( $order_id, '_ced_etsy_order_id', $OrderNumber );

							$_product->set_price( $UnitPrice );
							$item_id = $order->add_product( $_product, $Qty );
							$order->calculate_totals();

							if ( ! empty( $variations ) && is_array( $variations ) ) {
								foreach ( $variations as $variation ) {
									wc_update_order_item_meta( $item_id, $variation['formatted_name'], $variation['formatted_value'] );
								}
							}
						}
					}
				}

				if ( ! $order_created ) {
					return false;
				}

				$OrderItemAmount = isset( $OrderItemsInfo['OrderItemAmount'] ) ? $OrderItemsInfo['OrderItemAmount'] : 0;
				$ShippingAmount  = isset( $OrderItemsInfo['ShippingAmount'] ) ? $OrderItemsInfo['ShippingAmount'] : 0;
				$DiscountAmount  = isset( $OrderItemsInfo['DiscountAmount'] ) ? $OrderItemsInfo['DiscountAmount'] : 0;
				$RefundAmount    = isset( $OrderItemsInfo['RefundAmount'] ) ? $OrderItemsInfo['RefundAmount'] : 0;
				$ShipService     = isset( $OrderItemsInfo['ShipService'] ) ? $OrderItemsInfo['ShipService'] : '';

				$fees_array = isset( $OrderItemsInfo['fees_array'] ) ? $OrderItemsInfo['fees_array'] : '';

				if ( ! empty( $fees_array ) ) {
					foreach ( $fees_array as $fee_name => $fee_value ) {
						$item_fee = new WC_Order_Item_Fee();
						$item_fee->set_name( $fee_name );
						$fee_amount = (float) $fee_value;
						$item_fee->set_total( $fee_amount );
						$order->add_item( $item_fee );
					}
				}

				if ( ! empty( $ShipService ) ) {
					$Ship_params = array(
						'ShippingCost' => $ShippingAmount,
						'ShipService'  => $ShipService,
					);
					$this->add_shipping_charge( $order, $Ship_params );
				}

				$ShippingAddress = isset( $address['shipping'] ) ? $address['shipping'] : '';
				if ( is_array( $ShippingAddress ) && ! empty( $ShippingAddress ) ) {
					if ( WC()->version < '3.0.0' ) {
						$order->set_address( $ShippingAddress, 'shipping' );
					} else {
						$type = 'shipping';
						foreach ( $ShippingAddress as $key => $value ) {
							if ( ! empty( $value ) && null != $value && ! empty( $value ) ) {
								update_post_meta( $order->get_id(), "_{$type}_" . $key, $value );
								if ( is_callable( array( $order, "set_{$type}_{$key}" ) ) ) {
									$order->{"set_{$type}_{$key}"}( $value );
								}
							}
						}
					}
				}

				$new_fee            = new stdClass();
				$new_fee->name      = 'Tax';
				$new_fee->amount    = (float) esc_attr( $OrderItemsInfo['tax'] );
				$new_fee->tax_class = '';
				$new_fee->taxable   = 0;
				$new_fee->tax       = '';
				$new_fee->tax_data  = array();
				if ( WC()->version < '3.0.0' ) {
					$item_id = $order->add_fee( $new_fee );
				} else {
					$item_id = $order->add_item( $new_fee );
				}

				$BillingAddress = isset( $address['billing'] ) ? $address['billing'] : '';
				if ( is_array( $BillingAddress ) && ! empty( $BillingAddress ) ) {
					if ( WC()->version < '3.0.0' ) {
						$order->set_address( $ShippingAddress, 'billing' );
					} else {
						$type = 'billing';
						foreach ( $BillingAddress as $key => $value ) {
							if ( null != $value && ! empty( $value ) ) {
								update_post_meta( $order->get_id(), "_{$type}_" . $key, $value );
								if ( is_callable( array( $order, "set_{$type}_{$key}" ) ) ) {
									$order->{"set_{$type}_{$key}"}( $value );
								}
							}
						}
					}
				}
				wc_reduce_stock_levels( $order->get_id() );
				$order->set_payment_method( 'check' );

				if ( WC()->version < '3.0.0' ) {
					$order->set_total( $DiscountAmount, 'cart_discount' );
				} else {
					$order->set_total( $DiscountAmount );
				}

				$order->calculate_totals();

				update_post_meta( $order_id, '_ced_etsy_order_id', $OrderNumber );
				update_post_meta( $order_id, '_is_ced_etsy_order', 1 );
				update_post_meta( $order_id, '_etsy_umb_order_status', 'Fetched' );
				update_post_meta( $order_id, '_umb_etsy_marketplace', $frameworkName );
				update_post_meta( $order_id, 'ced_etsy_order_shop_id', $shopId );
				update_option( 'ced_etsy_last_order_created_time', $creation_date );

				$data_on_global_settings = get_option( 'ced_etsy_global_settings', array() );
				$default_order_status       = ! empty( $data_on_global_settings[ $shopId ]['default_order_status'] ) ? $data_on_global_settings[ $shopId ]['default_order_status'] : 'wc-processing';
				$order->update_status( $default_order_status );
				if ( count( $orderMeta ) ) {
					foreach ( $orderMeta as $oKey => $oValue ) {
						update_post_meta( $order_id, $oKey, $oValue );
					}
				}
			}

			return $order_id;
		}
		return false;
	}

	/**
	 * Etsy checking if order already exists
	 *
	 * @since    1.0.0
	 */
	public function is_etsy_order_exists( $order_number = 0 ) {
		global $wpdb;
		if ( $order_number ) {
			$order_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_ced_etsy_order_id' AND meta_value=%s LIMIT 1", $order_number ) );
			if ( $order_id ) {
				return $order_id;
			}
		}
		return false;
	}

	/**
	 * Function to add shipping data
	 *
	 * @since 1.0.0
	 * @param object $order Order details.
	 * @param array  $ship_params Shipping details.
	 */
	public static function add_shipping_charge( $order, $ship_params = array() ) {
		$ship_name = isset( $ship_params['ShipService'] ) ? ( $ship_params['ShipService'] ) : 'UMB Default Shipping';
		$ship_cost = isset( $ship_params['ShippingCost'] ) ? $ship_params['ShippingCost'] : 0;
		$ship_tax  = isset( $ship_params['ShippingTax'] ) ? $ship_params['ShippingTax'] : 0;
		$item      = new WC_Order_Item_Shipping();
		$item->set_method_title( $ship_name );
		$item->set_method_id( $ship_name );
		$item->set_total( $ship_cost );
		$order->add_item( $item );
		$order->calculate_totals();
		$order->save();
	}
}
