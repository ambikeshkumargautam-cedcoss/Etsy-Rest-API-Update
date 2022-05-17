<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
Cedhandler::ced_header();
require_once CED_ETSY_DIRPATH . 'admin/template/product-fields.php';
$profileID = isset( $_GET['profileID'] ) ? sanitize_text_field( wp_unslash( $_GET['profileID'] ) ) : '';

global $wpdb;
$shop_name = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
$tableName = $wpdb->prefix . 'ced_etsy_profiles';

if ( isset( $_POST['add_meta_keys'] ) || isset( $_POST['ced_etsy_profile_save_button'] ) ) {

	if ( ! isset( $_POST['profile_settings_submit'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['profile_settings_submit'] ) ), 'ced_etsy_profile_save_button' ) ) {
		return;
	}
	$sanitized_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING );
	$profileName     = $sanitized_array['ced_etsy_profile_name'];
	$marketplaceName = isset( $sanitized_array['marketplaceName'] ) ? $sanitized_array['marketplaceName'] : 'all';
	foreach ( $sanitized_array['ced_etsy_required_common'] as $key ) {
		$arrayToSave = array();
		isset( $sanitized_array[ $key ][0] ) ? $arrayToSave['default'] = $sanitized_array[ $key ][0] : $arrayToSave['default'] = '';
		if ( '_umb_' . $marketplaceName . '_subcategory' == $key ) {
			isset( $sanitized_array[ $key ] ) ? $arrayToSave['default'] = $sanitized_array[ $key ] : $arrayToSave['default'] = '';
		}
		if ( '_umb_etsy_category' == $key && empty( $profileID ) ) {
			$profileCategoryNames = array();
			for ( $i = 1; $i < 8; $i++ ) {
				$profileCategoryNames[] = isset( $sanitized_array[ 'ced_etsy_level' . $i . '_category' ] ) ? $sanitized_array[ 'ced_etsy_level' . $i . '_category' ] : '';
			}
			$CategoryNames = array();
			foreach ( $profileCategoryNames as $key1 => $value1 ) {
				$CategoryNames[] = explode( ',', $value1[0] );
			}
			foreach ( $CategoryNames as $key2 => $value2 ) {
				if ( ! empty( $CategoryNames[ $key2 ][0] ) ) {
					$profile_category_id = $CategoryNames[ $key2 ][0];
				}
			}
			$category_id = $profile_category_id;
			isset( $sanitized_array[ $key ][0] ) ? $arrayToSave['default'] = $category_id : $arrayToSave['default'] = '';

		}
		isset( $sanitized_array[ $key . '_attibuteMeta' ] ) ? $arrayToSave['metakey'] = $sanitized_array[ $key . '_attibuteMeta' ] : $arrayToSave['metakey'] = 'null';
		$updateinfo[ $key ] = $arrayToSave;
	}

	$updateinfo['selected_product_id']   = isset( $sanitized_array['selected_product_id'] ) ? sanitize_text_field( wp_unslash( $sanitized_array['selected_product_id'] ) ) : '';
	$updateinfo['selected_product_name'] = isset( $sanitized_array['ced_sears_pro_search_box'] ) ? sanitize_text_field( wp_unslash( $sanitized_array['ced_sears_pro_search_box'] ) ) : '';
	$updateinfo                          = json_encode( $updateinfo );
	if ( empty( $profileID ) ) {
		$profileCategoryNames = array();
		for ( $i = 1; $i < 8; $i++ ) {
			$profileCategoryNames[] = isset( $sanitized_array[ 'ced_etsy_level' . $i . '_category' ] ) ? $sanitized_array[ 'ced_etsy_level' . $i . '_category' ] : '';
		}
		$CategoryNames = array();
		foreach ( $profileCategoryNames as $key => $value ) {
			$CategoryNames[] = explode( ',', $value[0] );
			if ( ! empty( $CategoryNames[ $key ][1] ) ) {
				$CategoryName .= $CategoryNames[ $key ][1] . '-->';
			}
		}
		$catl        = strlen( $CategoryName );
		$profileName = substr( $CategoryName, 0, -3 );
		foreach ( $CategoryNames as $key1 => $value1 ) {
			if ( ! empty( $CategoryNames[ $key1 ][0] ) ) {
				$profile_category_id = $CategoryNames[ $key1 ][0];
			}
		}
		$profile_category_id = $profile_category_id;


		$profileDetails = array(
			'profile_name'   => $profileName,
			'profile_status' => 'active',
			'profile_data'   => $updateinfo,
			'shop_name'      => $shop_name,
		);

		global $wpdb;
		$profileTableName = $wpdb->prefix . 'ced_etsy_profiles';
		$wpdb->insert( $profileTableName, $profileDetails );
		$profileId = $wpdb->insert_id;

		$profile_edit_url = admin_url( 'admin.php?page=ced_etsy&profileID=' . $profileId . '&section=profiles-view&panel=edit&shop_name=' . $shop_name );
		header( 'location:' . $profile_edit_url . '' );
	} elseif ( $profileID ) {
		$wpdb->update(
			$tableName,
			array(
				'profile_name'   => $profileName,
				'profile_status' => 'Active',
				'profile_data'   => $updateinfo,
			),
			array( 'id' => $profileID )
		);
	}
}
$etsyFirstLevelCategories = @file_get_contents( CED_ETSY_DIRPATH . 'admin/lib/json/categoryLevel-1.json' );
$etsyFirstLevelCategories = json_decode( $etsyFirstLevelCategories, true );

