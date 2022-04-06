<?php
	$activeShop                 = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
	$renderDataOnGlobalSettings = get_option( 'ced_etsy_global_settings', false );
	$auto_fetch_orders          = isset( $renderDataOnGlobalSettings[ $activeShop ]['ced_etsy_auto_fetch_orders'] ) ? $renderDataOnGlobalSettings[ $activeShop ]['ced_etsy_auto_fetch_orders'] : '';

	$auto_confirm_orders               = isset( $renderDataOnGlobalSettings[ $activeShop ]['ced_etsy_auto_import_product'] ) ? $renderDataOnGlobalSettings[ $activeShop ]['ced_etsy_auto_import_product'] : '';
	$auto_update_inventory_woo_to_etsy = isset( $renderDataOnGlobalSettings[ $activeShop ]['ced_etsy_auto_update_inventory'] ) ? $renderDataOnGlobalSettings[ $activeShop ]['ced_etsy_auto_update_inventory'] : '';
	$auto_update_stock_etsy_to_woo     = isset( $renderDataOnGlobalSettings[ $activeShop ]['ced_etsy_update_inventory_etsy_to_woo'] ) ? $renderDataOnGlobalSettings[ $activeShop ]['ced_etsy_update_inventory_etsy_to_woo'] : '';
	$ced_etsy_auto_upload_product      = isset( $renderDataOnGlobalSettings[ $activeShop ]['ced_etsy_auto_upload_product'] ) ? $renderDataOnGlobalSettings[ $activeShop ]['ced_etsy_auto_upload_product'] : '';
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
						$profile_page = admin_url( 'admin.php?page=ced_etsy&section=profiles-view&shop_name=' . $activeShop );
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
