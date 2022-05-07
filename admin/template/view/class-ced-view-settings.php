<?php
/**
 * Class Ced View Settings.
 *
 * @package Settings view
 * Class Ced View Settings is under the Cedcommerce\View\Settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * The Settings specific class..
 *
 * Ced_View_Settings class is rending fields which are required to show on the settings tab.
 *
 * @package    Woocommmerce_Etsy_Integration
 * @subpackage Woocommmerce_Etsy_Integration/View/Settings
 */
class Ced_View_Settings {
	/**
	 * The Current shop name which currently active now.
	 *
	 * @since    2.1.3
	 * @var      string    $plugin_name   The shop Name.
	 */
	public $shop_name;
	/**
	 * Previously saved values in DB.
	 *
	 * @since    1.0.0
	 * @var      string    $pre_saved_values    The PresavedValues is pre-saved values in DB.
	 */
	private $pre_saved_values;
	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $schedulers = array( 'ced_etsy_auto_import_schedule_job_', 'ced_etsy_inventory_scheduler_job_', 'ced_etsy_order_scheduler_job_' );
	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $tabs    The ID of this plugin.
	 */
	private $tabs = array( 
		'order_imoprt_settings'  => 'Order Import Settings',
		'scheduler_setting_view' => 'Schedulers',
	);

	/**
	 * Instializing all the required variations and functions.
	 * 
	 * @since    2.1.3
	 *    string    $plugin_name    The name of the plugin.
	 */
	public function __construct( $shop_name='' ) {
		/**
		 * Show header on the top of tabs.
		 *
		 * @since    1.0.0
		 * @var      string    $plugin_name    The ID of this plugin.
		 */
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
					print_r( "<li> $instruction</li>" );
				}
				echo '</ul>';

				?>
			</div>
		</div>
		<?php
		$this->shop_name = $shop_name;
		if ( empty( $this->shop_name ) ) {
			$this->shop_name = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
		}
		if ( $this->shop_name ) {
			$this->pre_saved_values = get_option( 'ced_etsy_global_settings', array() );
			$this->pre_saved_values = isset( $this->pre_saved_values[ $this->shop_name ] ) ? $this->pre_saved_values[ $this->shop_name ] : array();
		}
		/**
		 * Get submit form here.
		 */