$profile_data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_etsy_profiles WHERE `id`=%s ", $profileID ), 'ARRAY_A' );

if ( ! empty( $profile_data ) ) {
	$profile_category_data = json_decode( $profile_data[0]['profile_data'], true );
	$client                = ced_etsy_getOauthClientObject( $shop_name );
	$profile_category_data = isset( $profile_category_data ) ? $profile_category_data : '';
	$profile_category_id   = isset( $profile_category_data['_umb_etsy_category']['default'] ) ? (int) $profile_category_data['_umb_etsy_category']['default'] : '';

	$profile_data              = isset( $profile_data[0] ) ? $profile_data[0] : $profile_data;
	$success                   = $client->CallAPI( "https://openapi.etsy.com/v2/taxonomy/seller/{$profile_category_id}/properties", 'GET', array( 'taxonomy_id' => $profile_category_id ), array( 'FailOnAccessError' => true ), $getTaxonomyNodeProperties );
	$getTaxonomyNodeProperties = json_decode( json_encode( $getTaxonomyNodeProperties ), true );
	$action                    = "application/seller-taxonomy/nodes/{$profile_category_id}/properties";
	$getTaxonomyNodeProperties = etsy_request()->get( $action, $shop_name );
	$getTaxonomyNodeProperties = $getTaxonomyNodeProperties['results'];
	$productFieldInstance      = Ced_Etsy_Product_Fields::get_instance();
	$taxonomyList              = $productFieldInstance->get_taxonomy_node_properties( $getTaxonomyNodeProperties );
}
$attributes    = wc_get_attribute_taxonomies();
$attrOptions   = array();
$addedMetaKeys = get_option( 'ced_etsy_selected_metakeys', array() );
$addedMetaKeys = array_merge( $addedMetaKeys, array( '_woocommerce_title', '_woocommerce_short_description', '_woocommerce_description' ) );

if ( $addedMetaKeys && count( $addedMetaKeys ) > 0 ) {
	foreach ( $addedMetaKeys as $metaKey ) {
		$attrOptions[ $metaKey ] = $metaKey;
	}
}
if ( ! empty( $attributes ) ) {
	foreach ( $attributes as $attributesObject ) {
		$attrOptions[ 'umb_pattr_' . $attributesObject->attribute_name ] = $attributesObject->attribute_label;
	}
}


/* select dropdown setup */
ob_start();
$fieldID  = '{{*fieldID}}';
$selectId = $fieldID . '_attibuteMeta';
echo '<select id="' . esc_attr( $selectId ) . '" name="' . esc_attr( $selectId ) . '">';
echo '<option value="null"> -- select -- </option>';
if ( is_array( $attrOptions ) ) {
	foreach ( $attrOptions as $attrKey => $attrName ) :
		echo '<option value="' . esc_attr( $attrKey ) . '">' . esc_attr( $attrName ) . '</option>';
	endforeach;
}
echo '</select>';
$selectDropdownHTML   = ob_get_clean();
$productFieldInstance = Ced_Etsy_Product_Fields::get_instance();
$fields               = $productFieldInstance->get_custom_products_fields();
?>

