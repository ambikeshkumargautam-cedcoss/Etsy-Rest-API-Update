<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
$activeShop = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
if ( isset( $_GET['section'] ) ) {

	$section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '';
}
update_option( 'ced_etsy_active_shop', trim( $activeShop ) );
?>
<div class="ced_etsy_loader">
	<img src="<?php echo esc_url( CED_ETSY_URL . 'admin/images/loading.gif' ); ?>" width="50px" height="50px" class="ced_etsy_loading_img" >
</div>
<div class="success-admin-notices is-dismissible"></div>
<div class="navigation-wrapper">
	<?php esc_attr( ced_etsy_cedcommerce_logo() ); ?>
	<ul class="navigation">
				<li>
					<?php
					$url = admin_url( 'admin.php?page=ced_etsy&section=settings&shop_name=' . $activeShop );
					?>
					<a href="<?php echo esc_attr( $url ); ?>" class="
						<?php
						if ( 'settings' == $section || 'add-shipping-profile' == $section ) {
							echo 'active'; }
						?>
							"><?php esc_html_e( 'Global Settings', 'woocommerce-etsy-integration' ); ?></a>
							</li>
								<li>
									<?php
									$url = admin_url( 'admin.php?page=ced_etsy&section=category-mapping&shop_name=' . $activeShop );
									?>
									<a class="
									<?php
									if ( 'category-mapping' == $section ) {
										echo 'active'; }
									?>
										" href="<?php echo esc_attr( $url ); ?>"><?php esc_html_e( 'Category Mapping', 'woocommerce-etsy-integration' ); ?></a>
									</li>
									<li>
										<?php
										$url = admin_url( 'admin.php?page=ced_etsy&section=profiles&shop_name=' . $activeShop );
										?>
										<a class="
										<?php
										if ( 'profiles' == $section || 'profile-edit' == $section ) {
											echo 'active'; }
										?>
											" href="<?php echo esc_attr( $url ); ?>"><?php esc_html_e( 'Profile', 'woocommerce-etsy-integration' ); ?></a>
										</li>
										<li>
											<?php
											$url = admin_url( 'admin.php?page=ced_etsy&section=products&shop_name=' . $activeShop );
											?>
											<a class="
											<?php
											if ( 'products' == $section ) {
												echo 'active'; }
											?>
												" href="<?php echo esc_attr( $url ); ?>"><?php esc_html_e( 'Products', 'woocommerce-etsy-integration' ); ?></a>
											</li>
											<li>
												<?php
												$url = admin_url( 'admin.php?page=ced_etsy&section=orders&shop_name=' . $activeShop );
												?>
												<a class="
												<?php
												if ( 'orders' == $section ) {
													echo 'active'; }
												?>
													" href="<?php echo esc_attr( $url ); ?>"><?php esc_html_e( 'Orders', 'woocommerce-etsy-integration' ); ?></a>
												</li>
												<li>
													<?php
													$url = admin_url( 'admin.php?page=ced_etsy&section=product-importer&shop_name=' . $activeShop );
													?>
													<a class="
													<?php
													if ( 'product-importer' == $section ) {
														echo 'active'; }
													?>
														" href="<?php echo esc_attr( $url ); ?>"><?php esc_html_e( 'Importer', 'woocommerce-etsy-integration' ); ?></a>
												</li>
												<li>
													<?php
													$url = admin_url( 'admin.php?page=ced_etsy&section=etsy-logs&shop_name=' . $activeShop );
													?>
													<a class="
													<?php
													if ( 'etsy-logs' == $section ) {
														echo 'active'; }
													?>
														" href="<?php echo esc_attr( $url ); ?>"><?php esc_html_e( 'Logs', 'woocommerce-etsy-integration' ); ?></a>
												</li>
											</ul>
											<?php
											$active = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';

											?>

										</div>
<?php esc_attr( display_support_html() ); ?>
