<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

Cedhandler::ced_header();
$saved_etsy_details = get_option( 'ced_etsy_details', array() );

if ( ! is_array( $saved_etsy_details ) ) {
	$saved_etsy_details = array();
}



$marketPlaceName = 'etsy';

$shop_name = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
/*GET COUNTRIES LIST FOR SHIPPING TEMPLATE */
$saved_etsy_details = get_option( 'ced_etsy_details', array() );

if ( ! is_array( $saved_etsy_details ) ) {
	$saved_etsy_details = array();
}


$shopDetails = $saved_etsy_details[ $shop_name ];
$user_id     = isset( $shopDetails['details']['user_id'] ) ? $shopDetails['details']['user_id'] : '';
$shop_id     = isset( $shopDetails['details']['shop_id'] ) ? $shopDetails['details']['shop_id'] : '';

$client = ced_etsy_getOauthClientObject( $shop_name );

if ( ! file_exists( CED_ETSY_DIRPATH . 'admin/lib/json/countries.json' ) ) {
	$success   = $client->CallAPI( 'https://openapi.etsy.com/v2/countries', 'GET', array(), array( 'FailOnAccessError' => true ), $countries );
	$countries = json_encode( $countries );

	$file = fopen( CED_ETSY_DIRPATH . 'admin/lib/json/countries.json', 'w+' );
	chmod( CED_ETSY_DIRPATH . 'admin/lib/json/countries.json', 777 );
	fwrite( $file, $countries );
	fclose( $file );
	$countries = json_decode( $countries, true );
} else {
	$countries = file_get_contents( CED_ETSY_DIRPATH . 'admin/lib/json/countries.json' );
	if ( '' != $countries ) {
		$countries = json_decode( $countries, true );
	}
}

if ( ! file_exists( CED_ETSY_DIRPATH . 'admin/lib/json/regions.json' ) ) {
	$success = $client->CallAPI( 'https://openapi.etsy.com/v2/regions', 'GET', array(), array( 'FailOnAccessError' => true ), $regions );
	$regions = json_encode( $regions );
	$file    = CED_ETSY_DIRPATH . 'admin/lib/json/regions.json';
	file_put_contents( $file, $regions );
	$regions = json_decode( $regions, true );
} else {
	$regions = file_get_contents( CED_ETSY_DIRPATH . 'admin/lib/json/regions.json' );
	if ( '' != $regions ) {
		$regions = json_decode( $regions, true );
	}
}


