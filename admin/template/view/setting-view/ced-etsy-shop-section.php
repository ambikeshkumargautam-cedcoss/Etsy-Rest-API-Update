<?php
/* saving and getting values */
$activeShop         = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
$saved_etsy_details = get_option( 'ced_etsy_details', array() );
$shopDetails        = $saved_etsy_details[ $activeShop ];
$user_id            = isset( $shopDetails['details']['user_id'] ) ? $shopDetails['details']['user_id'] : '';
$shop_id            = isset( $shopDetails['details']['shop_id'] ) ? $shopDetails['details']['shop_id'] : '';

$client            = ced_etsy_getOauthClientObject( $activeShop );
$shopSections      = array();
$savedShopSections = array();
$success           = $client->CallAPI( "https://openapi.etsy.com/v2/shops/{$shop_id}/sections", 'GET', array( 'shop_id' => $shop_id ), array( 'FailOnAccessError' => true ), $shopSections );

$shopSections = json_decode( json_encode( $shopSections ), true );

if ( isset( $shopSections['count'] ) && $shopSections['count'] >= 1 ) {
	$shopSections = $shopSections['results'];
	foreach ( $shopSections as $key => $value ) {
		$savedShopSections[ $value['shop_section_id'] ] = $value['title'];
	}
	update_option( 'ced_etsy_shop_sections', $savedShopSections );
}

?>

<div class="ced_etsy_heading">
<?php echo esc_html_e( get_etsy_instuctions_html( 'Shop Section' ) ); ?>
<div class="ced_etsy_child_element">
	<?php wp_nonce_field( 'global_settings', 'global_settings_submit' ); ?>
	
	<div class="ced_etsy_wrap">
		<div class="">
			<span class="section_info"><?php esc_html_e( '[ This section displays all shop sections of your Etsy shop . You can aslo add a new shop section by entering the section name and clicking Add ]', 'ced-etsy' ); ?></span>
		</div>
		<?php
		if ( ! empty( $savedShopSections ) || isset( $shopDetails['access_token'] ) && ! empty( $shopDetails['access_token'] ) ) {
			?>
			<div class="ced_etsy_return_address">
					<?php wp_nonce_field( 'saveShopSections', 'shop_settings_submit' ); ?>
					<div id="update-button">
						<table class=""  id="ced_etsy_shop_sections">
							<tbody>
								<?php
								foreach ( $savedShopSections as $key => $value ) {
									?>
									<tr>
										<input class="ced_etsy_inputs" type="hidden" name="ced_etsy_shop_sections[id][]" value="<?php echo esc_html( $key ); ?>"></input>
										<div class="shop_sections"><?php echo esc_html( $value ); ?> |</div>

									</tr>
									<?php
								}
								?>
								<tr>
									<td class="manage-column" >
										<input class="ced_etsy_inputs" type="hidden" name="ced_etsy_shop_sections[id][]"></input>
										<input class="ced_etsy_inputs ced_etsy_shopsection_inputs" type="text" name="ced_etsy_shop_sections[title][]" placeholder="Enter shop section to be added"></input>
									</td>
									<td>
										<input type="button" class="ced_etsy_add_more_shop_section button" id = "" value="<?php esc_html_e( '+', 'ced-etsy' ); ?>"></input>
									</td>
								</tr>
								<tr>
									<td>
										<button id=""  name="saveShopSections" class="button-primary"><?php esc_html_e( 'Add', 'woocommerce-etsy-integration' ); ?></button>
									</td>
								</tr>
							</tbody>
						
						</table>
					</div>
			</div>
			<?php
		} else {
			?>
			<table class="" id="ced_etsy_shop_sections">
				<tbody>
					<tr>
						<th>
							<?php esc_html_e( 'Please Authorize your store', 'ced-etsy' ); ?>
						</th>
					</tr>
				</tbody>
			</table>
			<?php
		}
		?>
	</div>
</div>
</div>
