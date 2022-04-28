<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

function etsy_write_logs( $log, $folder, $end = true ) {
	$dirTowriteFile = CED_ETSY_LOG_DIRECTORY;
	if ( defined( 'CED_ETSY_LOG_DIRECTORY' ) ) {

		if ( ! is_dir( $dirTowriteFile . '/' . $folder ) ) {
			if ( ! mkdir( $dirTowriteFile . '/' . $folder, 0755 ) ) {
				return;
			}
		}

		$fileTowrite = $dirTowriteFile . "/$folder/" . date( 'd-m-y' ) . '.log';
		$fp          = fopen( $fileTowrite, 'a' );

		if ( ! $fp ) {
			return;
		}

		if ( $end ) {
			$log .= $log . "\n>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>\n\n";
		}

		$fr = fwrite( $fp, $log );
		fclose( $fp );

	}
}

function log_head() {
	$user         = wp_get_current_user();
	$user_name    = $user->data->display_name;
	$user_email   = $user->data->user_email;
	$user_details = $user_name . ' [ ' . $user_email . ' ]';
	$log_head     = '';
	$log_head    .= 'Time : ' . date_i18n( 'y-m-d H:i:s' ) . "\n";
	$log_head    .= "Current user : $user_details \n\n";
	return $log_head;
}

function checkLicenseValidationEtsy() {

	return true;

	$etsy_license        = get_option( 'ced_etsy_license', false );
	$etsy_license_key    = get_option( 'ced_etsy_license_key', false );
	$etsy_license_module = get_option( 'ced_etsy_license_module', false );
	$license_valid       = apply_filters( 'ced_etsy_license_check', false );

	if ( $license_valid ) {
		return true;
	} else {
		return false;
	}
}

function display_support_html() {
	?>

	<div class="ced_contact_menu_wrap">
		<input type="checkbox" href="#" class="ced_menu_open" name="menu-open" id="menu-open" />
		<label class="ced_menu_button" for="menu-open">
			<img src="<?php echo esc_url( CED_ETSY_URL . 'admin/assets/images/icon.png' ); ?>" alt="" title="Click to Chat">
		</label>
		<a href="https://join.skype.com/UHRP45eJN8qQ" class="ced_menu_content ced_skype" target="_blank"> <i class="fa fa-skype" aria-hidden="true"></i> </a>
		<a href="https://chat.whatsapp.com/BcJ2QnysUVmB1S2wmwBSnE" class="ced_menu_content ced_whatsapp" target="_blank"> <i class="fa fa-whatsapp" aria-hidden="true"></i> </a>
	</div>

	<?php
}

function ced_etsy_inactive_shops( $shop_name = '' ) {

	$shops = get_option( 'ced_etsy_details', '' );
	if ( isset( $shops[ $shop_name ]['details']['ced_shop_account_status'] ) && 'InActive' == $shops[ $shop_name ]['details']['ced_shop_account_status'] ) {
		return true;
	}
}

function ced_etsy_getOauthClientObject( $shop_name = '' ) {

	if ( session_status() == PHP_SESSION_NONE ) {
		session_start();
	}

	$activeShop            = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : $shop_name;
	$access_tokens         = get_option( 'ced_etsy_access_tokens', array() );
	$ced_etsy_access_token = isset( $access_tokens[ $activeShop ] ) ? $access_tokens[ $activeShop ] : get_option( 'ced_etsy_access_token', array() );

	if ( ! empty( $ced_etsy_access_token ) ) {
		$_SESSION['OAUTH_ACCESS_TOKEN'] = $ced_etsy_access_token;
	}

	if ( file_exists( CED_ETSY_DIRPATH . 'admin/lib/vendor/http.php' ) ) {
		require_once CED_ETSY_DIRPATH . 'admin/lib/vendor/http.php';
	}
	if ( file_exists( CED_ETSY_DIRPATH . 'admin/lib/vendor/oauth_client.php' ) ) {
		require_once CED_ETSY_DIRPATH . 'admin/lib/vendor/oauth_client.php';
	}

	$client                = new oauth_client_class();
	$client->shop_name     = $activeShop;
	$client->debug         = false;
	$client->debug_http    = true;
	$client->server        = 'Etsy';
	$client->redirect_uri  = admin_url( 'admin.php?page=ced_etsy' );
	$application_line      = __LINE__;
	$client->client_id     = 'ghvcvauxf2taqidkdx2sw4g4';
	$client->client_secret = '27u2kvhfmo';

	$client->Initialize();
	return $client;
}

function ced_etsy_get_active_shop_name() {
	$saved_etsy_details = get_option( 'ced_etsy_details', array() );
	$shopName           = isset( $saved_etsy_details['details']['ced_etsy_shop_name'] ) ? $saved_etsy_details['details']['ced_etsy_shop_name'] : '';
	return $shopName;
}

function ced_etsy_tool_tip( $tip = '' ) {
	// echo wc_help_tip( __( $tip, 'woocommerce-etsy-integration' ) );
	print_r( "</br><span class='cedcommerce-tip'>[ $tip ]</span>" );
}

/**
 * Callback function for display html.
 *
 * @since 1.0.0
 */