if ( isset( $_POST['shipping_settings'] ) ) {


	if ( ! isset( $_POST['shipping_settings_submit'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['shipping_settings_submit'] ) ), 'shipping_settings' ) ) {
		return;
	}
	$shipping_title      = isset( $_POST['ced_etsy_shipping_title'] ) ? sanitize_text_field( wp_unslash( $_POST['ced_etsy_shipping_title'] ) ) : '';
	$country_id          = isset( $_POST['ced_etsy_shipping_country_id'] ) ? sanitize_text_field( wp_unslash( $_POST['ced_etsy_shipping_country_id'] ) ) : '';
	$destination_id      = isset( $_POST['ced_etsy_shipping_destination_id'] ) ? sanitize_text_field( wp_unslash( $_POST['ced_etsy_shipping_destination_id'] ) ) : '';
	$primary_cost        = isset( $_POST['ced_etsy_shipping_primary_cost'] ) ? (float) sanitize_text_field( wp_unslash( $_POST['ced_etsy_shipping_primary_cost'] ) ) : '';
	$secondary_cost      = isset( $_POST['ced_etsy_shipping_secondary_cost'] ) ? (float) sanitize_text_field( wp_unslash( $_POST['ced_etsy_shipping_secondary_cost'] ) ) : '';
	$region_id           = isset( $_POST['ced_etsy_shipping_region_id'] ) ? sanitize_text_field( wp_unslash( $_POST['ced_etsy_shipping_region_id'] ) ) : '';
	$min_process_time    = isset( $_POST['ced_etsy_shipping_min_process_time'] ) ? sanitize_text_field( wp_unslash( $_POST['ced_etsy_shipping_min_process_time'] ) ) : '';
	$max_process_time    = isset( $_POST['ced_etsy_shipping_max_process_time'] ) ? sanitize_text_field( wp_unslash( $_POST['ced_etsy_shipping_max_process_time'] ) ) : '';
	$origin_postal_code  = isset( $_POST['ced_etsy_origin_postal_code'] ) ? sanitize_text_field( wp_unslash( $_POST['ced_etsy_origin_postal_code'] ) ) : '';
	$shipping_carrier_id = ! empty( $_POST['ced_etsy_shipping_carrier_id'] ) ? sanitize_text_field( wp_unslash( $_POST['ced_etsy_shipping_carrier_id'] ) ) : '';
	$mail_class          = ! empty( $_POST['ced_etsy_mail_class'] ) ? sanitize_text_field( wp_unslash( $_POST['ced_etsy_mail_class'] ) ) : '';
	$min_delivery_time   = isset( $_POST['ced_etsy_min_delivery_time'] ) ? sanitize_text_field( wp_unslash( $_POST['ced_etsy_min_delivery_time'] ) ) : '';
	$max_delivery_time   = isset( $_POST['ced_etsy_max_delivery_time'] ) ? sanitize_text_field( wp_unslash( $_POST['ced_etsy_max_delivery_time'] ) ) : '';
	if ( ! empty( $shipping_title ) && ! empty( $country_id ) ) {
		$params = array(
			'title'                  => "$shipping_title",
			'origin_country_id'      => (int) $country_id,
			'destination_country_id' => (int) $destination_id,
			'primary_cost'           => (float) $primary_cost,
			'secondary_cost'         => (float) $secondary_cost,
			'destination_region_id'  => (int) $region_id,
			'min_processing_days'    => (int) $min_process_time,
			'max_processing_days'    => (int) $max_process_time,
		);

		if ( ! empty( $origin_postal_code ) ) {
			$params['origin_postal_code'] = (string) $origin_postal_code;
		}

		if ( ! empty( $mail_class ) ) {
			$params['mail_class'] = (string) $mail_class;
		}
		if ( ! empty( $shipping_carrier_id ) ) {
			$params['shipping_carrier_id'] = (int) $shipping_carrier_id;
		}
		if ( empty( $params['mail_class'] ) ) {
			$params['min_delivery_time'] = (int) $min_delivery_time;
			$params['max_delivery_time'] = (int) $max_delivery_time;
		}
		$success          = $client->CallAPI( 'https://openapi.etsy.com/v2/shipping/templates', 'POST', $params, array( 'FailOnAccessError' => true ), $shippingTemplate );
		$shippingTemplate = json_decode( json_encode( $shippingTemplate ), true );

		if ( isset( $shippingTemplate['results'] ) ) {
			echo '<div class="notice notice-success" ><p>' . esc_html( __( 'Shipping Template Created', 'woocommerce-etsy-integration' ) ) . '</p></div>';
		} else {
			$error = array_keys( $shippingTemplate );
			echo '<div class="notice notice-error" ><p>' . esc_html( __( ( isset( $error[0] ) ? ucfirst( str_replace( '_', ' ', $error[0] ) ) : 'Shipping Template Not Created.' ), 'woocommerce-etsy-integration' ) ) . '</p></div>';
		}
	} else {
		echo '<div class="notice notice-error" ><p>' . esc_html( __( 'Required Fields Missing', 'woocommerce-etsy-integration' ) ) . '</p></div>';
	}
}

?>

