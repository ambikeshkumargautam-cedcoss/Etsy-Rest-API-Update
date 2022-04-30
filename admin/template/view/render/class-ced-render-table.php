<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
namespace Cedcommerce\View\Render\Table;
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://cedcommerce.com
 * @since      1.0.0
 *
 * @package    Woocommmerce_Etsy_Integration
 * @subpackage Woocommmerce_Etsy_Integration/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Woocommmerce_Etsy_Integration
 * @subpackage Woocommmerce_Etsy_Integration/admin
 */

class Ced_Render_Table{
	public function __construct(){

	}

	public function table_open($class){
		return '';
	}

	public function table_label($name){
	    return '<label for="'.$name.'">'.$name.'</label> : ';
	}

    public function table_input($type, ,$class, $name, $placeholder){
		return '<input type="'.$type.'" name="'.$name.'" class="'.$class.'" id="'.$id.'" placeholder="'.$placeholder.'"">';
	}

	public function table_textarea($name, $placeholder){
			return '<textarea name="'.$name.'" placeholder="'.$placeholder.'"></textarea>';
	}

	public function table_head(){

	}
	
	public function table_body(){

	}

	public function table_button($type, $text){
		
		return '<input type="'.$type.'" value="'.$text.'">';
	}

	public function table_close(){
		return '';
	}
}