function ced_etsy_render_html( $meta_keys_to_be_displayed = array(), $added_meta_keys = array() ) {
	$html  = '';
	$html .= '<table class="wp-list-table widefat fixed striped ced_etsy_config_table">';

	if ( isset( $meta_keys_to_be_displayed ) && is_array( $meta_keys_to_be_displayed ) && ! empty( $meta_keys_to_be_displayed ) ) {
		$total_items  = count( $meta_keys_to_be_displayed );
		$pages        = ceil( $total_items / 10 );
		$current_page = 1;
		$counter      = 0;
		$break_point  = 1;

		foreach ( $meta_keys_to_be_displayed as $meta_key => $meta_data ) {
			$display = 'display : none';
			if ( 0 == $counter ) {
				if ( 1 == $break_point ) {
					$display = 'display : contents';
				}
				$html .= '<tbody style="' . esc_attr( $display ) . '" class="ced_etsy_metakey_list_' . $break_point . '  			ced_etsy_metakey_body">';
				$html .= '<tr><td colspan="3"><label>CHECK THE METAKEYS OR ATTRIBUTES</label></td>';
				$html .= '<td class="ced_etsy_pagination"><span>' . $total_items . ' items</span>';
				$html .= '<button class="button ced_etsy_navigation" data-page="1" ' . ( ( 1 == $break_point ) ? 'disabled' : '' ) . ' ><b><<</b></button>';
				$html .= '<button class="button ced_etsy_navigation" data-page="' . esc_attr( $break_point - 1 ) . '" ' . ( ( 1 == $break_point ) ? 'disabled' : '' ) . ' ><b><</b></button><span>' . $break_point . ' of ' . $pages;
				$html .= '</span><button class="button ced_etsy_navigation" data-page="' . esc_attr( $break_point + 1 ) . '" ' . ( ( $pages == $break_point ) ? 'disabled' : '' ) . ' ><b>></b></button>';
				$html .= '<button class="button ced_etsy_navigation" data-page="' . esc_attr( $pages ) . '" ' . ( ( $pages == $break_point ) ? 'disabled' : '' ) . ' ><b>>></b></button>';
				$html .= '</td>';
				$html .= '</tr>';
				$html .= '<tr><td><label>Select</label></td><td><label>Metakey / Attributes</label></td><td colspan="2"><label>Value</label></td>';

			}
			$checked    = ( in_array( $meta_key, $added_meta_keys ) ) ? 'checked=checked' : '';
			$html      .= '<tr>';
			$html      .= "<td><input type='checkbox' class='ced_etsy_meta_key' value='" . esc_attr( $meta_key ) . "' " . $checked . '></input></td>';
			$html      .= '<td>' . esc_attr( $meta_key ) . '</td>';
			$meta_value = ! empty( $meta_data[0] ) ? $meta_data[0] : '';
			$html      .= '<td colspan="2">' . esc_attr( $meta_value ) . '</td>';
			$html      .= '</tr>';
			++$counter;
			if ( 10 == $counter || $break_point == $pages ) {
				$counter = 0;
				++$break_point;
				// $html .= '<tr><td colsapn="4"><a href="" class="ced_etsy_custom_button button button-primary">Save</a></td></tr>';
				$html .= '</tbody>';
			}
		}
	} else {
		$html .= '<tr><td colspan="4" class="etsy-error">No data found. Please search the metakeys.</td></tr>';
	}
	$html .= '</table>';
	return $html;
}

/**
 * Callback function for display html.
 *
 * @since 1.0.0
 */
function get_etsy_instuctions_html( $label = 'Instructions' ) {
	?>
	<div class="ced_etsy_parent_element">
		<h2>
			<label><?php echo esc_html_e( $label, 'etsy-woocommerce-integration' ); ?></label>
			<span class="dashicons dashicons-arrow-down-alt2 ced_etsy_instruction_icon"></span>
		</h2>
	</div>
	<?php
}

/**
 * *********************************************
 * Get Product id by listing id and Shop Name
 * *********************************************
 *
 * @since 1.0.0
 */
function etsy_get_product_id_by_shopname_and_listing_id( $shop_name = '', $listing = '' ) {

	if ( empty( $shop_name ) || empty( $listing ) ) {
		return;
	}
	$if_exists  = get_posts(
		array(
			'numberposts' => -1,
			'post_type'   => 'product',
			'meta_query'  => array(
				array(
					'key'     => '_ced_etsy_listing_id_' . $shop_name,
					'value'   => $listing,
					'compare' => '=',
				),
			),
			'fields'      => 'ids',
		)
	);
	$product_id = isset( $if_exists[0] ) ? $if_exists[0] : '';
	return $product_id;
}

function ced_etsy_cedcommerce_logo() {
	?>
	<a href="https://cedcommerce.com" target="_blank"><img src="<?php echo esc_url( CED_ETSY_URL . 'admin/assets/images/ced-logo.png' ); ?> "></a>
	<?php
}

function etsy_request() {
	require_once CED_ETSY_DIRPATH . 'admin/lib/etsyRequest.php';
	$request = new Ced_Etsy_Request();
	return $request;
}

function etsy_shop_id( $shop_name = '' ) {
	$saved_etsy_details = get_option( 'ced_etsy_details', array() );
	$shopDetails        = $saved_etsy_details[ $shop_name ];
	$shop_id            = isset( $shopDetails['details']['shop_id'] ) ? $shopDetails['details']['shop_id'] : '';
	return $shop_id;
}


function deactivate_ced_etsy_woo_missing() {
	deactivate_plugins( CED_ETSY_PLUGIN_BASENAME );
	add_action( 'admin_notices', 'ced_zalora_woo_missing_notice' );
	if ( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] );
	}
}

function ced_etsy_check_woocommerce_active() {
	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		return true;
	}
	return false;
}

function get_etsy_shop_id( $shop_name = '' ){
	$saved_etsy_details = get_option( 'ced_etsy_details', array() );
	$shopDetails        = $saved_etsy_details[ $shop_name ];
	$shop_id            = isset( $shopDetails['details']['shop_id'] ) ? $shopDetails['details']['shop_id'] : '';
	return $shop_id;
}