		if ( isset( $_POST['global_settings'] ) ) {
			if ( ! isset( $_POST['global_settings_submit'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['global_settings_submit'] ) ), 'global_settings' ) ) {
				return;
			}
			/**
			 * Save Settings in DB.
			 *
			 * @since    2.1.3
			 */
			$this->ced_etsy_save_settings();
		}
	}

	/**
	 * Schedule events for automate the scheduling of import and export.
	 *
	 * @since    2.1.3
	 * @var      string    $scheduler_name    The Scheduler hook name .
	 * @var      string    $times_stamp    The given times stamp.
	 */
	public function ced_schedule_events( $scheduler_name = '', $times_stamp = '' ) {
		wp_schedule_event( time(), $times_stamp, $scheduler_name . $this->shop_name );
		update_option( $scheduler_name . $this->shop_name, $this->shop_name );
	}

	/**
	 * Clear Schedule events for automate the scheduling of import and export.
	 *
	 * @since    2.1.3
	 * @var      string    $hook_name    The Scheduler hook name.
	 */
	public function ced_clear_scheduled_hook( $hook_name = '' ) {
		wp_clear_scheduled_hook( $hook_name . $this->shop_name );
	}

	/**
	 * Save setting values in Db.
	 *
	 * @since    2.1.3
	 */
	public function ced_etsy_save_settings() {

		$sanitized_array          = ced_filter_input();
		$ced_etsy_global_settings = isset( $sanitized_array['ced_etsy_global_settings'] ) ? $sanitized_array['ced_etsy_global_settings'] : array();
		if ( isset( $sanitized_array['ced_etsy_global_settings'] ) ) {
			foreach ( $sanitized_array['ced_etsy_global_settings'] as $scheduler => $scheduler_value ) {
				// Un-schedule the events.
				$this->ced_clear_scheduled_hook( $scheduler );
				// scheduling evens.
				if ( in_array( $scheduler, $this->schedulers ) ) {
					if ( isset( $this->schedulers[$scheduler] ) && 'on' === $this->schedulers[$scheduler] ) {
						$this->ced_schedule_events( $scheduler, 'ced_etsy_15min' );
					}
				}
			}
		}

		$marketplace_name           = isset( $_POST['marketplaceName'] ) ? sanitize_text_field( wp_unslash( $_POST['marketplaceName'] ) ) : 'etsy';
		$offer_settings_information = array();
		$array_to_save              = array();
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
		/**
		 * Getting older settings values merging with new settings values.
		 *
		 * @since    2.0.8
		 */
		$settings                     = get_option( 'ced_etsy_global_settings', array() );
		$settings[ $this->shop_name ] = array_merge( $ced_etsy_global_settings, $offer_settings_information );
		update_option( 'ced_etsy_global_settings', $settings );

	}

	/**
	 * Showing setting values in form.
	 *
	 * @since    2.0.8
	 */
	public function settings_view( $shop_name = '' ) {
		$ced_h           = new Cedhandler();
		$ced_h->dir_name = '/admin/template/view/';
		$ced_h->ced_require( 'ced-etsy-metakeys-template' );		
		$ced_h->dir_name = '/admin/template/view/render/';
		$ced_h->ced_require( 'class-ced-render-form' );
		// Rending forms.
		$form  = new \Cedcommerce\view\render\Ced_Render_Form();
		echo $form->form_open('POST', '');
		$form->ced_nonce( 'global_settings', 'global_settings_submit' );
		$this->product_export_setting();
		foreach ($this->tabs as $tab_key => $tab_name) {
			$this->ced_etsy_show_setting_tabs( $tab_name, $tab_key );
		}
		echo '<div class="left ced-button-wrapper" >'.$form->button( 'glb_stg_btn','button-primary', 'submit','global_settings', 'Save Settings' ).'</div>';
		echo $form->form_close();
	}
	/**
	 * Show settings tabs using array.
	 *
	 * @since    2.1.3
	 */
	private function ced_etsy_show_setting_tabs( $tab_name = '', $tab_key = '' ){
		?>
		<div class="ced_etsy_heading">
			<?php echo esc_html_e( get_etsy_instuctions_html( $tab_name ) ); ?>
			<div class="ced_etsy_child_element">
				<?php wp_nonce_field( 'global_settings', 'global_settings_submit' ); ?>
				<?php
				$fields = $this->ced_etsy_all_settings_fields();
				$fields = isset( $fields[$tab_key] ) ? $fields[$tab_key] : array();
				echo $this->ced_etsy_render_table( $fields );
				?>				
			</div>
		</div>
		<?php
	}
	/**
	 * Reder Table into forms.
	 *
	 * @since    2.0.8
	 */
	private function ced_etsy_render_table( $table_array = array() ){
		$ced_h           = new Cedhandler();
		$stored_value    = isset( $this->pre_saved_values[ $this->shop_name ] ) ? $this->pre_saved_values[ $this->shop_name ] : $this->pre_saved_values;
		$ced_h->dir_name = '/admin/template/view/render/';
		$ced_h->ced_require('class-ced-render-table');
		$table = new \Cedcommerce\view\render\Ced_Render_Table();
		echo $table->table_open( 'wp-list-table fixed widefat ced_etsy_schedule_wrap' );
		$table_array = isset( $table_array ) ? $table_array : array();
		$prep_tr = '';
		$table_tds = '';
			foreach ($table_array as $table_values ) {
				// echo "<br>Keys :-". $table_values['name'];
				$is_value = isset( $stored_value[$table_values['name']] ) ? $stored_value[$table_values['name']] : '';
				// echo "Is value :-".$is_value;
				$table_ids = '';
				$is_checked = '';
				$table_tds .= '<tr>';
				if ( 'on' === $is_value ) {
					$is_checked = 'checked';		
				}					
				$table_ids .= $table->label( '', $table_values['label'],  $table_values['tooltip'] );
				$table_tds .= $table->th( $table_ids );
				if ( 'select' === $table_values['type'] ) {
					$table_tds .= $table->td( $table->select( 'ced_etsy_global_settings['.$table_values['name'].']', $table_values['options'] ), $is_value );
				}
				if ('check' === $table_values['type']  ) {
					$table_tds .= $table->td( $table->label( 'switch', $table->check_box( 'ced_etsy_global_settings['.$table_values['name'].']', $is_checked ) ) );
				}
				$table_tds .= '</tr>';
			}
		echo ( $table->table_body( $table_tds ));					
		echo $table->table_close();
	}

	/**
	 * All the Required settings tabs ans sub-tabs.
	 *
	 * @since    2.0.8
	 */
	public function ced_etsy_all_settings_fields() {
		return array(
			'order_imoprt_settings' => array(
				array(
					'label'   => __( 'Default WooCommerce Order Status', 'woocommerce-etsy-integration' ),
					'tooltip' => 'Choose the order status in which you want to create etsy orders . Default is processing.',
					'type'    => 'select',
					'name'    => 'default_order_status',
					'options' => wc_get_order_statuses(),
				),array(
					'label'   => __( 'Fetch Etsy Order By Status', 'woocommerce-etsy-integration' ),
					'tooltip' => 'Choose the order status to be fetched from etsy . Default is all status and limit 15 latest orders.',
					'type'    => 'select',
					'name'    => 'ced_fetch_etsy_order_by_status',
					'options' => array(
						'all'        => __( 'All', 'woocommerce-etsy-integration' ),
						'open'       => __( 'Open', 'woocommerce-etsy-integration' ),
						'unshipped'  => __( 'Unshipped', 'woocommerce-etsy-integration' ),
						'unpaid'     => __( 'Unshipped', 'woocommerce-etsy-integration' ),
						'completed'  => __( 'Completed', 'woocommerce-etsy-integration' ),
						'processing' => __( 'Processing', 'woocommerce-etsy-integration' )
					),
				),array(
					'label'   => __( 'Use Etsy Order Number', 'etsy-woocommerce-integration' ),
					'tooltip' => 'Use etsy order number instead of woocommerce id when creating etsy orders in woocommerce.',
					'type'    => 'check',
					'name'    => 'use_etsy_order_no',
					'options' => '',
				),array(
					'label'   => __( 'Auto Update Tracking', 'etsy-woocommerce-integration' ),
					'tooltip' => 'Auto update tracking information on etsy if using <a href="https://woocommerce.com/products/shipment-tracking" target="_blank">Shipment Tracking</a> plugin.',
					'type'    => 'check',
					'name'    => 'update_tracking',
					'options' => ''
				),
			),
			'scheduler_setting_view' => array(
				array(
					'label'   => __( 'Fetch Etsy Orders', 'woocommerce-etsy-integration' ),
					'tooltip' => 'Auto fetch etsy orders and create in woocommerce.',
					'type'    => 'check',
					'name'    => 'ced_etsy_auto_fetch_orders',
					'options' => '',
				),array(
					'label'   => __( 'Upload Products To Etsy', 'woocommerce-etsy-integration' ),
					'tooltip' => 'Auto upload products from woocommerce to etsy. Please choose the categories/profile that you want to be uploaded automatically in <a href="' . admin_url( 'admin.php?page=ced_etsy&section=profiles-view&shop_name=' . $this->shop_name ) . '">Profile</a> section.',
					'type'    => 'check',
					'name'    => 'ced_etsy_auto_upload_product',
					'options' => '',
				),array(
					'label'   => __( 'Update Inventory To Etsy', 'etsy-woocommerce-integration' ),
					'tooltip' => 'Auto update price and stock from woocommerce to etsy.',
					'type'    => 'check',
					'name'    => 'ced_etsy_update_inventory_etsy_to_woo',
					'options' => '',
				),array(
					'label'   => __( 'Import Products From Etsy', 'etsy-woocommerce-integration' ),
					'tooltip' => 'Auto import the active listings from etsy to woocommerce.',
					'type'    => 'check',
					'name'    => 'ced_etsy_auto_import_product',
					'options' => ''
				)
			)
		);
	}

	/**
	 * Product export setting view.
	 *
	 * @since    2.0.8
	 */
	public function product_export_setting() {
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

$global_setting = new Ced_View_Settings();
$global_setting->settings_view();