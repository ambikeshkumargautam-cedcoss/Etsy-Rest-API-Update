<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

Cedhandler::ced_header();
?>
<div class="ced_etsy_heading ">
	<?php echo esc_html_e( get_etsy_instuctions_html() ); ?>
	<div class="ced_etsy_child_element">
		<?php
				$shop_name = isset( $_GET['shop_name'] ) ? sanitize_text_field( $_GET['shop_name'] ) : '';
				$instructions = array(
					'In this section all the configuration related to product and order sync are provided.',
					'It is mandatory to fill/map the required attributes [ <span style="color:red;">*</span> ] in <a>Product Export Settings</a> section.',
					'View the  information of each attribute using the tooltip icon next to the attribute name.',
					'The <a>Metakeys and Attributes List</a> section will help you to choose the required metakey or attribute on which the product information is stored.These metakeys or attributes will furthur be used in <a>Product Export Settings</a> for listing products on etsy from woocommerce.',
					'For selecting the required metakey or attribute expand the <a>Metakeys and Attributes List</a> section enter the product name/keywords and list will be displayed under that . Select the metakey or attribute as per requirement and save settings.',
					'Configure the order related settings in <a>Order Import Settings</a>.',
					'Choose the Shiping profile in <a>Shipping Profiles</a> to be used while listing a product on etsy from woocommerce or you can also add new using <a>Add New</a> button.',
					'To automate the process related to inventory , order and import product sync , enable the features as per requirement in <a>Schedulers</a>.',
				);

				echo '<ul class="ced_etsy_instruction_list" type="disc">';
				foreach ( $instructions as $instruction ) {
					print_r( "<li>$instruction</li>" );
				}
				echo '</ul>';

				?>
	</div>
</div>
<?php
/**
 * Global Settings class. 
 */
class Gloabl_Settings
{
	
