<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

Cedhandler::ced_header();
$saved_etsy_details = get_option( 'ced_etsy_details', array() );

$marketPlaceName = 'etsy';

$activeShop = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
/*GET COUNTRIES LIST FOR SHIPPING TEMPLATE */
$saved_etsy_details = get_option( 'ced_etsy_details', array() );
$shopDetails        = $saved_etsy_details[ $activeShop ];
$user_id            = isset( $shopDetails['details']['user_id'] ) ? $shopDetails['details']['user_id'] : '';
$shop_id            = isset( $shopDetails['details']['shop_id'] ) ? $shopDetails['details']['shop_id'] : '';

$countries = file_get_contents( CED_ETSY_DIRPATH . 'admin/etsy/lib/json/countries.json' );
if ( '' != $countries ) {
	$countries = json_decode( $countries, true );
}

$regions = file_get_contents( CED_ETSY_DIRPATH . 'admin/etsy/lib/json/regions.json' );
if ( '' != $regions ) {
	$regions = json_decode( $regions, true );
}

$country_list = array();
if ( ! empty( $countries ) ) {
	foreach ( $countries['results'] as $key => $value ) {
		$country_list[ $value['iso_country_code'] ] = $value['name'];
	}
}
$region_list = array();
if ( ! empty( $regions ) ) {
	foreach ( $regions['results'] as $key => $value ) {
		$region_list[ $value['region_id'] ] = $value['region_name'];
	}
}

if ( isset( $_POST['shipping_settings'] ) ) {


	if ( ! isset( $_POST['shipping_settings_submit'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['shipping_settings_submit'] ) ), 'shipping_settings' ) ) {
		return;
	}

	$title                   = isset( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : '';
	$origin_country_iso      = isset( $_POST['origin_country_iso'] ) ? sanitize_text_field( $_POST['origin_country_iso'] ) : '';
	$primary_cost            = isset( $_POST['primary_cost'] ) ? sanitize_text_field( $_POST['primary_cost'] ) : '';
	$secondary_cost          = isset( $_POST['secondary_cost'] ) ? sanitize_text_field( $_POST['secondary_cost'] ) : '';
	$min_processing_time     = isset( $_POST['min_processing_time'] ) ? sanitize_text_field( $_POST['min_processing_time'] ) : '';
	$max_processing_time     = isset( $_POST['max_processing_time'] ) ? sanitize_text_field( $_POST['max_processing_time'] ) : '';
	$processing_time_unit    = isset( $_POST['processing_time_unit'] ) ? sanitize_text_field( $_POST['processing_time_unit'] ) : '';
	$destination_country_iso = isset( $_POST['destination_country_iso'] ) ? sanitize_text_field( $_POST['destination_country_iso'] ) : '';
	$destination_region      = isset( $_POST['destination_region'] ) ? sanitize_text_field( $_POST['destination_region'] ) : '';
	$origin_postal_code      = isset( $_POST['origin_postal_code'] ) ? sanitize_text_field( $_POST['origin_postal_code'] ) : '';

	$parameters = array(
		'title'                   => (string) $title,
		'origin_country_iso'      => (string) $origin_country_iso,
		'primary_cost'            => (float) $primary_cost,
		'secondary_cost'          => (float) $secondary_cost,
		'min_processing_time'     => (int) $min_processing_time,
		'max_processing_time'     => (int) $max_processing_time,
		'processing_time_unit'    => (string) $processing_time_unit,
		'destination_country_iso' => (string) $destination_country_iso,
		'destination_region'      => (string) $destination_region,
		'origin_postal_code'      => (string) $origin_postal_code,
	);

	// $parameters = array_filter($parameters);

	echo '<pre>';

	print_r( $parameters );

	$action   = "application/shops/{$shop_id}/shipping-profiles";
	$response = etsy_request()->post( $action, $parameters, $activeShop );

	print_r( $response );
	if ( false ) {
		echo '<div class="notice notice-success" ><p>' . esc_html( __( 'Shipping Profile Created', 'woocommerce-etsy-integration' ) ) . '</p></div>';
	} elseif ( isset( $response['error'] ) && ! empty( $response['error'] ) ) {
		echo '<div class="notice notice-error" ><p>' . ( isset( $response['error_description'] ) ? esc_attr( $response['error'] ) : esc_attr( $response['error'] ) ) . '</p></div>';
	}
	echo '</pre>';
}

