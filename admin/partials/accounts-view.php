<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

$file = CED_ETSY_DIRPATH . 'admin/partials/header.php';
if ( file_exists( $file ) ) {
	require_once $file;
}
$shops = get_option( 'ced_etsy_details', array() );
?>

<div class="ced_etsy_account_configuration_wrapper">	
	<div class="ced_etsy_account_configuration_fields">		
		<table class="wp-list-table widefat fixed striped ced_etsy_account_configuration_fields_table">
			<tbody>
				<?php
				if ( isset( $shops[ $activeShop ]['details']['shop_id'] ) ) {
					?>
					<tr>
						<th>
							<label><?php esc_html_e( 'Store Id', 'woocommerce-etsy-integration' ); ?></label>
						</th>
						<td>
							<label><?php echo esc_attr( $shops[ $activeShop ]['details']['shop_id'] ); ?></label>
						</td>
					</tr>
					<?php
				}
				?>
							
				<tr>
					<th>
						<label><?php esc_html_e( 'Store Name', 'woocommerce-etsy-integration' ); ?></label>
					</th>
					<td>
						<label><?php echo esc_attr( $activeShop ); ?></label>
					</td>
				</tr>
				<tr>
					<th>
						<label><?php esc_html_e( 'Username', 'woocommerce-etsy-integration' ); ?></label>
					</th>
					<td>
						<label><?php echo esc_attr( $shops[ $activeShop ]['details']['user_name'] ); ?></label>
					</td>
				</tr>
				<tr>
					<th>
						<label><?php esc_html_e( 'Account Status', 'woocommerce-etsy-integration' ); ?></label>
					</th>
					<td>
						<?php
						if ( isset( $shops[ $activeShop ]['details']['ced_shop_account_status'] ) && 'InActive' == $shops[ $activeShop ]['details']['ced_shop_account_status'] ) {
							$inactive = 'selected';
							$active   = '';
						} else {
							$active   = 'selected';
							$inactive = '';
						}
						?>
						<select class="ced_etsy_select select_boxes" id="ced_etsy_account_status">
							<option><?php esc_html_e( '--Select Status--', 'woocommerce-etsy-integration' ); ?></option>
							<option value="Active" <?php echo esc_attr( $active ); ?>><?php esc_html_e( 'Active', 'woocommerce-etsy-integration' ); ?></option>
							<option value="InActive" <?php echo esc_attr( $inactive ); ?>><?php esc_html_e( 'Inactive', 'woocommerce-etsy-integration' ); ?></option>
						</select>
						<a class="ced_etsy_update_status_message" data-id="<?php echo esc_attr( $activeShop ); ?>" id="ced_etsy_update_account_status" href="javascript:void(0);"><?php esc_html_e( 'Update Account Status', 'woocommerce-etsy-integration' ); ?></a>
					</td>
				</tr>			
			</tbody>
		</table>
	</div>

</div>