	public $shop_name;
	private $pre_saved_values;
	private $schedulers = array( 'ced_etsy_auto_import_schedule_job_', 'ced_etsy_inventory_scheduler_job_', 'ced_etsy_order_scheduler_job_' );
	public function __construct( $shop_name='' ) {
		$this->shop_name = $shop_name;
		if (empty( $this->shop_name )) {
			$this->shop_name = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
		}
		if ($this->shop_name) {
			$this->pre_saved_values = get_option('ced_etsy_global_settings', array());
			$this->pre_saved_values = isset( $this->pre_saved_values[$this->shop_name] ) ? $this->pre_saved_values[$this->shop_name] : array();
		}
		if (isset( $_POST['global_settings'])) {
			if ( ! isset( $_POST['global_settings_submit'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['global_settings_submit'] ) ), 'global_settings' ) ) {
				return;
			}
			$this->ced_etsy_save_settings();
		}
	}

	public function ced_schedule_events( $scheduler_name='', $times_stamp='' ){
		wp_schedule_event( time(), $times_stamp, $scheduler_name . $this->shop_name );
		update_option( $scheduler_name . $this->shop_name, $this->shop_name );
	}

	public function ced_clear_scheduled_hook( $hook_name = '' ) {
		wp_clear_scheduled_hook( $hook_name . $this->shop_name );
	}

	public function ced_etsy_save_settings(){

		$sanitized_array = ced_filter_input();
		$ced_etsy_global_settings = isset( $sanitized_array['ced_etsy_global_settings'] ) ? $sanitized_array['ced_etsy_global_settings'] : array();
		if (isset( $sanitized_array['ced_etsy_global_settings'] ) ) {
			foreach ($sanitized_array['ced_etsy_global_settings'] as $scheduler => $scheduler_value ) {
				// Un-schedule the events
				$this->ced_clear_scheduled_hook( $scheduler );
				// scheduling evens which want
				if (in_array( $scheduler, $this->schedulers )) {
					if ( isset( $this->schedulers[$scheduler] ) && 'on' === $this->schedulers[$scheduler] ) {
						$this->ced_schedule_events( $scheduler, 'ced_etsy_15min' );
					}
				}
			}
		}

		$marketplace_name           = isset( $_POST['marketplaceName'] ) ? sanitize_text_field( wp_unslash( $_POST['marketplaceName'] ) ) : 'etsy';
		$offer_settings_information = array();
		$array_to_save = array();
		if ( isset( $sanitized_array['ced_etsy_required_common'] ) ) {
			foreach ( ( $sanitized_array['ced_etsy_required_common'] ) as $key ) {
				isset( $sanitized_array[ $key ][0] ) ? $array_to_save['default'] = $sanitized_array[ $key ][0] : $array_to_save['default'] = '';

				if ( '_umb_' . $marketplace_name . '_subcategory' == $key ) {
					isset( $sanitized_array[ $key ] ) ? $array_to_save['default'] = $sanitized_array[ $key ] : $array_to_save['default'] = '';
				}

				isset( $sanitized_array[ $key . '_attribute_meta' ] ) ? $array_to_save['metakey'] = $sanitized_array[ $key . '_attribute_meta' ] : $array_to_save['metakey'] = 'null';
				$offer_settings_information['product_data'][ $key ]                               = $array_to_save;
			}
		}
		$this->pre_saved_values[ $this->shop_name ] = array_merge( $ced_etsy_global_settings, $offer_settings_information );
		update_option( 'ced_etsy_global_settings', $this->pre_saved_values );
	}

	public function settings_view( $shop_name = '' ) {
		$ced_h           = new Cedhandler();
		$ced_h->dir_name = '/admin/template/view/setting-view/';
		$ced_h->ced_require( 'ced-etsy-metakeys-template' );		
		$ced_h->dir_name = '/admin/template/view/render/';
		$ced_h->ced_require( 'class-ced-render-form' );
		// Rending forms. 
		$form  = new \Cedcommerce\view\render\Ced_Render_Form();
		echo $form->form_open('POST', '');
		$form->ced_nonce( 'global_settings', 'global_settings_submit' );
		$this->product_export_setting();
		$this->order_import_setting();
		$this->scheduler_setting_view();
		echo '<div class="left ced-button-wrapper" >'.$form->button( 'glb_stg_btn','button-primary', 'submit','global_settings', 'Save Settings' ).'</div>';
		echo $form->form_close();
	}

	public function order_import_setting(){
		?>
		<div class="ced_etsy_heading">
		<?php echo esc_html_e( get_etsy_instuctions_html( 'Order Import Settings' ) ); ?>
		<div class="ced_etsy_child_element">
			<?php wp_nonce_field( 'global_settings', 'global_settings_submit' ); ?>
			<?php

				// $ced_h->dir_name = '/admin/template/view/render/';
				// $ced_h->ced_require( 'class-ced-render-table' );
				// $table = new \Cedcommerce\view\render\Ced_Render_Table();
				// echo $table->table_open( 'wp-list-table fixed widefat ced_etsy_schedule_wrap' );
				// 	echo $table->table_body(
				// 		$table->tr(
				// 			$table->td(
				// 				$table->label('td_label', 'Default WooCommerce Order Status' ).
				// 			).
				// 			$table->td(
				// 				$table->label('td_label', 'Default WooCommerce Order Status' ).
				// 			)
				// 		)
				// 	);					
				// echo $table->table_close();

			?>
			<table class="wp-list-table fixed widefat ced_etsy_schedule_wrap">
				<tbody>
					<?php
					$ListType                   = isset( $this->pre_saved_values[ $this->shop_name ]['ced_fetch_etsy_order_by_status'] ) ? $this->pre_saved_values[ $this->shop_name ]['ced_fetch_etsy_order_by_status'] : '';
					$use_etsy_order_no          = isset( $this->pre_saved_values[ $this->shop_name ]['use_etsy_order_no'] ) ? $this->pre_saved_values[ $this->shop_name ]['use_etsy_order_no'] : '';
					$default_order_status       = isset( $this->pre_saved_values[ $this->shop_name ]['default_order_status'] ) ? $this->pre_saved_values[ $this->shop_name ]['default_order_status'] : '';
					$update_tracking            = isset( $this->pre_saved_values[ $this->shop_name ]['update_tracking'] ) ? $this->pre_saved_values[ $this->shop_name ]['update_tracking'] : '';
					?>
					<tr>
						<td>
							<label>
							<?php
							esc_html_e( 'Default WooCommerce Order Status', 'woocommerce-etsy-integration' );
							?>
								
							</label>
							<?php ced_etsy_tool_tip( 'Choose the order status in which you want to create etsy orders . Default is processing.' ); ?>
						</td>
						<?php
						$woo_order_statuses = wc_get_order_statuses();
						echo '<td>';
						echo "<select name='ced_etsy_global_settings[default_order_status]'>";
						echo "<option value=''>---Not mapped---</option>";
						foreach ( $woo_order_statuses as $woo_status => $woo_label ) {
							echo "<option value='" . esc_attr( $woo_status ) . "' " . ( ( isset( $default_order_status ) && $woo_status == $default_order_status ) ? 'selected' : '' ) . '>' . esc_attr( $woo_label ) . '</option>';
						}
						echo '</select>';
						?>
					</tr>
					<tr>
						<td>
							
							<label>
							<?php
							esc_html_e( 'Fetch Etsy Order By Status', 'woocommerce-etsy-integration' );

							?>
								
							</label>
							<?php
							ced_etsy_tool_tip( 'Choose the order status to be fetched from etsy . Default is all status and limit 15 latest orders.' );
							?>
						</td>
						<td>
							 <select name="ced_etsy_global_settings[ced_fetch_etsy_order_by_status]">
								<option <?php echo ( 'all' == $ListType ) ? 'selected' : ''; ?> value="all"><?php esc_html_e( 'All', 'woocommerce-etsy-integration' ); ?></option>
								<option <?php echo ( 'open' == $ListType ) ? 'selected' : ''; ?> value="open"><?php esc_html_e( 'Open', 'woocommerce-etsy-integration' ); ?></option>
								<option <?php echo ( 'unshipped' == $ListType ) ? 'selected' : ''; ?> value="unshipped"><?php esc_html_e( 'Unshipped', 'woocommerce-etsy-integration' ); ?></option>
								<option <?php echo ( 'unpaid' == $ListType ) ? 'selected' : ''; ?> value="unpaid"><?php esc_html_e( 'Unpaid', 'woocommerce-etsy-integration' ); ?></option>
								<option <?php echo ( 'completed' == $ListType ) ? 'selected' : ''; ?> value="completed"><?php esc_html_e( 'Completed', 'woocommerce-etsy-integration' ); ?></option>
								<option <?php echo ( 'processing' == $ListType ) ? 'selected' : ''; ?> value="processing"><?php esc_html_e( 'Processing', 'woocommerce-etsy-integration' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th>
							<label>
								<?php
								echo esc_html_e( 'Use Etsy Order Number', 'etsy-woocommerce-integration' );

								?>
								 </label>
								 <?php ced_etsy_tool_tip( 'Use etsy order number instead of woocommerce id when creating etsy orders in woocommerce.' ); ?>
						</th>
						<td>
							<label class="switch">
								<input type="checkbox" name="ced_etsy_global_settings[use_etsy_order_no]" <?php echo ( 'on' == $use_etsy_order_no ) ? 'checked=checked' : ''; ?>>
								<span class="slider round"></span>
							</label>
						</td>
					</tr>
					<tr>
						<th>
							<label>
								<?php
								echo esc_html_e( 'Auto Update Tracking', 'etsy-woocommerce-integration' );

								?>
								 </label>
								 <?php ced_etsy_tool_tip( 'Auto update tracking information on etsy if using <a href="https://woocommerce.com/products/shipment-tracking" target="_blank">Shipment Tracking</a> plugin.' ); ?>
						</th>
						<td>
							<label class="switch">
								<input type="checkbox" name="ced_etsy_global_settings[update_tracking]" <?php echo ( 'on' == $update_tracking ) ? 'checked=checked' : ''; ?>>
								<span class="slider round"></span>
							</label>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		</div>
		<?php
	}

	public function scheduler_setting_view(){
		?>
		<?php
			$this->shop_name            = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
			$auto_fetch_orders          = isset( $this->pre_saved_values[ $this->shop_name ]['ced_etsy_auto_fetch_orders'] ) ? $this->pre_saved_values[ $this->shop_name ]['ced_etsy_auto_fetch_orders'] : '';
			$auto_confirm_orders               = isset( $this->pre_saved_values[ $this->shop_name ]['ced_etsy_auto_import_product'] ) ? $this->pre_saved_values[ $this->shop_name ]['ced_etsy_auto_import_product'] : '';
			$auto_update_inventory_woo_to_etsy = isset( $this->pre_saved_values[ $this->shop_name ]['ced_etsy_auto_update_inventory'] ) ? $this->pre_saved_values[ $this->shop_name ]['ced_etsy_auto_update_inventory'] : '';
			$auto_update_stock_etsy_to_woo     = isset( $this->pre_saved_values[ $this->shop_name ]['ced_etsy_update_inventory_etsy_to_woo'] ) ? $this->pre_saved_values[ $this->shop_name ]['ced_etsy_update_inventory_etsy_to_woo'] : '';
			$ced_etsy_auto_upload_product      = isset( $this->pre_saved_values[ $this->shop_name ]['ced_etsy_auto_upload_product'] ) ? $this->pre_saved_values[ $this->shop_name ]['ced_etsy_auto_upload_product'] : '';
		?>
		<div class="ced_etsy_heading">
		<?php echo esc_html_e( get_etsy_instuctions_html( 'Schedulers' ) ); ?>
		<div class="ced_etsy_child_element">
			<?php wp_nonce_field( 'global_settings', 'global_settings_submit' ); ?>
			<table class="wp-list-table fixed widefat ced_etsy_schedule_wrap">
				<tbody>
					<tr>
						<th>
							<label><?php echo esc_html_e( 'Fetch Etsy Orders', 'etsy-woocommerce-integration' ); ?></label>
							<?php ced_etsy_tool_tip( 'Auto fetch etsy orders and create in woocommerce.' ); ?>
						</th>
						<td>
							<label class="switch">
								<input type="checkbox" name="ced_etsy_global_settings[ced_etsy_auto_fetch_orders]" <?php echo ( 'on' == $auto_fetch_orders ) ? 'checked=checked' : ''; ?>>
								<span class="slider round"></span>
							</label>
						</td>
					</tr>
					<tr>
						<th>
							<label>
								<?php
								echo esc_html_e( 'Upload Products To Etsy', 'etsy-woocommerce-integration' );
								$profile_page = admin_url( 'admin.php?page=ced_etsy&section=profiles-view&shop_name=' . $this->shop_name );
								?>
								 </label>
							<?php ced_etsy_tool_tip( 'Auto upload products from woocommerce to etsy. Please choose the categories/profile that you want to be uploaded automatically in <a href="' . $profile_page . '">Profile</a> section.' ); ?>
						</th>
						<td>
							<label class="switch">
								<input type="checkbox" name="ced_etsy_global_settings[ced_etsy_auto_upload_product]" <?php echo ( 'on' == $ced_etsy_auto_upload_product ) ? 'checked=checked' : ''; ?>>
								<span class="slider round"></span>
							</label>
						</td>
					</tr>
					<tr>
						<th>
							<label><?php echo esc_html_e( 'Update Inventory To Etsy', 'etsy-woocommerce-integration' ); ?></label>
							<?php ced_etsy_tool_tip( 'Auto update price and stock from woocommerce to etsy.' ); ?>
						</th>
						<td>
							<label class="switch">
								<input type="checkbox" name="ced_etsy_global_settings[ced_etsy_auto_update_inventory]" <?php echo ( 'on' == $auto_update_inventory_woo_to_etsy ) ? 'checked=checked' : ''; ?>>
								<span class="slider round"></span>
							</label>
						</td>
					</tr>
					<tr>
						<th>
							<label><?php echo esc_html_e( 'Import Products From Etsy', 'etsy-woocommerce-integration' ); ?></label>
							<?php ced_etsy_tool_tip( 'Auto import the active listings from etsy to woocommerce.' ); ?>
						</th>
						<td>
							<label class="switch">
								<input type="checkbox" name="ced_etsy_global_settings[ced_etsy_auto_import_product]" <?php echo ( 'on' == $auto_confirm_orders ) ? 'checked=checked' : ''; ?>>
								<span class="slider round"></span>
							</label>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		</div>
		<?php
	}
	public function product_export_setting(){
		?>
		<div class="ced_etsy_heading">
		<?php echo esc_html_e( get_etsy_instuctions_html( 'Product Export Settings' ) ); ?>
		<div class="ced_etsy_child_element">
			<?php wp_nonce_field( 'global_settings', 'global_settings_submit' ); ?>
			<table class="wp-list-table ced_etsy_global_settings">
				<tbody>
					<?php
					/**
					 * -------------------------------------
					 *  INCLUDING PRODUCT FIELDS ARRAY FILE
					 * -------------------------------------
					 */
					$this->shop_name = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';

					$product_fields_files = CED_ETSY_DIRPATH . 'admin/template/product-fields.php';
					if ( file_exists( $product_fields_files ) ) {
						require_once $product_fields_files;
					}

					$productFieldInstance = Ced_Etsy_Product_Fields::get_instance();
					$product_fields       = $productFieldInstance->get_custom_products_fields();
					$requiredInAnyCase    = array( '_umb_id_type', '_umb_id_val', '_umb_brand' );
					$marketPlace          = 'ced_etsy_required_common';
					$productID            = 0;
					$categoryID           = '';
					$indexToUse           = 0;
					$attributes           = wc_get_attribute_taxonomies();
					$attr_options         = array();
					$added_meta_keys      = get_option( 'ced_etsy_selected_metakeys', array() );
					$added_meta_keys      = array_merge( $added_meta_keys, array( '_woocommerce_title', '_woocommerce_short_description', '_woocommerce_description' ) );
					$select_dropdown_html = '';

					if ( $added_meta_keys && count( $added_meta_keys ) > 0 ) {
						foreach ( $added_meta_keys as $meta_key ) {
							$attr_options[ $meta_key ] = $meta_key;
						}
					}
					if ( ! empty( $attributes ) ) {
						foreach ( $attributes as $attributes_object ) {
							$attr_options[ 'umb_pattr_' . $attributes_object->attribute_name ] = $attributes_object->attribute_label;
						}
					}

					if ( ! empty( $product_fields ) ) {

						?>
							<tr>
								<td><b>Etsy Attribute</b></td>
								<td><b>Default Value</b></td>
								<td><b>Pick Value From</b></td>
							</tr>
						<?php

						$product_specific_attribute_key = get_option( 'ced_etsy_product_specific_attribute_key', array() );
						foreach ( $product_fields as $field_data ) {
							echo '<tr>';
							// Don't show category specifiction option
							if ( '_umb_etsy_category' == $field_data['id'] || '_ced_etsy_shipping_profile' == $field_data['id'] ) {
								continue;
							}

							$check    = false;
							$field_id = isset( $field_data['id'] ) ? $field_data['id'] : '';
							if ( empty( $product_specific_attribute_key ) ) {
								$product_specific_attribute_key = array( $field_id );
							} else {
								foreach ( $product_specific_attribute_key as $key => $product_key ) {
									if ( $product_key == $field_id ) {
										$check = true;
										break;
									}
								}
								if ( false == $check ) {
									$product_specific_attribute_key[] = $field_id;
								}
							}

							$ced_etsy_global_data = get_option( 'ced_etsy_global_settings', array() );
							if ( ! empty( $ced_etsy_global_data ) ) {
								$data = isset( $ced_etsy_global_data[ $this->shop_name ]['product_data'] ) ? $ced_etsy_global_data[ $this->shop_name ]['product_data'] : array();
							}
							update_option( 'ced_etsy_product_specific_attribute_key', $product_specific_attribute_key );
							echo '<tr class="form-field _umb_id_type_field ">';
							$label        = isset( $field_data['fields']['label'] ) ? $field_data['fields']['label'] : '';
							$field_id     = trim( $field_id, '_' );
							$category_id  = '';
							$product_id   = '';
							$market_place = 'ced_etsy_required_common';
							$description  = isset( $field_data['fields']['description'] ) ? $field_data['fields']['description'] : '';
							$required     = isset( $field_data['fields']['is_required'] ) ? (bool) $field_data['fields']['is_required'] : '';
							$index_to_use = 0;
							$default      = isset( $data[ $field_data['fields']['id'] ]['default'] ) ? $data[ $field_data['fields']['id'] ]['default'] : '';

							$field_value = array(
								'case'  => 'profile',
								'value' => $default,
							);

							if ( '_text_input' == $field_data['type'] ) {
								$productFieldInstance->renderInputTextHTML( $field_id, $label, $category_id, $product_id, $market_place, $description, $index_to_use, $field_value, $required );

							} elseif ( '_select' == $field_data['type'] ) {
								$value_for_dropdown = $field_data['fields']['options'];
								$productFieldInstance->renderDropdownHTML( $field_id, $label, $value_for_dropdown, $category_id, $product_id, $market_place, $description, $index_to_use, $field_value, $required );
							}
							echo '<td>';
							$previous_selected_value = 'null';
							if ( isset( $data[ $field_data['fields']['id'] ]['metakey'] ) && 'null' != $data[ $field_data['fields']['id'] ]['metakey'] ) {
								$previous_selected_value = $data[ $field_data['fields']['id'] ]['metakey'];
							}
							$select_id = $field_data['fields']['id'] . '_attribute_meta';
							?>
							<select id="<?php echo esc_attr( $select_id ); ?>" name="<?php echo esc_attr( $select_id ); ?>">
								<option value="null" selected> -- select -- </option>
								<?php
								if ( is_array( $attr_options ) ) {
									foreach ( $attr_options as $attr_key => $attr_name ) :
										if ( trim( $previous_selected_value ) == $attr_key ) {
											$selected = 'selected';
										} else {
											$selected = '';
										}
										?>
										<option value="<?php echo esc_attr( $attr_key ); ?>"<?php echo esc_attr( $selected ); ?>><?php echo esc_attr( $attr_name ); ?></option>
										<?php
									endforeach;
								}
								?>
							</select>
							<?php
							echo '</td>';
							echo '</tr>';
						}
					}
					?>
				</tbody>
			</table>
		</div>
		</div>
		<?php
	}
}

$global_setting = new Gloabl_Settings();
$global_setting->settings_view();