$shipping_profile_fields = array(
	array(
		'type'   => '_text',
		'id'     => 'title',
		'fields' => array(
			'id'          => 'title',
			'label'       => __( 'shipping profile title', 'woocommerce-etsy-integration' ),
			'desc_tip'    => true,
			'description' => __(
				'The name string of this shipping profile.',
				'woocommerce-etsy-integration'
			),
			'type'        => '_text',
			'class'       => 'wc_input_price',
			'required'    => true,
		),
	),
	array(
		'type'   => '_select',
		'id'     => 'origin_country_iso',
		'fields' => array(
			'id'          => 'origin_country_iso',
			'label'       => __( 'origin country', 'woocommerce-etsy-integration' ),
			'desc_tip'    => true,
			'description' => __(
				'Country from which the listing ships.',
				'woocommerce-etsy-integration'
			),
			'type'        => '_select',
			'options'     => $country_list,
			'class'       => 'wc_input_price',
			'required'    => true,
		),
	),
	array(
		'type'   => '_text',
		'id'     => 'primary_cost',
		'fields' => array(
			'id'          => 'primary_cost',
			'label'       => __( 'primary cost', 'woocommerce-etsy-integration' ),
			'desc_tip'    => true,
			'description' => __(
				'The cost of shipping to this country/region alone, measured in the store\'s default currency.',
				'woocommerce-etsy-integration'
			),
			'type'        => '_text',
			'class'       => 'wc_input_price',
			'required'    => true,
		),
	),
	array(
		'type'   => '_text',
		'id'     => 'secondary_cost',
		'fields' => array(
			'id'          => 'secondary_cost',
			'label'       => __( 'secondary cost', 'woocommerce-etsy-integration' ),
			'desc_tip'    => true,
			'description' => __(
				'The cost of shipping to this country/region with another item, measured in the store\'s default currency.',
				'woocommerce-etsy-integration'
			),
			'type'        => '_text',
			'class'       => 'wc_input_price',
			'required'    => true,
		),
	),
	array(
		'type'   => '_text',
		'id'     => 'min_processing_time',
		'fields' => array(
			'id'          => 'min_processing_time',
			'label'       => __( 'min processing time', 'woocommerce-etsy-integration' ),
			'desc_tip'    => true,
			'description' => __(
				'The minimum time required to process to ship listings with this shipping profile.',
				'woocommerce-etsy-integration'
			),
			'type'        => '_text',
			'class'       => 'wc_input_price',
			'required'    => true,
		),
	),
	array(
		'type'   => '_text',
		'id'     => 'max_processing_time',
		'fields' => array(
			'id'          => 'max_processing_time',
			'label'       => __( 'max processing time', 'woocommerce-etsy-integration' ),
			'desc_tip'    => true,
			'description' => __(
				'The maximum processing time the listing needs to ship.',
				'woocommerce-etsy-integration'
			),
			'type'        => '_text',
			'class'       => 'wc_input_price',
			'required'    => true,
		),
	),
	array(
		'type'   => '_select',
		'id'     => 'processing_time_unit',
		'fields' => array(
			'id'          => 'processing_time_unit',
			'label'       => __( 'processing time unit', 'woocommerce-etsy-integration' ),
			'desc_tip'    => true,
			'description' => __(
				'The unit used to represent how long a processing time is. A week is equivalent to 5 business days.',
				'woocommerce-etsy-integration'
			),
			'type'        => '_select',
			'options'     => array(
				'business_days' => 'business days',
				'weeks'         => 'weeks',
			),
			'class'       => 'wc_input_price',
			'required'    => false,
		),
	),
	array(
		'type'   => '_select',
		'id'     => 'destination_country_iso',
		'fields' => array(
			'id'          => 'destination_country_iso',
			'label'       => __( 'destination country', 'woocommerce-etsy-integration' ),
			'desc_tip'    => true,
			'description' => __(
				'Country to which the listing ships. If blank, request sets destination to destination_region.',
				'woocommerce-etsy-integration'
			),
			'type'        => '_select',
			'options'     => $country_list,
			'class'       => 'wc_input_price',
			'required'    => false,
		),
	),
	array(
		'type'   => '_select',
		'id'     => 'destination_region',
		'fields' => array(
			'id'          => 'destination_region',
			'label'       => __( 'destination region', 'woocommerce-etsy-integration' ),
			'desc_tip'    => true,
			'description' => __(
				'The code of the region to which the listing ships. A region represents a set of countries. Supported regions are Europe Union and Non-Europe Union (countries in Europe not in EU). If `none", request sets destination to destination_country_iso, or "everywhere" if destination_country_iso is also blank
					.',
				'woocommerce-etsy-integration'
			),
			'type'        => '_select',
			'options'     => array(
				'eu'     => 'Europe Union',
				'non_eu' => 'Non-Europe Union',
				'none'   => 'None',
			),
			'class'       => 'wc_input_price',
			'required'    => false,
		),
	),
	array(
		'type'   => '_text',
		'id'     => 'origin_postal_code',
		'fields' => array(
			'id'          => 'origin_postal_code',
			'label'       => __( 'origin postal code', 'woocommerce-etsy-integration' ),
			'desc_tip'    => true,
			'description' => __(
				'The postal code string (not necessarily a number) for the location from which the listing ships.',
				'woocommerce-etsy-integration'
			),
			'type'        => '_text',
			'class'       => 'wc_input_price',
			'required'    => false,
		),
	),
);
?>
<div class="ced_etsy_wrap">
	<div class="ced_etsy_account_configuration_wrapper">	
		<div class="ced_etsy_account_configuration_fields">	
			<form method="post" action="">
				<?php wp_nonce_field( 'shipping_settings', 'shipping_settings_submit' ); ?>
				<table class="wp-list-table widefat fixed striped ced_etsy_account_configuration_fields_table">
					<thead>
						<tr><th colspan="2">ADD SHIPPING PROFILE</td></tr>
					</thead>
					<tbody>	

					<?php

					foreach ( $shipping_profile_fields as $field_data ) {
						echo '<tr>';
						$type        = isset( $field_data['type'] ) ? sanitize_text_field( $field_data['type'] ) : '';
						$label       = isset( $field_data['fields']['label'] ) ? sanitize_text_field( $field_data['fields']['label'] ) : '';
						$id          = isset( $field_data['fields']['id'] ) ? sanitize_text_field( $field_data['fields']['id'] ) : '';
						$description = isset( $field_data['fields']['description'] ) ? sanitize_text_field( $field_data['fields']['description'] ) : '';
						$options     = isset( $field_data['fields']['options'] ) ? ( $field_data['fields']['options'] ) : '';
								echo '<td><label>' . ucwords( $label ) . '</label>';
						if ( isset( $field_data['fields']['required'] ) && $field_data['fields']['required'] ) {
							echo ' <span style="color:red;">*</span>';
						}
						ced_etsy_tool_tip( $description );
						 echo '</td>';
						switch ( $type ) {
							case '_text':
								echo "<td><input type='text' name='" . esc_attr( $id ) . "'></td>";
								break;
							case '_select':
								echo "<td><select name='" . $id . "'>";
								echo "<option value=''>--Select--</option>";
								foreach ( $options as $optval => $optlabel ) {
									echo "<option value='" . esc_attr( $optval ) . "'>" . esc_attr( ucwords( $optlabel ) ) . '</option>';
								}
								echo '</select></td>';
								break;
						}
						echo '</tr>';
					}

					?>
								
					</tbody>
				</table>
				<div align="">
					<button id="save_shipping_settings"  name="shipping_settings" class="button-primary"><?php esc_html_e( 'Save', 'woocommerce-etsy-integration' ); ?></button>
				</div>
			</form>
		</div>
	</div>
</div>