<?php do_action( 'ced_etsy_render_meta_keys_settings' ); ?>
<form action="" method="post">
	<?php wp_nonce_field( 'ced_etsy_profile_save_button', 'profile_settings_submit' ); ?>

	<div class="ced_etsy_heading">
		<?php echo esc_html_e( get_etsy_instuctions_html( 'BASIC INFORMATION' ) ); ?>
		<div class="ced_etsy_child_element">
			<table class="wp-list-table fixed widefat ced_etsy_config_table">
				<tr>
					<td>
						<label><?php esc_html_e( 'Profile Name', 'woocommerce-etsy-integration' ); ?></label>
						<?php ced_etsy_tool_tip( 'Profile name.' ); ?>
					</td>
					<?php

					if ( isset( $profile_data['profile_name'] ) ) {
						?>
						<td>
							<input type="text" name="ced_etsy_profile_name" value="<?php echo esc_attr( $profile_data['profile_name'] ); ?>">
						</td>
					</tr>
						<?php
					}
					?>
				<tr>
				</table>
			</div>
		</div>
		<div class="ced_etsy_heading">
			<?php echo esc_html_e( get_etsy_instuctions_html( 'Product Export Settings' ) ); ?>
			<div class="ced_etsy_child_element">
				<table class="wp-list-table ced_etsy_global_settings">
					<tr>
						<td><b>Etsy Attribute</b></td>
						<td><b>Default Value</b></td>
						<td><b>Pick Value From</b></td>
					</tr>

					<?php
					$requiredInAnyCase          = array( '_umb_id_type', '_umb_id_val', '_umb_brand' );
					$global_settings_field_data = get_option( 'ced_etsy_global_settings', '' );
					$marketPlace                = 'ced_etsy_required_common';
					$productID                  = 0;
					$categoryID                 = '';
					$indexToUse                 = 0;

					if ( ! empty( $profile_data ) ) {
						$data = json_decode( $profile_data['profile_data'], true );
					}
					foreach ( $fields as $value ) {
						$isText   = true;
						$field_id = trim( $value['fields']['id'], '_' );
						if ( in_array( $value['fields']['id'], $requiredInAnyCase ) ) {
							$attributeNameToRender  = ucfirst( $value['fields']['label'] );
							$attributeNameToRender .= '<span class="ced_etsy_wal_required"> [ Required ]</span>';
						} else {
							$attributeNameToRender = ucfirst( $value['fields']['label'] );
						}
						$is_required = isset( $value['fields']['is_required'] ) ? $value['fields']['is_required'] : false;
						$default     = isset( $data[ $value['fields']['id'] ]['default'] ) ? $data[ $value['fields']['id'] ]['default'] : '';
						echo '<tr class="form-field _umb_id_type_field ">';
						if ( '_select' == $value['type'] ) {
							$valueForDropdown = $value['fields']['options'];
							if ( '_umb_id_type' == $value['fields']['id'] ) {
								unset( $valueForDropdown['null'] );
							}
							$valueForDropdown = apply_filters( 'ced_etsy_alter_data_to_render_on_profile', $valueForDropdown, $field_id );
							$productFieldInstance->renderDropdownHTML(
								$field_id,
								$attributeNameToRender,
								$valueForDropdown,
								$categoryID,
								$productID,
								$marketPlace,
								$value['fields']['description'],
								$indexToUse,
								array(
									'case'  => 'profile',
									'value' => $default,
								),
								$is_required
							);
							$isText = true;

						} elseif ( '_text_input' == $value['type'] ) {
							$productFieldInstance->renderInputTextHTML(
								$field_id,
								$attributeNameToRender,
								$categoryID,
								$productID,
								$marketPlace,
								$value['fields']['description'],
								$indexToUse,
								array(
									'case'  => 'profile',
									'value' => $default,
								),
								$is_required
							);
						} elseif ( '_checkbox' == $value['type'] ) {
							$productFieldInstance->rendercheckboxHTML(
								$field_id,
								$attributeNameToRender,
								$categoryID,
								$productID,
								$marketPlace,
								$value['fields']['description'],
								$indexToUse,
								array(
									'case'  => 'profile',
									'value' => $default,
								),
								$is_required
							);
							$isText = true;
						} elseif ( '_hidden' == $value['type'] ) {

							$profile_category_id = isset( $profile_category_id ) ? $profile_category_id : '';
							$productFieldInstance->renderInputTextHTMLhidden(
								$field_id,
								$attributeNameToRender,
								$categoryID,
								$productID,
								$marketPlace,
								$value['fields']['description'],
								$indexToUse,
								array(
									'case'  => 'profile',
									'value' => $profile_category_id,
								),
								$is_required
							);
							$isText = false;
						}

						echo '<td>';
						if ( $isText ) {
							$previousSelectedValue = 'null';
							if ( isset( $data[ $value['fields']['id'] ]['metakey'] ) && ! empty( $data[ $value['fields']['id'] ]['metakey'] ) ) {
								$previousSelectedValue = $data[ $value['fields']['id'] ]['metakey'];
							}
							$updatedDropdownHTML = str_replace( '{{*fieldID}}', $value['fields']['id'], $selectDropdownHTML );
							$updatedDropdownHTML = str_replace( 'value="' . $previousSelectedValue . '"', 'value="' . $previousSelectedValue . '" selected="selected"', $updatedDropdownHTML );
							print_r( $updatedDropdownHTML );
						}
						echo '</td>';
						echo '</tr>';
					}
					?>
				</table>
			</div>
		</div>
		<div class="ced_etsy_heading">
			<?php echo esc_html_e( get_etsy_instuctions_html( 'Category specific' ) ); ?>
		<div class="ced_etsy_child_element">
			<table class="wp-list-table ced_etsy_global_settings">
				<tr>
						<td><b>Etsy Attribute</b></td>
						<td><b>Default Value</b></td>
						<td><b>Pick Value From</b></td>
					</tr>
				<?php
				if ( ! empty( $profileID ) ) {

					if ( ! empty( $taxonomyList ) ) {
						foreach ( $taxonomyList as $key => $value ) {

							$isText   = true;
							$field_id = trim( $value['fields']['id'], '_' );
							$default  = isset( $data[ $value['fields']['id'] ] ) ? $data[ $value['fields']['id'] ] : '';
							$default  = isset( $default['default'] ) ? $default['default'] : '';
							echo '<tr class="form-field _umb_brand_field ">';
							if ( '_select' == $value['type'] ) {
								$valueForDropdown     = $value['fields']['options'];
								$tempValueForDropdown = array();
								foreach ( $valueForDropdown as $key => $_value ) {
									$tempValueForDropdown[ $key ] = $_value;
								}
								$valueForDropdown = $tempValueForDropdown;

								$productFieldInstance->renderDropdownHTML(
									$field_id,
									ucfirst( $value['fields']['label'] ),
									$valueForDropdown,
									$categoryID,
									$productID,
									$marketPlace,
									$value['fields']['description'],
									$indexToUse,
									array(
										'case'  => 'profile',
										'value' => $default,
									)
								);
								$isText = true;
							} else {
								continue;
								$productFieldInstance->renderInputTextHTML(
									$field_id,
									ucfirst( $value['fields']['label'] ),
									$categoryID,
									$productID,
									$marketPlace,
									$value['fields']['description'],
									$indexToUse,
									array(
										'case'  => 'profile',
										'value' => $default,
									)
								);
							}

							echo '<td>';
							if ( $isText ) {
								$previousSelectedValue = 'null';
								if ( isset( $data[ $value['fields']['id'] ] ) && 'null' != $data[ $value['fields']['id'] ] ) {
									$previousSelectedValue = $data[ $value['fields']['id'] ]['metakey'];
								}
								$updatedDropdownHTML = str_replace( '{{*fieldID}}', $value['fields']['id'], $selectDropdownHTML );
								$updatedDropdownHTML = str_replace( 'value="' . $previousSelectedValue . '"', 'value="' . $previousSelectedValue . '" selected="selected"', $updatedDropdownHTML );
								print_r( $updatedDropdownHTML );
							}
							echo '</td>';
							echo '</tr>';
						}
					}
				}
				?>

			</table>
		</div>
		</div>
		<div class="ced-button-wrapper">
			<button  name="ced_etsy_profile_save_button" class="button-primary"><?php esc_html_e( 'Save Profile', 'woocommerce-etsy-integration' ); ?></button>			
		</div>

	</form>