<div class="ced_etsy_wrap">
	<div class="ced_etsy_account_configuration_wrapper">	
		<div class="ced_etsy_account_configuration_fields">	
			<form method="post" action="">
				<?php wp_nonce_field( 'shipping_settings', 'shipping_settings_submit' ); ?>
				<table class="wp-list-table widefat fixed striped ced_etsy_account_configuration_fields_table">
					<thead>
						<tr><th colspan="2">ADD SHIPPING TEMPLATE</th></tr>
					</thead>
					<tbody>
						<tr>
							<th>
								<label><?php esc_html_e( 'Title', 'woocommerce-etsy-integration' ); ?></label><span style="color: red; margin-left:5px; ">*</span>
							</th>
							<td>
								<input placeholder="<?php esc_html_e( 'Enter Shipping Title', 'woocommerce-etsy-integration' ); ?>" class="short" type="text" name="ced_etsy_shipping_title"></input>
							</td>
						</tr>
						<tr>
							<th>
								<label><?php esc_html_e( 'Origin Country', 'woocommerce-etsy-integration' ); ?></label><span style="color: red; margin-left:5px; ">*</span>
							</th>
							<td>
								<select name="ced_etsy_shipping_country_id" class="select short" id="ced_etsy_shipping_country_id">
									<option value=""><?php esc_html_e( '--Select--', 'woocommerce-etsy-integration' ); ?></option>
									<?php
									foreach ( $countries['results'] as $key => $value ) {
										?>
										<option value="<?php echo esc_attr( $value['country_id'] ); ?>" data-country-iso="<?php echo esc_attr( $value['iso_country_code'] ); ?>"><?php echo esc_attr( $value['name'] ); ?></option>
										<?php
									}
									?>
								</select>
							</td>
						</tr>
						<tr>
							<th>
								<label><?php esc_html_e( 'Destination Country', 'woocommerce-etsy-integration' ); ?></label>
							</th>
							<td>
								<select name="ced_etsy_shipping_destination_id" class="select short">
									<option value=""><?php esc_html_e( '--Select--', 'woocommerce-etsy-integration' ); ?></option>
									<?php
									foreach ( $countries['results'] as $key => $value ) {
										?>
										<option value="<?php echo esc_attr( $value['country_id'] ); ?>"><?php echo esc_attr( $value['name'] ); ?></option>
										<?php
									}
									?>
								</select>
							</td>
						</tr>
						<tr>
							<th>
								<label><?php esc_html_e( 'Primary Cost', 'woocommerce-etsy-integration' ); ?></label><span style="color: red; margin-left:5px; ">*</span>
							</th>
							<td>
								<input placeholder="<?php esc_html_e( 'Enter Primary Cost', 'woocommerce-etsy-integration' ); ?>" class="short" type="text" name="ced_etsy_shipping_primary_cost"></input>
							</td>
						</tr>
						<tr>
							<th>
								<label><?php esc_html_e( 'Secondary Cost', 'woocommerce-etsy-integration' ); ?></label><span style="color: red; margin-left:5px; ">*</span>
							</th>
							<td>
								<input placeholder="<?php esc_html_e( 'Enter Secondary Cost', 'woocommerce-etsy-integration' ); ?>" class="short" type="text" name="ced_etsy_shipping_secondary_cost"></input>
							</td>
						</tr>
						<tr>
							<th>
								<label><?php esc_html_e( 'Destination Region', 'woocommerce-etsy-integration' ); ?></label>
							</th>
							<td>
								<select name="ced_etsy_shipping_region_id" class="select short">
									<option value=""><?php esc_html_e( '--Select--', 'woocommerce-etsy-integration' ); ?></option>
									<?php
									foreach ( $regions['results'] as $key => $value ) {
										?>
										<option value="<?php echo esc_attr( $value['region_id'] ); ?>"><?php echo esc_attr( $value['region_name'] ); ?></option>
										<?php
									}
									?>
								</select>
							</td>
						</tr>
						<tr>
							<th>
								<label><?php esc_html_e( 'Minimum Processing Days', 'woocommerce-etsy-integration' ); ?></label>
							</th>
							<td>
								<input placeholder="<?php esc_html_e( 'Enter Min Processing Days', 'woocommerce-etsy-integration' ); ?>" class="short" type="text" name="ced_etsy_shipping_min_process_time"></input>
							</td>
						</tr>
						<tr>
							<th>
								<label><?php esc_html_e( 'Maximum Processing Days', 'woocommerce-etsy-integration' ); ?></label>
							</th>
							<td>
								<input placeholder="<?php esc_html_e( 'Enter Max Processing Days', 'woocommerce-etsy-integration' ); ?>" class="short" type="text" name="ced_etsy_shipping_max_process_time"></input>
							</td>
						</tr>

						<tr>
							<th>
								<label><?php esc_html_e( 'Origin Postal Code / Zip Code', 'woocommerce-etsy-integration' ); ?></label>
							</th>
							<td>
								<input placeholder="<?php esc_html_e( 'Enter Origin Postal Code', 'woocommerce-etsy-integration' ); ?>" class="short" type="text" name="ced_etsy_origin_postal_code"></input>
							</td>
						</tr>
						<tr>
							<th>
								<label><?php esc_html_e( 'Shipping Carrier', 'woocommerce-etsy-integration' ); ?></label>
							</th>
							<td>
								<select name="ced_etsy_shipping_carrier_id" class="select short" id="ced_etsy_shipping_carrier_id">
									<option value=""><?php esc_html_e( '--Choose Destination Country Above First--', 'woocommerce-etsy-integration' ); ?></option>
								</select>
							</td>
						</tr>

						<tr>
							<th>
								<label><?php esc_html_e( 'Mail Class', 'woocommerce-etsy-integration' ); ?></label>
							</th>
							<td>
								<select name="ced_etsy_mail_class" class="select short" id="ced_etsy_mail_class">
									<option value=""><?php esc_html_e( '--Choose Destination Country Above First--', 'woocommerce-etsy-integration' ); ?></option>
								</select>
							</td>
						</tr>


						<tr>
							<th>
								<label><?php esc_html_e( 'Min Delivery Time', 'woocommerce-etsy-integration' ); ?></label><span style="color: red; margin-left:5px; ">*</span>
							</th>
							<td>
								<input placeholder="<?php esc_html_e( 'Enter Min Delivery Time', 'woocommerce-etsy-integration' ); ?>" class="short" type="text" name="ced_etsy_min_delivery_time"></input>
							</td>
						</tr>
						<tr>
							<th>
								<label><?php esc_html_e( 'Max Delivery Time', 'woocommerce-etsy-integration' ); ?></label><span style="color: red; margin-left:5px; ">*</span>
							</th>
							<td>
								<input placeholder="<?php esc_html_e( 'Enter Max Delivery Time', 'woocommerce-etsy-integration' ); ?>" class="short" type="text" name="ced_etsy_max_delivery_time"></input>
							</td>
						</tr>
					</tbody>
				</table>
				<div align="">
					<button id="save_shipping_settings"  name="shipping_settings" class="btn btn-primary"><?php esc_html_e( 'Save', 'woocommerce-etsy-integration' ); ?></button>
				</div>
			</form>
		</div>
	</div>
</div>
