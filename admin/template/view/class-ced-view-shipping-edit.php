<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
$header = new \Cedcommerce\Template\View\Ced_View_Header();
$shop_name = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
$e_prof_id = isset( $_GET['e_prof_id'] ) ? sanitize_text_field( wp_unslash( $_GET['e_prof_id'] ) ) : '';
$saved_etsy_details = get_option( 'ced_etsy_details', array() );

if (isset($_POST['update_e_shiping_prof'])) {
	if ( ! isset( $_POST['edit_shipping_profile'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['edit_shipping_profile'] ) ), 'e_s_p' ) ) {
		return;
	}
	$get_prof_data      = filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING );
	$ced_e_prof_data    = isset( $get_prof_data['etsy_shipping_prof_data'] ) ? $get_prof_data['etsy_shipping_prof_data'] : array();
	if (empty( $e_prof_id )) {
		$e_prof_id = isset( $ced_e_prof_data['shipping_profile_id'] ) ? $ced_e_prof_data['shipping_profile_id'] : '';
	}
	$shopDetails        = $saved_etsy_details[ $shop_name ];
	$shop_id            = isset( $shopDetails['details']['shop_id'] ) ? $shopDetails['details']['shop_id'] : '';
	$action  = "application/shops/{$shop_id}/shipping-profiles/{$e_prof_id}";
	// Refresh token if isn't.
	do_action( 'ced_etsy_refresh_token', $shop_name );
	$response = etsy_request()->put( $action, $ced_e_prof_data, $shop_name, array(), 'PUT' );
	if (isset( $response['shipping_profile_id'] )) {
		echo "<h3> Updated Successfully!</h3>";
	}
}

$shopDetails        = $saved_etsy_details[ $shop_name ];
$user_id            = isset( $shopDetails['details']['user_id'] ) ? $shopDetails['details']['user_id'] : '';
$shop_id            = isset( $shopDetails['details']['shop_id'] ) ? $shopDetails['details']['shop_id'] : '';
$shippingTemplates  = array();
$action             = "application/shops/{$shop_id}/shipping-profiles/{$e_prof_id}";
// Refresh token if isn't.
do_action( 'ced_etsy_refresh_token', $shop_name );
$shippign_templates = etsy_request()->get( $action, $shop_name );
?>
<form action="" method="post">
	<?php wp_nonce_field( 'e_s_p', 'edit_shipping_profile' ); ?>
	<table class="wp-list-table fixed widefat ced_etsy_schedule_wrap">
			<tbody>
				<?php 
				$not_show = array( 'shipping_profile_destinations', 'shipping_profile_upgrades', 'user_id','shipping_profile_id' );
				foreach($shippign_templates as $lable => $value ) {
					if ( in_array( $lable, $not_show )) {
						echo '<input type="hidden" name="etsy_shipping_prof_data['.$lable.']" value="'.$value.'">';
						continue;
					}
				 ?>
					<tr>
						<th>
							<label><?php echo esc_html_e( ucfirst( str_replace('_', ' ', $lable ) ), 'etsy-woocommerce-integration' ); ?></label>
						</th>
						<td>
							<input type="text" name="etsy_shipping_prof_data[<?php echo $lable; ?>]" value="<?php echo $value; ?>">
						</td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	<div class="left ced-button-wrapper" >
		<button id=""  type="submit" name="update_e_shiping_prof" class="button-primary" ><?php esc_html_e( 'Update Profile', 'woocommerce-etsy-integration' ); ?></button>
	</div>
</form>
