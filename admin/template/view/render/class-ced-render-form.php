<?php
namespace Cedcommerce\view\render;
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
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
class Ced_Render_Form{
	public function __construct(){
		
	}
	public function ced_nonce($my_action, $nonce_field ) {
		return wp_nonce_field( $my_action, $nonce_field );
	}
	public function form_open($method='', $action='' ){
		return '<form  method="'.$method.'" action "'.$action.'">';
	}
	public function form_label($name = '' ){
	    return '<label for="'.$name.'">'.$name.'</label> : ';
	}

    public function form_input($type='', $class='', $name='', $placeholder=''){
		return '<input type="'.$type.'" name="'.$name.'" class="'.$class.'" id="'.$id.'" placeholder="'.$placeholder.'"">';
	}
	public function form_textarea($name = '', $placeholder='' ){
			return '<textarea name="'.$name.'" placeholder="'.$placeholder.'"></textarea>';
	}
	public function button( $id="",$class="" ,$type="" ,$name="", $in_text='' ){
		return '<button id="'.$id.'" class="'.$class.'" type="'.$type.'" name="'.$name.'">'. $in_text.'</button>';
	}
	public function form_close(){
		return '</form>';
	}
}