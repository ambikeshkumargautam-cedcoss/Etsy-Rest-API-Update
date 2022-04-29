<?php
namespace Cedcommerce\View\header;
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * 
 */
class CedEtsyEtsyHeader{

	public $shop_name;
	public $section;
	public $not_show;
	public function __construct($shop_name = '')
	{
		$this->shop_name = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
		if ( isset( $_GET['section'] ) ) {

			$this->section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '';
		}
		update_option( 'ced_etsy_active_shop', trim( $this->shop_name ) );
		print_r( $this->show_loading_image());
		print_r( $this->header_wrap_view( $this->section, $this->shop_name ) );
		esc_attr( display_support_html() );
	}

	public function show_loading_image(){
		return '<div class="ced_etsy_loader">
		<img src="'.esc_url( CED_ETSY_URL . 'admin/assets/images/loading.gif' ).'" width="50px" height="50px" class="ced_etsy_loading_img" >
		</div>';
	}

	public function header_wrap_view( $curnt_section='', $curnt_shopname=''){
		$view .= '<div class="success-admin-notices is-dismissible">
		</div><div class="navigation-wrapper">'.
			ced_etsy_cedcommerce_logo()
			.'
			<ul class="navigation">
			';
				$header_sections = $this->header_sections();
				$this->not_show = array('shipping-add', 'shipping-edit', 'profile-edit');
				foreach ($header_sections as $section => $name ) {
					if (in_array($section, $this->not_show )) {
						continue;
					}
						$view .= '<li>
							<a href="'.$this->section_url( $section, $this->shop_name ).'" class="'.$this->check_active( $this->section, $section ).'">'.$name.'</a>
						</li>';
				}
				$view .= '
			</ul>
		</div>
		</div>';

		return $view;
	}
	public function check_active($current_section, $view_sec ){
		
		// if ( in_array( $current_section, $this->not_show ) ) {
		// 	return 'active';
		// }

		if ( $current_section === $view_sec ) {
			return 'active';
		}else{
			return '';
		}
	}
	public function section_url( $section='', $shop_name='' ){
		if (empty( $section ) || empty( $shop_name ) ) {
			$section   = $this->section;
			$shop_name = $this->shop_name;
		}
		return admin_url( 'admin.php?page=ced_etsy&section='.$section.'&shop_name=' . $shop_name );
	}
	public function header_sections(){
		return array(
			'settings'         => 'Global Settings',
			'shipping'         => 'Shipping Profile',
			'shipping-edit'    => 'Edit Shippign Profile',
			'shipping-add'     => 'Add Shipping Profile',
			'category'         => 'Category Mapping',
			'profiles'         => 'Profile',
			'profile-edit'     => 'Profile Edit',
			'products'         => 'Products',
			'orders'           => 'Orders',
			'product-importer' => 'Importer',
			'etsy-logs'        => 'Logs'
		);
	}
}

$header = new CedEtsyEtsyHeader();
?>
