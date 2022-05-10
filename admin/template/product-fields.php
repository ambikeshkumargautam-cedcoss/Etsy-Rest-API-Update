<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 *Used to render the Product Fields
 *
 * @since      1.0.0
 *
 * @package    Woocommerce etsy Integration
 * @subpackage Woocommerce etsy Integration/admin/helper
 */

if ( ! class_exists( 'Ced_Etsy_Product_Fields' ) ) {

	/**
	 * Single product related functionality.
	 *
	 * Manage all single product related functionality required for listing product on marketplaces.
	 *
	 * @since      1.0.0
	 * @package    Woocommerce etsy Integration
	 * @subpackage Woocommerce etsy Integration/admin/helper
	 */
	class Ced_Etsy_Product_Fields {

		/**
		 * The Instace of CED_etsy_product_fields.
		 *
		 * @since    1.0.0
		 * @var      $_instance   The Instance of CED_etsy_product_fields class.
		 */
		private static $_instance;

		/**
		 * CED_etsy_product_fields Instance.
		 *
		 * Ensures only one instance of CED_etsy_product_fields is loaded or can be loaded.
		 *
		 * @since 1.0.0
		 * @static
		 * @return CED_etsy_product_fields instance.
		 */
		public static function get_instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Get product custom fields for preparing
		 * product data information to send on different
		 * marketplaces accoding to there requirement.
		 *
		 * @since 1.0.0
		 * @param string $type  required|framework_specific|common
		 * @param bool   $ids  true|false
		 * @return array  fields array
		 */
		public static function get_custom_products_fields( $shop_name = '' ) {

			$active_shop = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : $shop_name;
			if ( empty( $active_shop ) ) {
				$active_shop = get_option( 'ced_etsy_shop_name', '' );
			}
			$shop_id            = get_etsy_shop_id( $active_shop );
			$sections           = array();
			if ( ! empty( $shop_id ) ) {
				$action             = "application/shops/{$shop_id}/sections";
				// Refresh token if isn't.
				do_action( 'ced_etsy_refresh_token', $active_shop );
				$shopSections = etsy_request()->get( $action, $active_shop );
				if ( isset( $shopSections['count'] ) && $shopSections['count'] >= 1 ) {
					$shopSections = $shopSections['results'];
					foreach ( $shopSections as $key => $value ) {
						$sections[ $value['shop_section_id'] ] = $value['title'];
					}
				}
			}

			$shipping_templates = array();

			$required_fields = array(
				array(
					'type'   => '_hidden',
					'id'     => '_umb_etsy_category',
					'fields' => array(
						'id'          => '_umb_etsy_category',
						'label'       => __( 'Etsy Category', 'woocommerce-etsy-integration' ),
						'desc_tip'    => true,
						'description' => __( 'Specify the Etsy category.', 'woocommerce-etsy-integration' ),
						'type'        => 'hidden',
						'class'       => 'wc_input_price',
					),
				),
				array(
					'type'   => '_select',
					'id'     => '_etsy_language',
					'fields' => array(
						'id'          => '_etsy_language',
						'label'       => __( 'Etsy Shop Language', 'woocommerce-etsy-integration' ),
						'desc_tip'    => true,
						'description' => __( 'Your etsy shop language.', 'woocommerce-etsy-integration' ),
						'type'        => 'select',
						'options'     => array(
							'en' => 'English',
							'de' => 'German',
							'es' => 'Spanish',
							'fr' => 'French',
							'it' => 'Italian',
							'ja' => 'Japanese',
							'nl' => 'Dutch',
							'pl' => 'Polish',
							'pt' => 'Portuguese',
							'ru' => 'Russian',
						),
						'is_required' => true,
						'class'       => 'wc_input_price',
					),
				),
				array(
					'type'   => '_text_input',
					'id'     => '_ced_etsy_title_pre',
					'fields' => array(
						'id'          => '_ced_etsy_title_pre',
						'label'       => __( 'Title Prefix', 'woocommerce-etsy-integration' ),
						'desc_tip'    => true,
						'description' => __( 'Text to be added before the title.', 'woocommerce-etsy-integration' ),
						'type'        => 'text',
						'is_required' => false,
						'class'       => 'wc_input_price',
					),
				),
				array(
					'type'   => '_text_input',
					'id'     => '_ced_etsy_title',
					'fields' => array(
						'id'          => '_ced_etsy_title',
						'label'       => __( 'Title', 'woocommerce-etsy-integration' ),
						'desc_tip'    => true,
						'description' => __( 'Title of the product to be uploaded on etsy.If left blank woocommerce title will be used.', 'woocommerce-etsy-integration' ),
						'type'        => 'text',
						'is_required' => true,
						'class'       => 'wc_input_price',
					),
				),
				array(
					'type'   => '_text_input',
					'id'     => '_ced_etsy_title_post',
					'fields' => array(
						'id'          => '_ced_etsy_title_post',
						'label'       => __( 'Title Suffix', 'woocommerce-etsy-integration' ),
						'desc_tip'    => true,
						'description' => __( 'Text to be added after the title.', 'woocommerce-etsy-integration' ),
						'type'        => 'text',
						'is_required' => false,
						'class'       => 'wc_input_price',
					),
				),
				array(
					'type'   => '_text_input',
					'id'     => '_ced_etsy_description_pre',
					'fields' => array(
						'id'          => '_ced_etsy_description_pre',
						'label'       => __( 'Description Prefix', 'woocommerce-etsy-integration' ),
						'desc_tip'    => true,
						'description' => __( 'Text to be added before the Description.', 'woocommerce-etsy-integration' ),
						'type'        => 'text',
						'is_required' => false,
						'class'       => 'wc_input_price',
					),
				),
				array(
					'type'   => '_text_input',
					'id'     => '_ced_etsy_description',
					'fields' => array(
						'id'          => '_ced_etsy_description',
						'label'       => __( 'Description', 'woocommerce-etsy-integration' ),
						'desc_tip'    => true,
						'description' => __( 'Description of the product to be uploaded on etsy.If left blank woocommerce description will be used.', 'woocommerce-etsy-integration' ),
						'type'        => 'text',
						'is_required' => true,
						'class'       => 'wc_input_price',
					),
				),
				array(
					'type'   => '_text_input',
					'id'     => '_ced_etsy_description_post',
					'fields' => array(
						'id'          => '_ced_etsy_description_post',
						'label'       => __( 'Description Suffix', 'woocommerce-etsy-integration' ),
						'desc_tip'    => true,
						'description' => __( 'Text to be added after the Description.', 'woocommerce-etsy-integration' ),
						'type'        => 'text',
						'is_required' => false,
						'class'       => 'wc_input_price',
					),
				),
				array(
					'type'   => '_text_input',
					'id'     => '_ced_etsy_price',
					'fields' => array(
						'id'          => '_ced_etsy_price',
						'label'       => __( 'Price', 'woocommerce-etsy-integration' ),
						'desc_tip'    => true,
						'description' => __( 'Price of the product to be uploaded on etsy.If left blank woocommerce price will be used.', 'woocommerce-etsy-integration' ),
						'type'        => 'text',
						'is_required' => true,
						'class'       => 'wc_input_price',
					),
				),
				array(
					'type'     => '_select',
					'id'       => '_ced_etsy_markup_type',
					'fields'   => array(
						'id'          => '_ced_etsy_markup_type',
						'label'       => __( 'Increase Price By', 'woocommerce-etsy-integration' ),
						'desc_tip'    => true,
						'description' => __( 'Increase price by a certain amount in the actual price of the product when uploading on etsy.', 'woocommerce-etsy-integration' ),
						'type'        => 'select',
						'options'     => array(
							'Fixed_Increased'      => __( 'Fixed Increase' ),
							'Percentage_Increased' => __( 'Percentage Increase' ),
						),
						'class'       => 'wc_input_price',
					),
					'required' => false,
				),
				array(
					'type'   => '_text_input',
					'id'     => '_ced_etsy_markup_value',
					'fields' => array(
						'id'          => '_ced_etsy_markup_value',
						'label'       => __( 'Markup Value', 'woocommerce-etsy-integration' ),
						'desc_tip'    => true,
						'description' => __( 'Enter the markup value to be added in the price. Eg : 10', 'woocommerce-etsy-integration' ),
						'type'        => 'text',
						'is_required' => false,
						'class'       => 'wc_input_price',
					),
				),

				array(
					'type'   => '_text_input',
					'id'     => '_ced_etsy_stock',
					'fields' => array(
						'id'          => '_ced_etsy_stock',
						'label'       => __( 'Quantity', 'woocommerce-etsy-integration' ),
						'desc_tip'    => true,
						'description' => __( 'Quantity [ Stock ] of the product to be uploaded on etsy.If left blank quantity price will be used.', 'woocommerce-etsy-integration' ),
						'type'        => 'text',
						'is_required' => true,
						'class'       => 'wc_input_price',
					),
				),
				array(
					'type'   => '_text_input',
					'id'     => '_ced_etsy_tags',
					'fields' => array(
						'id'          => '_ced_etsy_tags',
						'label'       => __( 'Product Tags', 'woocommerce-etsy-integration' ),
						'desc_tip'    => true,
						'description' => __( 'Product tags. Enter upto 13 tags comma ( , ) separated. Do not include special characters.', 'woocommerce-etsy-integration' ),
						'type'        => 'text',
						'is_required' => false,
						'class'       => 'wc_input_price',
					),
				),
				array(
					'type'   => '_text_input',
					'id'     => '_ced_etsy_materials',
					'fields' => array(
						'id'          => '_ced_etsy_materials',
						'label'       => __( 'Product Materials', 'woocommerce-etsy-integration' ),
						'desc_tip'    => true,
						'description' => __( 'Product Materials. Enter upto 13 materials comma ( , ) separated. Do not include special characters.', 'woocommerce-etsy-integration' ),
						'type'        => 'text',
						'is_required' => false,
						'class'       => 'wc_input_price',
					),
				),
				array(
					'type'   => '_select',
					'id'     => '_ced_etsy_shop_section',
					'fields' => array(
						'id'          => '_ced_etsy_shop_section',
						'label'       => __( 'Shop Section', 'woocommerce-etsy-integration' ),
						'desc_tip'    => true,
						'description' => __( 'Shop section for the products . The products will be listed in the section on etsy if selected.', 'woocommerce-etsy-integration' ),
						'type'        => 'select',
						'options'     => $sections,
						'is_required' => false,
						'class'       => 'wc_input_price',
					),
				),
				array(
					'type'   => '_select',
					'id'     => '_ced_etsy_shipping_profile',
					'fields' => array(
						'id'          => '_ced_etsy_shipping_profile',
						'label'       => __( 'Shipping Profile', 'woocommerce-etsy-integration' ),
						'desc_tip'    => true,
						'description' => __( 'Shipping profile to be used for products while uploading on etsy.', 'woocommerce-etsy-integration' ),
						'type'        => 'select',
						'options'     => $shipping_templates,
						'is_required' => true,
						'class'       => 'wc_input_price',
					),
				),
				array(
					'type'   => '_select',
					'id'     => '_ced_etsy_product_list_type',
					'fields' => array(
						'id'          => '_ced_etsy_product_list_type',
						'label'       => __( 'Product Listing Type', 'woocommerce-etsy-integration' ),
						'desc_tip'    => true,
						'description' => __( 'Product Listing type , whether you want to upload the product in active or draft', 'woocommerce-etsy-integration' ),
						'type'        => 'select',
						'options'     => array(
							'draft'  => 'Draft',
							'active' => 'Active',
						),
						'is_required' => false,
						'class'       => 'wc_input_price',
					),
				),
				array(
					'type'   => '_text_input',
					'id'     => '_ced_etsy_processing_min',
					'fields' => array(
						'id'          => '_ced_etsy_processing_min',
						'label'       => __( 'Processing Min', 'woocommerce-etsy-integration' ),
						'desc_tip'    => true,
						'description' => __( 'The minimum number of days for processing for this listing.', 'woocommerce-etsy-integration' ),
						'type'        => 'text',
						'is_required' => true,
						'class'       => 'wc_input_price',
					),
				),
				array(
					'type'   => '_text_input',
					'id'     => '_ced_etsy_processing_max',
					'fields' => array(
						'id'          => '_ced_etsy_processing_max',
						'label'       => __( 'Processing Max', 'woocommerce-etsy-integration' ),
						'desc_tip'    => true,
						'description' => __( 'The maximum number of days for processing for this listing.', 'woocommerce-etsy-integration' ),
						'type'        => 'text',
						'is_required' => true,
						'class'       => 'wc_input_price',
					),
				),
				array(
					'type'   => '_select',
					'id'     => '_ced_etsy_who_made',
					'fields' => array(
						'id'          => '_ced_etsy_who_made',
						'label'       => __( 'Who Made', 'woocommerce-etsy-integration' ),
						'desc_tip'    => true,
						'description' => __( 'Who made the item being listed.', 'woocommerce-etsy-integration' ),
						'type'        => 'select',
						'options'     => array(
							'i_did'        => 'I Did',
							'collective'   => 'Collective',
							'someone_else' => 'Someone Else',
						),
						'is_required' => true,
						'class'       => 'wc_input_price',
					),
				),
				array(
					'type'   => '_select',
					'id'     => '_ced_etsy_product_supply',
					'fields' => array(
						'id'          => '_ced_etsy_product_supply',
						'label'       => __( 'Product Supply', 'woocommerce-etsy-integration' ),
						'desc_tip'    => true,
						'description' => __( 'Use of the products.', 'woocommerce-etsy-integration' ),
						'type'        => 'select',
						'options'     => array(
							'true'  => 'A supply or tool to make things',
							'false' => 'A finished product',
						),
						'is_required' => true,
						'class'       => 'wc_input_price',
					),
				),
				array(
					'type'   => '_select',
					'id'     => '_ced_etsy_when_made',
					'fields' => array(
						'id'          => '_ced_etsy_when_made',
						'label'       => __( 'Manufacturing Year', 'woocommerce-etsy-integration' ),
						'desc_tip'    => true,
						'description' => __( 'When was the item made.', 'woocommerce-etsy-integration' ),
						'type'        => 'select',
						'options'     => array(
							'made_to_order' => 'Made to Order',
							'2020_2021'     => '2020-2021',
							'2010_2019'     => '2010-2019',
							'2002_2009'     => '2002-2009',
							'before_2002'   => 'Before 2002',
							'2000_2001'     => '2000-2001',
							'1990s'         => '1990s',
							'1980s'         => '1980s',
							'1970s'         => '1970s',
							'1960s'         => '1960s',
							'1950s'         => '1950s',
							'1940s'         => '1940s',
							'1930s'         => '1930s',
							'1920s'         => '1920s',
							'1910s'         => '1910s',
							'1900s'         => '1900s',
							'1800s'         => '1800s',
							'1700s'         => '1700s',
							'before_1700'   => 'Before 1700',
						),
						'is_required' => true,
						'class'       => 'wc_input_price',
					),
				),
				array(
					'type'   => '_select',
					'id'     => '_ced_etsy_recipient',
					'fields' => array(
						'id'          => '_ced_etsy_recipient',
						'label'       => __( 'Preferred Audience (Recipient)', 'woocommerce-etsy-integration' ),
						'desc_tip'    => true,
						'description' => __( 'Preferred Audience or Recipient to use the product.', 'woocommerce-etsy-integration' ),
						'type'        => 'select',
						'options'     => array(
							'men'           => 'Men',
							'women'         => 'Women',
							'unisex_adults' => 'Unisex Adults',
							'teen_boys'     => 'Teen Boys',
							'teen_girls'    => 'Teen Girls',
							'teens'         => 'Teens',
							'boys'          => 'Boys',
							'girls'         => 'Girls',
							'children'      => 'Children',
							'baby_boys'     => 'Baby Boys',
							'baby_girls'    => 'Baby Girls',
							'babies'        => 'Babies',
							'birds'         => 'Birds',
							'cats'          => 'Cats',
							'dogs'          => 'Dogs',
							'pets'          => 'Pets',
							'not_specified' => 'Not Specified',
						),
						'is_required' => false,
						'class'       => 'wc_input_price',
					),
				),
				array(
					'type'   => '_select',
					'id'     => '_ced_etsy_occasion',
					'fields' => array(
						'id'          => '_ced_etsy_occasion',
						'label'       => __( 'Occasion', 'woocommerce-etsy-integration' ),
						'desc_tip'    => true,
						'description' => __( 'What is the occasion for this listing.', 'woocommerce-etsy-integration' ),
						'type'        => 'select',
						'options'     => array(
							'anniversary'        => 'Anniversary',
							'baptism'            => 'Baptism',
							'bar_or_bat_mitzvah' => 'Bar or Bat Mitzvah',
							'birthday'           => 'Birthday',
							'canada_day'         => 'Canada Day',
							'chinese_new_year'   => 'Chinese New Year',
							'cinco_de_mayo'      => 'Cinco De Mayo',
							'confirmation'       => 'Confirmation',
							'christmas'          => 'Christmas',
							'day_of_the_dead'    => 'Day of the Dead',
							'easter'             => 'Easter',
							'eid'                => 'Eid',
							'engagement'         => 'Engagement',
							'fathers_day'        => 'Fathers Day',
							'get_well'           => 'Get Well',
							'graduation'         => 'Graduation',
							'halloween'          => 'Halloween',
							'hanukkah'           => 'Hanukkah',
							'housewarming'       => 'Housewarming',
							'kwanzaa'            => 'Kwanzaa',
							'prom'               => 'Prom',
							'july_4th'           => 'July 4th',
							'mothers_day'        => 'Mothers Day',
							'new_baby'           => 'New Baby',
							'new_years'          => 'New Years',
							'quinceanera'        => 'Quinceanera',
							'retirement'         => 'Retirement',
							'st_patricks_day'    => 'St. Patricks Day',
							'sweet_16'           => 'Sweet 16',
							'sympathy'           => 'Sympathy',
							'thanksgiving'       => 'Thanks Giving',
							'valentines'         => 'Valentines',
							'wedding'            => 'Wedding',
						),
						'is_required' => false,
						'class'       => 'wc_input_price',
					),
				),

			);

			return $required_fields;
		}

		/*
		* Function to render input text html
		*/
		public function renderInputTextHTML( $attribute_id, $attribute_name, $categoryID, $productID, $marketPlace, $attribute_description = null, $indexToUse, $additionalInfo = array( 'case' => 'product' ), $conditionally_required = false, $conditionally_required_text = '' ) {
			global $post,$product,$loop;
			$fieldName = $categoryID . '_' . $attribute_id;
			if ( 'product' == $additionalInfo['case'] ) {
				$previousValue = get_post_meta( $productID, $fieldName, true );
			} else {
				$previousValue = $additionalInfo['value'];
			}

			?>
			<!-- <p class="form-field _umb_brand_field "> -->
				<input type="hidden" name="<?php echo esc_attr( $marketPlace . '[]' ); ?>" value="<?php echo esc_attr( $fieldName ); ?>" />
				<td>
					<label for=""><?php echo esc_attr( $attribute_name ); ?></label>
					<?php
					if ( $conditionally_required ) {
						?>
						<span style="color: red; margin-left:5px; ">*</span>
						<?php
					}
					if ( ! is_null( $attribute_description ) && ! empty( $attribute_description ) ) {
						ced_etsy_tool_tip( $attribute_description );
					}

					?>
				</td>

				<td>
					<input class="short" style="" name="<?php echo esc_attr( $fieldName . '[' . $indexToUse . ']' ); ?>" id="" value="<?php echo esc_attr( $previousValue ); ?>" placeholder="" type="text" /> 
				</td>

				<!-- </p> -->
				<?php
		}

		/*
		* Function to render input text html
		*/
		public function rendercheckboxHTML( $attribute_id, $attribute_name, $categoryID, $productID, $marketPlace, $attribute_description = null, $indexToUse, $additionalInfo = array( 'case' => 'product' ), $conditionally_required = false, $conditionally_required_text = '' ) {

			global $post,$product,$loop;
			$fieldName = $categoryID . '_' . $attribute_id;
			if ( 'product' == $additionalInfo['case'] ) {
				$previousValue = get_post_meta( $productID, $fieldName, true );
			} else {
				$checked = ( 'yes' == $additionalInfo['value'] ) ? 'checked="checked"' : '';
			}

			?>
			<input type="hidden" name="<?php echo esc_attr( $marketPlace . '[]' ); ?>" value="<?php echo esc_attr( $fieldName ); ?>" />
			<td>
				<label for=""><?php echo esc_attr( $attribute_name ); ?>
			</label>
			<?php
			if ( ! is_null( $attribute_description ) && ! empty( $attribute_description ) ) {
				ced_etsy_tool_tip( $attribute_description );
			}

			?>
		</td>
		<td>
			<input class="short" style="" name="<?php echo esc_attr( $fieldName . '[' . $indexToUse . ']' ); ?>" id="" value="<?php echo esc_attr( 'yes' ); ?>" placeholder="" <?php echo esc_attr( $checked ); ?> type="checkbox" /> 
		</td>

		<!-- </p> -->
			<?php
		}

		/*
		* Function to render dropdown html
		*/
		public function renderDropdownHTML( $attribute_id, $attribute_name, $values, $categoryID, $productID, $marketPlace, $attribute_description = null, $indexToUse, $additionalInfo = array( 'case' => 'product' ), $is_required = false ) {
			$fieldName = $categoryID . '_' . $attribute_id;
			if ( 'product' == $additionalInfo['case'] ) {
				$previousValue = get_post_meta( $productID, $fieldName, true );
			} else {
				$previousValue = $additionalInfo['value'];
			}
			?>
			<!-- <p class="form-field _umb_id_type_field "> -->
				<input type="hidden" name="<?php echo esc_attr( $marketPlace . '[]' ); ?>" value="<?php echo esc_attr( $fieldName ); ?>" />
				<td>
					<label for=""><?php echo esc_attr( $attribute_name ); ?></label>
					<?php
					if ( $is_required ) {
						?>
						<span style="color: red; margin-left:5px; ">*</span>
						<?php
					}
					if ( ! is_null( $attribute_description ) && ! empty( $attribute_description ) ) {
						ced_etsy_tool_tip( $attribute_description );
					}
					?>
				</td>
				<td>
					<select id="" name="<?php echo esc_attr( $fieldName . '[' . $indexToUse . ']' ); ?>" class="select short" style="">
						<?php
						echo '<option value="">-- Select --</option>';
						foreach ( $values as $key => $value ) {
							if ( $previousValue == $key ) {
								echo '<option value="' . esc_attr( $key ) . '" selected>' . esc_attr( $value ) . '</option>';
							} else {
								echo '<option value="' . esc_attr( $key ) . '">' . esc_attr( $value ) . '</option>';
							}
						}
						?>
					</select>
				</td>

				<!-- </p> -->
				<?php
		}

		public function renderInputTextHTMLhidden( $attribute_id, $attribute_name, $categoryID, $productID, $marketPlace, $attribute_description = null, $indexToUse, $additionalInfo = array( 'case' => 'product' ), $conditionally_required = false, $conditionally_required_text = '' ) {
			global $post,$product,$loop;
			$fieldName = $categoryID . '_' . $attribute_id;
			if ( 'product' == $additionalInfo['case'] ) {
				$previousValue = get_post_meta( $productID, $fieldName, true );
			} else {
				$previousValue = $additionalInfo['value'];
			}

			?>

				<input type="hidden" name="<?php echo esc_attr( $marketPlace . '[]' ); ?>" value="<?php echo esc_attr( $fieldName ); ?>" />
				<td>
				</label>
			</td>
			<td>
				<label></label>
				<input class="short" style="" name="<?php echo esc_attr( $fieldName . '[' . $indexToUse . ']' ); ?>" id="" value="<?php echo esc_attr( $previousValue ); ?>" placeholder="" type="hidden" /> 
			</td>

			<?php
		}

		public function get_taxonomy_node_properties( $getTaxonomyNodeProperties ) {

			$taxonomyList = array();
			if ( isset( $getTaxonomyNodeProperties ) && is_array( $getTaxonomyNodeProperties ) && ! empty( $getTaxonomyNodeProperties ) ) {
				foreach ( $getTaxonomyNodeProperties as $getTaxonomyNodeProperties_key => $getTaxonomyNodeProperties_value ) {
					$type             = '';
					$taxonomy_options = array();
					if ( isset( $getTaxonomyNodeProperties_value['possible_values'] ) && is_array( $getTaxonomyNodeProperties_value['possible_values'] ) && ! empty( $getTaxonomyNodeProperties_value['possible_values'] ) ) {
						$type = '_select';
						foreach ( $getTaxonomyNodeProperties_value['possible_values'] as $possible_values_key => $possible_value ) {
							$taxonomy_options[ $possible_value['value_id'] ] = $possible_value['name'];
						}
					} else {
						$type = '_text_input';
					}
					if ( isset( $type ) && '_select' != $type ) {
						$taxonomyList[] = array(
							'type'   => $type,
							'id'     => '_ced_etsy_taxonomy_id_' . $getTaxonomyNodeProperties_value['property_id'],
							'fields' => array(
								'id'          => '_ced_etsy_property_id_' . $getTaxonomyNodeProperties_value['property_id'],
								'label'       => $getTaxonomyNodeProperties_value['name'],
								'desc_tip'    => true,
								'description' => /*$variation_category_attribute_property_value['description']*/ $getTaxonomyNodeProperties_value['name'],
								'type'        => 'text',
								'class'       => 'wc_input_price',
							),
						);
					} else {
						$taxonomyList[] = array(
							'type'   => $type,
							'id'     => '_ced_etsy_taxonomy_id_' . $getTaxonomyNodeProperties_value['property_id'],
							'fields' => array(
								'id'          => '_ced_etsy_property_id_' . $getTaxonomyNodeProperties_value['property_id'],
								'label'       => $getTaxonomyNodeProperties_value['name'],
								'desc_tip'    => true,
								'description' => /* $variation_category_attribute_property_value['description']*/ $getTaxonomyNodeProperties_value['name'],
								'type'        => 'text',
								'options'     => $taxonomy_options,
								'class'       => 'wc_input_price',
							),
						);
					}
				}
			}
			return $taxonomyList;
		}
	}
}
