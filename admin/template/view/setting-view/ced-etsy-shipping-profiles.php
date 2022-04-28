<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
$activeShop = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
/*GET COUNTRIES LIST FOR SHIPPING TEMPLATE */
$saved_etsy_details = get_option( 'ced_etsy_details', array() );
$shopDetails        = $saved_etsy_details[ $activeShop ];
$user_id            = isset( $shopDetails['details']['user_id'] ) ? $shopDetails['details']['user_id'] : '';
$shop_id            = isset( $shopDetails['details']['shop_id'] ) ? $shopDetails['details']['shop_id'] : '';
$shippingTemplates  = array();
$action             = "application/shops/{$shop_id}/shipping-profiles";
// $action = "application/shops/{$shop_id}/listings/1187758391/images";
do_action( 'ced_etsy_refresh_token', $activeShop );
$shopShippingTemplates = etsy_request()->get( $action, $activeShop );
if ( isset( $shopShippingTemplates['count'] ) && $shopShippingTemplates['count'] >= 1 ) {
	$shopShippingTemplates = $shopShippingTemplates['results'];
	foreach ( $shopShippingTemplates as $key => $value ) {
		$shippingTemplates[ (string) $value['shipping_profile_id'] ] = $value['title'];
	}
}

$savedShippingDetails     = '';
$savedTempShippingDetails = get_option( 'ced_etsy_details', array() );
$savedShippingDetails     = isset( $savedTempShippingDetails[ $activeShop ]['shippingTemplateId'] ) ? $savedTempShippingDetails[ $activeShop ]['shippingTemplateId'] : '';

$isShopInActive = ced_etsy_inactive_shops( $activeShop );
if ( $isShopInActive ) {
	echo '<div class="notice notice-error"><p>Shop is not Active.Please Activate your Shop in order to save Shipping Template</p></div>';

}
?>

<div class="ced_etsy_heading">
	<?php echo esc_html_e( get_etsy_instuctions_html( 'Shipping Profiles' ) ); ?>
	<div class="ced_etsy_child_element">
		<?php wp_nonce_field( 'global_settings', 'global_settings_submit' ); ?>
		<?php wp_nonce_field( 'saveShippingTemplates', 'shipping_settings_submit' ); ?>
		<div id="update-button">
				<table class="wp-list-table widefat ced_etsy_config_table"  id="">
					<?php

					if ( is_array( $shippingTemplates ) && ! empty( $shippingTemplates ) ) {
						$shipping_templates                = get_option( 'ced_etsy_shipping_templates', array() );
						$shipping_templates[ $activeShop ] = $shippingTemplates;
						update_option( 'ced_etsy_shipping_templates', $shipping_templates );
						?>
						<tbody>
							<tr>
								<th><label class="ced_bold"><?php esc_html_e( 'Choose Shipping Profile', 'ced-etsy' ); ?></label>
									<?php ced_etsy_tool_tip( 'Shipping profile to be used for uploading products on Etsy.' ); ?>
								</th>
								<td class="manage-column">
									<select class="select_boxes" name="ced_etsy_shipping_details[ced_etsy_selected_shipping_template]" value="">
										<?php
										foreach ( $shippingTemplates as $key1 => $value1 ) {
											$selected = '';
											if ( $key1 == $savedShippingDetails ) {
												$selected = 'selected';
											}
											?>
											<option <?php echo esc_html( $selected ); ?> value="<?php echo esc_html( $key1 ); ?>"><?php echo esc_html( $value1 ); ?></option>
											<i class="fa-thin fa-pen-to-square"></i>
											<?php
										}
										?>
									</select>
								</td>

								

								<?php
					} else {
						?>
								<td>
									<th><?php esc_html_e( 'Create a Shipping Profile', 'ced-etsy' ); ?></th>
								</td>
								<?php
					}
					?>
							<td>
								<?php
								$url = admin_url( 'admin.php?page=ced_etsy&section=add-shipping-profile&shop_name=' . $activeShop );
								?>
								<a href="<?php echo esc_attr( $url ); ?>" class="button-primary " >Add New</a>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
