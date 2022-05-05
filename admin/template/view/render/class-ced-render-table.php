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

class Ced_Render_Table{
	public function __construct(){

	}

	public function table_open($class){
		return '<table class="'.$class.'">';
	}

	public function table_label($name){
	    return '<label for="'.$name.'">'.$name.'</label> : ';
	}

    public function table_input($type, $class, $name, $placeholder){
		return '<input type="'.$type.'" name="'.$name.'" class="'.$class.'" id="'.$id.'" placeholder="'.$placeholder.'"">';
	}

	public function table_textarea($name, $placeholder){
			return '<textarea name="'.$name.'" placeholder="'.$placeholder.'"></textarea>';
	}
	public function td( $in_params='' ){
		return '<td>' . $in_params . '</td>';
	}
	public function th( $in_params='' ){
		return '<th>' . $in_params . '</th>';
	}
	public function tr( $in_params=''){
		return '<tr>' . $in_params . '</tr>';
	}
	
	public function table_body( $in_params='' ){
		return '<tbody>' . $in_params . '</tbody>';
	}

	public function table_button($type, $text){
		return '<input type="'.$type.'" value="'.$text.'">';
	}

	public function table_close(){
		return '</table>';
	}

	public function label( $class='', $in='',$desc='' ){
		return '<label class="'.$class.'">'. $in . '</label></br>'.( $desc );
	}

	public function select( $name='', $option_array='', $selected='', $class='', $id='' ){
		$to_return .= '<select name="'.$name.'">';
		$retrun .= '<option value="">---Not mapped---</option>';
		foreach ( $option_array as $opt_key => $opt_value ) {
			if ($opt_key === $selected ) {
				$selected = 'selected';
			}
			$to_return .= '<option value="'. esc_attr( $opt_key ) . '"'. esc_attr($selected).'>' . esc_attr( $opt_value ) . '</option>';
		}
		$to_return .= '</select>';
		return $to_return;
	}

	public function check_box( $name = '', $is_checked = '', $class='' ){
		return '<label class="'.$class.'"><input type="checkbox" name="'.$name.'"  '.$is_checked.'><span class="slider round"></span></label>';
	}
}