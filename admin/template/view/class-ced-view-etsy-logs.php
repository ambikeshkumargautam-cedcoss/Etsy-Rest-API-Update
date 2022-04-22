<?php
$file = CED_ETSY_DIRPATH . 'admin/partials/header.php';
if ( file_exists( $file ) ) {
	require_once $file;
}

$shop_name =isset($_GET['shop_name']) ? sanitize_text_field( $_GET['shop_name'] ) :'';

if ( isset( $_POST['ced_etsy_product_log_submit'] ) ) {
		$date = gmdate("d-m-y", strtotime($_POST['ced_etsy_product_log_date']));	
		wp_redirect( admin_url( 'admin.php?page=ced_etsy&section=etsy-logs&shop_name='.$shop_name.'&date=' . $date ) );
		exit;
}

$log_types = array( 'general','product','inventory','order' );
$content = '<p class="etsy-log-error">No logs to display !</p>';
$date = isset($_GET['date']) ? sanitize_text_field( $_GET['date'] ) : date( 'd-m-y' );
if( file_exists(CED_ETSY_LOG_DIRECTORY . "/general/" . $date . ".log") ) {
	$content = "<p class='etsy-log-content'>" . @file_get_contents(CED_ETSY_LOG_DIRECTORY . "/general/" . $date . ".log") . "</p>";
}
?>
<form method="post" action="">
	<div>
		<input type="date" name="ced_etsy_product_log_date" value="<?php esc_html_e( $date ); ?>">
	<button id=""  name="ced_etsy_product_log_submit" class="button-primary" ><?php esc_html_e( 'Filter', 'woocommerce-etsy-integration' ); ?></button>
	</div>
</form>
<div class="ced_etsy_heading">
	<div class="ced_etsy_log_wrapper">
		<div class="ced_etsy_parent_element">
			<?php
			echo nl2br($content);
			?>
		</div>
	</div>
</div>
