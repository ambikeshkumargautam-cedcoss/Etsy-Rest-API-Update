<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

Cedhandler::ced_header();


?>
<div class="ced_etsy_heading ">
	<?php echo esc_html_e( get_etsy_instuctions_html() ); ?>
	<div class="ced_etsy_child_element">
		<?php
				$activeShop = isset( $_GET['shop_name'] ) ? sanitize_text_field( $_GET['shop_name'] ) : '';
				$instructions = array(
					'In this section all the configuration related to product and order sync are provided.',
					'It is mandatory to fill/map the required attributes [ <span style="color:red;">*</span> ] in <a>Product Export Settings</a> section.',
					'View the  information of each attribute using the tooltip icon next to the attribute name.',
					'The <a>Metakeys and Attributes List</a> section will help you to choose the required metakey or attribute on which the product information is stored.These metakeys or attributes will furthur be used in <a>Product Export Settings</a> for listing products on etsy from woocommerce.',
					'For selecting the required metakey or attribute expand the <a>Metakeys and Attributes List</a> section enter the product name/keywords and list will be displayed under that . Select the metakey or attribute as per requirement and save settings.',
					'Configure the order related settings in <a>Order Import Settings</a>.',
					'Choose the Shiping profile in <a>Shipping Profiles</a> to be used while listing a product on etsy from woocommerce or you can also add new using <a>Add New</a> button.',
					'To automate the process related to inventory , order and import product sync , enable the features as per requirement in <a>Schedulers</a>.',
				);
				echo '<ul class="ced_etsy_instruction_list" type="disc">';
				foreach ( $instructions as $instruction ) {
					print_r( "<li>$instruction</li>" );
				}
				echo '</ul>';

				?>
	</div>
</div>
<div class="ced_etsy_heading ">
	
</div>