<?php
use Cedcommerce\EtsyManager\Ced_Etsy_Manager as EtsyManager;
use Cedcommerce\Order\GetOrders\Ced_Order_Get as EtsyGetOrdes;
use Cedcommerce\Product\ProductUpload\Ced_Product_Upload as EtsyUploadProducts;
use Cedcommerce\Product\ProductImport\Ced_Product_Import as EtsyImportProducts;
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
class Woocommmerce_Etsy_Integration_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->ced_etsy_mager   = EtsyManager::get_instance();
		$this->ced_etsy_order   = EtsyGetOrdes::get_instance();
		$this->ced_etsy_product = EtsyUploadProducts::get_instance();
		$this->importProduct    = EtsyImportProducts::get_instance();
		$this->plugin_name      = $plugin_name;
		add_action( 'manage_edit-shop_order_columns', array( $this, 'ced_etsy_add_table_columns' ) );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'ced_etsy_manage_table_columns' ), 10, 2 );
		add_action( 'wp_ajax_ced_etsy_auto_upload_categories', array( $this, 'ced_etsy_auto_upload_categories' ) );

		// product webhook action.
		add_action( 'wp_ajax_ced_etsy_product_webhook', array( $this, 'ced_etsy_product_webhook' ) );
		add_action( 'wp_ajax_nopriv_ced_etsy_product_webhook', array( $this, 'ced_etsy_product_webhook' ) );	
		// order webhook action.
		add_action( 'wp_ajax_ced_etsy_order_webhook', array( $this, 'ced_etsy_order_webhook' ) );
		add_action( 'wp_ajax_nopriv_ced_etsy_order_webhook', array( $this, 'ced_etsy_order_webhook' ) );
	}
	
	/**
	 * Webhook while updating the product in Woocommerce.
	 *
	 * @since    2.0.8
	 */
	public function ced_etsy_product_webhook() {

		$data      = file_get_contents( 'php://input' );
		$events    = json_decode( $data, true );
		$id        = $events['id'];
		$shop_name = get_option( 'ced_etsy_shop_name', '' );

		if ( ! empty( $id ) ) {
			$upload_dir      = ced_upload_dir_etsy();
			$fp              = fopen( $upload_dir . '/ced_etsy_product_webhook' . gmdate( 'j.n.Y' ) . '.log', 'a' );
			$ced_etsy_log   .= 'Time:' . gmdate( 'h:i:sa' ) . "\r\n";
			$ced_etsy_log   .= 'Date :' . date_i18n( 'Y-m-d H:i:s' ) . "\r\nMessage : processing of Product Webhook : Product Id - " . $id . "\r\n";
			$ced_etsy_log   .= 'etsy Product ID :' . $id . "\r\n";
			$response        = $this->ced_etsy_product->prepareDataForUpdating( array( $id ), $shop_name );
			$inventory_log[] = $response;
			$ced_etsy_log    = $response;
			fwrite( $fp, $ced_etsy_log );
			fclose( $fp );

		}

	}

	/**
	 * Webhook whle creating order on Woocommerce.
	 *
	 * @since    2.0.8
	 */
	public function ced_etsy_order_webhook() {

		$data      = file_get_contents( 'php://input' );
		$events    = json_decode( $data, true );
		$order_id  = $events['id'];
		$shop_name = get_option( 'ced_etsy_shop_name', '' );
		if ( ! empty( $order_id ) ) {
			$upload_dir      = ced_upload_dir_etsy();
			$fp              = fopen( $upload_dir . '/ced_etsy_order_webhook' . gmdate( 'j.n.Y' ) . '.log', 'a' );
			$ced_etsy_log   .= 'Time:' . gmdate( 'h:i:sa' ) . "\r\n";
			$ced_etsy_log   .= 'Date :' . date_i18n( 'Y-m-d H:i:s' ) . "\r\nMessage : processing of order Webhook : Product Id - " . $order_id . "\r\n";
			$ced_etsy_log   .= 'etsy Product ID :' . $order_id . "\r\n";
			$response        = $this->ced_etsy_product->prepareDataForUpdatingInventory( array( $order_id ), $shop_name );
			$inventory_log[] = $response;
			$ced_etsy_log    = $response;
			fwrite( $fp, $ced_etsy_log );
			fclose( $fp );
		}

	}

	public function ced_etsy_auto_upload_categories() {
		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$sanitized_array        = filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING );
			$woo_categories         = isset( $sanitized_array['categories'] ) ? json_decode( $sanitized_array['categories'], true ) : array();
			$shop_name              = isset( $sanitized_array['shop_name'] ) ? $sanitized_array['shop_name'] : '';
			$operation              = isset( $sanitized_array['operation'] ) ? sanitize_text_field( $sanitized_array['operation'] ) : 'save';
			$auto_upload_categories = get_option( 'ced_etsy_auto_upload_categories_' . $shop_name, array() );
			if ( 'save' == $operation ) {
				$auto_upload_categories = array_merge( $auto_upload_categories, $woo_categories );
				$message                = 'Category added in auto upload queue';
			} elseif ( 'remove' == $operation ) {
				$auto_upload_categories = array_diff( $auto_upload_categories, $woo_categories );
				$auto_upload_categories = array_values( $auto_upload_categories );
				$message                = 'Category removed from auto upload queue';
			}
			$auto_upload_categories = array_unique( $auto_upload_categories );
			update_option( 'ced_etsy_auto_upload_categories_' . $shop_name, $auto_upload_categories );
			echo json_encode(
				array(
					'status'  => 200,
					'message' => $message,
				)
			);
			wp_die();
		}
	}

	public function ced_etsy_add_table_columns( $columns ) {
		$modified_columns = array();
		foreach ( $columns as $key => $value ) {
			$modified_columns[ $key ] = $value;
			if ( 'order_number' == $key ) {
				$modified_columns['order_from'] = '<span title="Order source">Order source</span>';
			}
		}
		return $modified_columns;
	}


	public function ced_etsy_manage_table_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'order_from':
				$_ced_etsy_order_id = get_post_meta( $post_id, '_ced_etsy_order_id', true );
				if ( ! empty( $_ced_etsy_order_id ) ) {
					$etsy_icon = CED_ETSY_URL . 'admin/images/etsy.png';
					echo '<p><img src="' . esc_url( $etsy_icon ) . '" height="35" width="60"></p>';
				}
		}
	}



	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		global $pagenow;
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woocommmerce_Etsy_Integration_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woocommmerce_Etsy_Integration_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( 'ced-boot-css', 'https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css', array(), '2.0.0', 'all' );
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . '/assets/css/woocommmerce-etsy-integration-admin.css', array(), $this->version, 'all' );

		//SelectWoo Css
		wp_register_style( 'selectWoo', plugin_dir_url( __FILE__ ) . 'assets/css/selectWoo.min.css', array(), '1.0.0', 'all' );
		wp_enqueue_style( 'selectWoo' );

		if ( 'plugins.php' == $pagenow ) {
			wp_enqueue_style( 'ced-etsy-uninstall-css', plugin_dir_url( __FILE__ ) . 'assets/css/etsy-integration-for-woocommerce-uninstall.css', array(), $this->version, 'all' );
		}
		if ( isset($_GET['page']) && $_GET['page'] == "ced_etsy" ) {
			wp_enqueue_style( 'ced-bootstrap-etsy', 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css', array(), $this->version, 'all' );
		}

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		global $pagenow;
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woocommmerce_Etsy_Integration_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woocommmerce_Etsy_Integration_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		// woocommerce style //
		wp_register_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION );
		wp_enqueue_style( 'woocommerce_admin_menu_styles' );
		wp_enqueue_style( 'woocommerce_admin_styles' );

		$params = array(
			/* translators: (%s): wc_get_price_decimal_separator */
			'i18n_mon_decimal_error'           => sprintf( __( 'Please enter in monetary decimal (%s) format without thousand separators and currency symbols.', 'woocommerce' ), wc_get_price_decimal_separator() ),
			'i18n_country_iso_error'           => __( 'Please enter in country code with two capital letters.', 'woocommerce' ),
			'i18_sale_less_than_regular_error' => __( 'Please enter in a value less than the regular price.', 'woocommerce' ),

			'mon_decimal_point'                => wc_get_price_decimal_separator(),
			'strings'                          => array(
				'import_products' => __( 'Import', 'woocommerce' ),
				'export_products' => __( 'Export', 'woocommerce' ),
			),
			'urls'                             => array(
				'import_products' => esc_url_raw( admin_url( 'edit.php?post_type=product&page=product_importer' ) ),
				'export_products' => esc_url_raw( admin_url( 'edit.php?post_type=product&page=product_exporter' ) ),
			),
		);
		// woocommerce script //

		$suffix = '';
		wp_register_script( 'woocommerce_admin', WC()->plugin_url() . '/assets/js/admin/woocommerce_admin' . $suffix . '.js', array( 'jquery', 'jquery-blockui', 'jquery-ui-sortable', 'jquery-ui-widget', 'jquery-ui-core', 'jquery-tiptip' ), WC_VERSION );
		wp_localize_script( 'woocommerce_admin', 'woocommerce_admin', $params );

		wp_register_script( 'jquery-tiptip', WC()->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip' . $suffix . '.js', array( 'jquery' ), WC_VERSION, true );

		wp_register_script( 'selectWoo', plugin_dir_url( __FILE__ ) . 'assets/js/selectWoo.min.js', array( 'jquery' ), $this->version, true );
		wp_enqueue_script( 'selectWoo');
		wp_enqueue_script( 'woocommerce_admin' );

		$shop_name = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'assets/js/woocommmerce-etsy-integration-admin.js', array( 'jquery' ), $this->version, false );

		// SelectWoo
		// wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'assets/js/selectWoo.min.js', array( 'jquery' ), $this->version, false );

		$ajax_nonce     = wp_create_nonce( 'ced-etsy-ajax-seurity-string' );
		$localize_array = array(
			'ajax_url'   => admin_url( 'admin-ajax.php' ),
			'ajax_nonce' => $ajax_nonce,
			'shop_name'  => $shop_name,
		);
		wp_localize_script( $this->plugin_name, 'ced_etsy_admin_obj', $localize_array );
		if ( 'plugins.php' == $pagenow ) {
			wp_enqueue_script( $this->plugin_name . '_uninstall', plugin_dir_url( __FILE__ ) . 'js/etsy-integration-for-woocommerce-uninstall.js', array( 'jquery' ), $this->version, false );
		}
		if( isset($_GET['page']) && $_GET['page'] == "ced_etsy" ){
			wp_enqueue_script( $this->plugin_name . '_bootstrapjs', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js', array( 'jquery' ), '2.0.0', false );
			wp_enqueue_script( $this->plugin_name . '_bootstrapjspopper', 'https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js', array( 'jquery' ), '2.0.0', false );
			wp_enqueue_script( $this->plugin_name . '_bootstrapmin', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js', array( 'jquery' ), '2.0.0', false );
		}
	}

	/**
	 * Add admin menus and submenus
	 *
	 * @since    1.0.0
	 */
	public function ced_etsy_add_menus() {
		global $submenu;
		if ( empty( $GLOBALS['admin_page_hooks']['cedcommerce-integrations'] ) ) {
			add_menu_page( __( 'CedCommerce', 'woocommerce-etsy-integration' ), __( 'CedCommerce', 'woocommerce-etsy-integration' ), 'manage_woocommerce', 'cedcommerce-integrations', array( $this, 'ced_marketplace_listing_page' ), plugins_url( 'woocommerce-etsy-integration/admin/assets/images/logo1.png' ), 12 );

			$menus = apply_filters( 'ced_add_marketplace_menus_array', array() );

			if ( is_array( $menus ) && ! empty( $menus ) ) {
				foreach ( $menus as $key => $value ) {
					add_submenu_page( 'cedcommerce-integrations', $value['name'], $value['name'], 'manage_woocommerce', $value['menu_link'], array( $value['instance'], $value['function'] ) );
				}
			}
			/*
			add_submenu_page( 'cedcommerce-integrations', "Additionals", "Additionals", 'manage_options', 'ced_additional', array( $this, 'ced_additional_page' ) );*/
		}
	}

	/**
	 * Woocommerce_Etsy_Integration_Admin ced_etsy_search_product_name.
	 *
	 * @since 1.0.0
	 */
	public function ced_etsy_search_product_name() {

		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$keyword      = isset( $_POST['keyword'] ) ? sanitize_text_field( $_POST['keyword'] ) : '';
			$product_list = '';
			if ( ! empty( $keyword ) ) {
				$arguements = array(
					'numberposts' => -1,
					'post_type'   => array( 'product', 'product_variation' ),
					's'           => $keyword,
				);
				$post_data  = get_posts( $arguements );
				if ( ! empty( $post_data ) ) {
					foreach ( $post_data as $key => $data ) {
						$product_list .= '<li class="ced_etsy_searched_product" data-post-id="' . esc_attr( $data->ID ) . '">' . esc_html( __( $data->post_title, 'etsy-woocommerce-integration' ) ) . '</li>';
					}
				} else {
					$product_list .= '<li>No products found.</li>';
				}
			} else {
				$product_list .= '<li>No products found.</li>';
			}
			echo json_encode( array( 'html' => $product_list ) );
			wp_die();
		}
	}


		/**
		 * Woocommerce_Etsy_Integration_Admin ced_etsy_get_product_metakeys.
		 *
		 * @since 1.0.0
		 */
	public function ced_etsy_get_product_metakeys() {

		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$product_id = isset( $_POST['post_id'] ) ? sanitize_text_field( $_POST['post_id'] ) : '';
			include_once CED_ETSY_DIRPATH . 'admin/partials/ced-etsy-metakeys-list.php';
		}
	}

	/**
	 * Woocommerce_Etsy_Integration_Admin ced_etsy_process_metakeys.
	 *
	 * @since 1.0.0
	 */
	public function ced_etsy_process_metakeys() {

		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$metakey   = isset( $_POST['metakey'] ) ? sanitize_text_field( wp_unslash( $_POST['metakey'] ) ) : '';
			$operation = isset( $_POST['operation'] ) ? sanitize_text_field( wp_unslash( $_POST['operation'] ) ) : '';
			if ( ! empty( $metakey ) ) {
				$added_meta_keys = get_option( 'ced_etsy_selected_metakeys', array() );
				if ( 'store' == $operation ) {
					$added_meta_keys[ $metakey ] = $metakey;
				} elseif ( 'remove' == $operation ) {
					unset( $added_meta_keys[ $metakey ] );
				}
				update_option( 'ced_etsy_selected_metakeys', $added_meta_keys );
				echo json_encode( array( 'status' => 200 ) );
				die();
			} else {
				echo json_encode( array( 'status' => 400 ) );
				die();
			}
		}
	}

	/**
	 * Active Marketplace List
	 *
	 * @since    1.0.0
	 */

	public function ced_marketplace_listing_page() {
		$activeMarketplaces = apply_filters( 'ced_add_marketplace_menus_array', array() );
		if ( is_array( $activeMarketplaces ) && ! empty( $activeMarketplaces ) ) {
			require CED_ETSY_DIRPATH . 'admin/template/view/class-ced-view-marketplaces.php';
		}
	}

	public function ced_etsy_add_marketplace_menus_to_array( $menus = array() ) {
		$menus[] = array(
			'name'            => 'Etsy',
			'slug'            => 'woocommerce-etsy-integration',
			'menu_link'       => 'ced_etsy',
			'instance'        => $this,
			'function'        => 'ced_etsy_accounts_page',
			'card_image_link' => CED_ETSY_URL . 'admin/assets/images/etsy.png',
		);
		return $menus;
	}

	/**
	 * Ced Etsy Accounts Page
	 *
	 * @since    1.0.0
	 */
	public function ced_etsy_accounts_page() {
		$fileAccounts = CED_ETSY_DIRPATH . 'admin/template/view/class-ced-view-etsy-accounts.php';
		if ( file_exists( $fileAccounts ) ) {
			echo "<div class='cedcommerce-etsy-wrap'>";
			require_once $fileAccounts;
			echo '</div>';
		}
	}


	/**
	 * Etsy Changing Account status
	 *
	 * @since    1.0.0
	 */
	public function ced_etsy_change_account_status() {
		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$shop_name = isset( $_POST['shop_name'] ) ? sanitize_text_field( wp_unslash( $_POST['shop_name'] ) ) : '';
			$status    = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'active';
			$shops     = get_option( 'ced_etsy_details', array() );
			$shops[ $shop_name ]['details']['ced_shop_account_status'] = $status;
			update_option( 'ced_etsy_details', $shops );
			echo json_encode( array( 'status' => '200' ) );
			die;
		}
	}


	/**
	 * Woocommerce_Etsy_Integration_Admin ced_etsy_add_order_metabox.
	 *
	 * @since 1.0.0
	 */
	public function ced_etsy_add_order_metabox() {
		global $post;
		$product    = wc_get_product( $post->ID );
		$order_from = get_post_meta( $post->ID, '_umb_etsy_marketplace', true );
		if ( 'etsy' == strtolower( $order_from ) ) {
			add_meta_box(
				'ced_etsy_manage_orders_metabox',
				__( 'Manage Marketplace Orders', 'woocommerce-etsy-integration' ) . wc_help_tip( __( 'Please send shipping confirmation.', 'woocommerce-etsy-integration' ) ),
				array( $this, 'ced_etsy_render_orders_metabox' ),
				'shop_order',
				'advanced',
				'high'
			);
		}
	}

	/**
	 * Woocommerce_Etsy_Integration_Admin ced_etsy_submit_shipment.
	 *
	 * @since 1.0.0
	 */
	public function ced_etsy_submit_shipment() {

		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$ced_etsy_tracking_code = isset( $_POST['ced_etsy_tracking_code'] ) ? sanitize_text_field( wp_unslash( $_POST['ced_etsy_tracking_code'] ) ) : '';
			$ced_etsy_carrier_name  = isset( $_POST['ced_etsy_carrier_name'] ) ? sanitize_text_field( wp_unslash( $_POST['ced_etsy_carrier_name'] ) ) : '';
			$order_id               = isset( $_POST['order_id'] ) ? sanitize_text_field( wp_unslash( $_POST['order_id'] ) ) : '';

			$shop_name = get_option( 'ced_etsy_shop_name', '' );
			if ( empty( $shop_name ) ) {
				$shop_name = get_post_meta( $order_id, 'ced_etsy_order_shop_id', true );
			}
			$_ced_etsy_order_id = get_post_meta( $order_id, '_ced_etsy_order_id', true );
			$saved_etsy_details = get_option( 'ced_etsy_details', array() );
			$shopDetails        = $saved_etsy_details[ $shop_name ];
			$shop_id            = $shopDetails['details']['shop_id'];
			$parameters         = array(
				'tracking_code' => $ced_etsy_tracking_code,
				'carrier_name'  => $ced_etsy_carrier_name,
			);

			$action   = 'application/shops/' . etsy_shop_id() . '/receipts/' . $_ced_etsy_order_id . '/tracking';
			$response = etsy_request()->post( $action, $parameters, $shop_name );
			if ( isset( $response['receipt_id'] ) || isset( $response['Shipping_notification_email_has_already_been_sent_for_this_receipt_'] ) ) {
				update_post_meta( $order_id, '_etsy_umb_order_status', 'Shipped' );
				$_order = wc_get_order( $order_id );
				$_order->update_status( 'wc-completed' );
				echo json_encode(
					array(
						'status'  => 200,
						'message' => 'Shipment submitted successfully.',
					)
				);
				wp_die();
			} elseif ( is_array( $response ) ) {
				foreach ( $response as $error => $value ) {
					$message = isset( $error ) ? ucwords( str_replace( '_', ' ', $error ) ) : '';
					echo json_encode(
						array(
							'status'  => 400,
							'message' => $message,
						)
					);
					wp_die();
				}
			} else {
				echo json_encode(
					array(
						'status'  => 400,
						'message' => 'Shipment not submitted.',
					)
				);
				wp_die();
			}
		}
	}


	/**
	 * Woocommerce_Etsy_Integration_Admin ced_etsy_render_orders_metabox.
	 *
	 * @since 1.0.0
	 */
	public function ced_etsy_render_orders_metabox() {
		global $post;
		$order_id = isset( $post->ID ) ? intval( $post->ID ) : '';
		if ( ! is_null( $order_id ) ) {
			$order         = wc_get_order( $order_id );
			$template_path = CED_ETSY_DIRPATH . 'admin/partials/order-template.php';
			if ( file_exists( $template_path ) ) {
				include_once $template_path;
			}
		}
	}

	/**
	 * Woocommerce_Etsy_Integration_Admin ced_etsy_email_restriction.
	 *
	 * @since 1.0.0
	 */
	public function ced_etsy_email_restriction( $enable = '', $order = array() ) {
		if ( ! is_object( $order ) ) {
			return $enable;
		}
		$order_id   = $order->get_id();
		$order_from = get_post_meta( $order_id, '_umb_etsy_marketplace', true );
		if ( 'etsy' == strtolower( $order_from ) ) {
			$enable = false;
		}
		return $enable;
	}

	/**
	 * Marketplace
	 *
	 * @since    1.0.0
	 */
	public function ced_etsy_marketplace_to_be_logged( $marketplaces = array() ) {

		$marketplaces[] = array(
			'name'             => 'Etsy',
			'marketplace_slug' => 'etsy',
		);
		return $marketplaces;
	}

	/**
	 * Etsy Cron Schedules
	 *
	 * @since    1.0.0
	 */
	public function my_etsy_cron_schedules( $schedules ) {
		if ( ! isset( $schedules['ced_etsy_6min'] ) ) {
			$schedules['ced_etsy_6min'] = array(
				'interval' => 6 * 60,
				'display'  => __( 'Once every 6 minutes' ),
			);
		}
		if ( ! isset( $schedules['ced_etsy_10min'] ) ) {
			$schedules['ced_etsy_10min'] = array(
				'interval' => 10 * 60,
				'display'  => __( 'Once every 10 minutes' ),
			);
		}
		if ( ! isset( $schedules['ced_etsy_15min'] ) ) {
			$schedules['ced_etsy_15min'] = array(
				'interval' => 15 * 60,
				'display'  => __( 'Once every 15 minutes' ),
			);
		}
		if ( ! isset( $schedules['ced_etsy_30min'] ) ) {
			$schedules['ced_etsy_30min'] = array(
				'interval' => 30 * 60,
				'display'  => __( 'Once every 30 minutes' ),
			);
		}
		if ( ! isset( $schedules['ced_etsy_20min'] ) ) {
			$schedules['ced_etsy_20min'] = array(
				'interval' => 20 * 60,
				'display'  => __( 'Once every 20 minutes' ),
			);
		}
		return $schedules;
	}


	/**
	 * Etsy Fetch Next Level Category
	 *
	 * @since    1.0.0
	 */
	public function ced_etsy_fetch_next_level_category() {
		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			global $wpdb;
			$store_category_id      = isset( $_POST['store_id'] ) ? sanitize_text_field( wp_unslash( $_POST['store_id'] ) ) : '';
			$etsy_category_name     = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			$etsy_category_id       = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
			$level                  = isset( $_POST['level'] ) ? sanitize_text_field( wp_unslash( $_POST['level'] ) ) : '';
			$next_level             = intval( $level ) + 1;
			$etsyCategoryList       = file_get_contents( CED_ETSY_DIRPATH . 'admin/lib/json/categoryLevel-' . $next_level . '.json' );
			$etsyCategoryList       = json_decode( $etsyCategoryList, true );
			$select_html            = '';
			$nextLevelCategoryArray = array();
			if ( ! empty( $etsyCategoryList ) ) {
				foreach ( $etsyCategoryList as $key => $value ) {
					if ( isset( $value['parent_id'] ) && $value['parent_id'] == $etsy_category_id ) {
						$nextLevelCategoryArray[] = $value;
					}
				}
			}
			if ( is_array( $nextLevelCategoryArray ) && ! empty( $nextLevelCategoryArray ) ) {

				$select_html .= '<td data-catlevel="' . $next_level . '"><select class="ced_etsy_level' . $next_level . '_category ced_etsy_select_category select_boxes_cat_map" name="ced_etsy_level' . $next_level . '_category[]" data-level=' . $next_level . ' data-storeCategoryID="' . $store_category_id . '">';
				$select_html .= '<option value=""> --' . __( 'Select', 'woocommerce-etsy-integration' ) . '-- </option>';
				foreach ( $nextLevelCategoryArray as $key => $value ) {
					if ( ! empty( $value['name'] ) ) {
						$select_html .= '<option value="' . $value['id'] . '">' . $value['name'] . '</option>';
					}
				}
				$select_html .= '</select></td>';
				echo json_encode( $select_html );
				die;
			}
		}
	}

	/*
	*
	*Function for Fetching child categories for custom profile
	*
	*
	*/

	public function ced_etsy_fetch_next_level_category_add_profile() {
		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			global $wpdb;
			$tableName              = $wpdb->prefix . 'ced_etsy_accounts';
			$etsy_store_id          = isset( $_POST['etsy_store_id'] ) ? sanitize_text_field( wp_unslash( $_POST['etsy_store_id'] ) ) : '';
			$etsy_category_name     = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			$etsy_category_id       = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
			$level                  = isset( $_POST['level'] ) ? sanitize_text_field( wp_unslash( $_POST['level'] ) ) : '';
			$next_level             = intval( $level ) + 1;
			$etsyCategoryList       = @file_get_contents( CED_ETSY_DIRPATH . 'admin/lib/json/categoryLevel-' . $next_level . '.json' );
			$etsyCategoryList       = json_decode( $etsyCategoryList, true );
			$select_html            = '';
			$nextLevelCategoryArray = array();
			if ( ! empty( $etsyCategoryList ) ) {
				foreach ( $etsyCategoryList as $key => $value ) {
					if ( isset( $value['parent_id'] ) && $value['parent_id'] == $etsy_category_id ) {
						$nextLevelCategoryArray[] = $value;
					}
				}
			}
			if ( is_array( $nextLevelCategoryArray ) && ! empty( $nextLevelCategoryArray ) ) {

				$select_html .= '<td data-catlevel="' . $next_level . '"><select class="ced_etsy_level' . $next_level . '_category ced_etsy_select_category_on_add_profile  select_boxes_cat_map" name="ced_etsy_level' . $next_level . '_category[]" data-level=' . $next_level . ' data-etsyStoreId="' . $etsy_store_id . '">';
				$select_html .= '<option value=""> --' . __( 'Select', 'woocommerce-etsy-integration' ) . '-- </option>';
				foreach ( $nextLevelCategoryArray as $key => $value ) {
					if ( ! empty( $value['name'] ) ) {
						$select_html .= '<option value="' . $value['id'] . ',' . $value['name'] . '">' . $value['name'] . '</option>';
					}
				}
				$select_html .= '</select></td>';
				echo json_encode( $select_html );
				die;
			}
		}
	}


	/**
	 * Etsy Mapping Categories to WooStore
	 *
	 * @since    1.0.0
	 */
	public function ced_etsy_map_categories_to_store() {

		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$sanitized_array             = filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING );
			$etsy_category_array         = isset( $sanitized_array['etsy_category_array'] ) ? $sanitized_array['etsy_category_array'] : '';
			$store_category_array        = isset( $sanitized_array['store_category_array'] ) ? $sanitized_array['store_category_array'] : '';
			$etsy_category_name          = isset( $sanitized_array['etsy_category_name'] ) ? $sanitized_array['etsy_category_name'] : '';
			$etsy_store_id               = isset( $_POST['storeName'] ) ? sanitize_text_field( wp_unslash( $_POST['storeName'] ) ) : '';
			$etsy_saved_category         = get_option( 'ced_etsy_saved_category', array() );
			$alreadyMappedCategories     = array();
			$alreadyMappedCategoriesName = array();
			$etsyMappedCategories        = array_combine( $store_category_array, $etsy_category_array );
			$etsyMappedCategories        = array_filter( $etsyMappedCategories );
			$alreadyMappedCategories     = get_option( 'ced_woo_etsy_mapped_categories_' . $etsy_store_id, array() );
			if ( is_array( $etsyMappedCategories ) && ! empty( $etsyMappedCategories ) ) {
				foreach ( $etsyMappedCategories as $key => $value ) {
					$alreadyMappedCategories[ $etsy_store_id ][ $key ] = $value;
				}
			}
			update_option( 'ced_woo_etsy_mapped_categories_' . $etsy_store_id, $alreadyMappedCategories );
			$etsyMappedCategoriesName    = array_combine( $etsy_category_array, $etsy_category_name );
			$etsyMappedCategoriesName    = array_filter( $etsyMappedCategoriesName );
			$alreadyMappedCategoriesName = get_option( 'ced_woo_etsy_mapped_categories_name_' . $etsy_store_id, array() );
			if ( is_array( $etsyMappedCategoriesName ) && ! empty( $etsyMappedCategoriesName ) ) {
				foreach ( $etsyMappedCategoriesName as $key => $value ) {
					$alreadyMappedCategoriesName[ $etsy_store_id ][ $key ] = $value;
				}
			}
			update_option( 'ced_woo_etsy_mapped_categories_name_' . $etsy_store_id, $alreadyMappedCategoriesName );
			$this->ced_etsy_mager->ced_etsy_createAutoProfiles( $etsyMappedCategories, $etsyMappedCategoriesName, $etsy_store_id );
			wp_die();
		}
	}


	/**
	 * Etsy Preview Product
	 *
	 * @since    1.0.0
	 */
	public function ced_etsy_preview_product_detail() {
		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {

			require_once CED_ETSY_DIRPATH . 'admin/etsy/lib/etsyProducts.php';
			$product_id = isset( $_POST['prodId'] ) ? sanitize_text_field( wp_unslash( $_POST['prodId'] ) ) : '';
			$shopid     = isset( $_POST['shopid'] ) ? sanitize_text_field( wp_unslash( $_POST['shopid'] ) ) : '';
			if ( isset( $product_id ) && ! empty( $product_id ) ) {
				$etsyProductsInstance = Class_Ced_Product_Upload::get_instance();
				$previewData          = $etsyProductsInstance->getFormattedData( $product_id, $shopid, true );
				$previewData          = $previewData['data'];
				$image_gallery_id     = array();
				$image_gallery_view   = array();
				$product              = wc_get_product( $product_id );
				$product_type         = $product->get_type();
				$image                = $product->get_data();
				$image_id             = $image['image_id'];
				$image_gallery_id     = $image['gallery_image_ids'];
				$image_view           = wp_get_attachment_image_url( $image_id );
				?>
				<div class="ced_etsy_preview_product_popup_content">
					<div class="ced_etsy_preview_product_popup_header">
						<h5><?php esc_html_e( 'Etsy Product Details', 'woocommerce-etsy-integration' ); ?></h5>
						<span class="ced_etsy_preview_product_popup_close">X</span>
					</div>
					<div class="ced_etsy_preview_product_popup_body">
						<div class="preview_content preview-image-col">
							<div id="preview-image">
								<?php
								echo '<image height="100%" width="100%" src="' . esc_html( $image_view ) . '">';
								?>
							</div>
							<div class="ced_etsy_thumbnail">
								<ul>
									<?php
									if ( isset( $image_gallery_id ) && ! empty( $image_gallery_id ) ) {
										foreach ( $image_gallery_id as $value ) {
											$image_gallery_view = wp_get_attachment_image_url( $value );
											echo '<li>
											<img src="' . esc_html( $image_gallery_view ) . '">
											</li>';
										}
									}
									?>
								</ul>
							</div>	
						</div>
						<div class="preview_content preview_content-col">
							<div id="preview_Right_details_content">
								<h4>
									<?php
									echo esc_html( $shopid );
									?>
								</h4>
								<h3 class="">
									<?php
									$title  = isset( $previewData['title'] ) ? $previewData['title'] : '';
									$title .= '<br>';
									echo esc_html( $title );
									?>
								</h3>
								<p>
									<?php
									$sku = get_post_meta( $product_id, '_sku', true );
									echo esc_html( $sku );
									?>
								</p>
								<div id="Price_detail_etsy">
									<?php
									$price = '₹' . isset( $previewData['price'] ) ? $previewData['price'] : '';
									echo esc_html( $previewData['price'] );
									?>
								</div>
								<div class="ced_etsy_preview_detail">
									<ul>
										<li>
											<?php
											if ( 'variable' == $product_type ) {
												$variations = $etsyProductsInstance->getVaritionDataForPreview( $product_id );
												if ( isset( $variations['tier_variation'] ) ) {
													foreach ( $variations['tier_variation'] as $key => $value ) {
														echo '<div class="ced_etsy_variations_wrapper">
														<div>' . esc_html( $value['name'] );
														foreach ( $value['options'] as $key1 => $value1 ) {
															echo '<span class="ced_etsy_product_variation">' . esc_html( $value1 ) . '</span>';
														}
														echo '</span></div>';
														echo '</div>';
													}
												}
											}
											?>
										</li>
										<li>
											<div class="ced_etsy_preview_detail_name">Quantity</div>
											<div class="ced_etsy_preview_detail_desc">
												<div class="ced_etsy_qnty_wrapper">
													<span class="ed_etsy_qnty_point">-</span>
													<span class="ed_etsy_qnty_number">1</span>
													<span class="ed_etsy_qnty_point">+</span>
												</div>
												<div class="ced_etsy_product_piece"><?php echo isset( $previewData['quantity'] ) ? esc_html( $previewData['quantity'] ) : 0; ?> piece available</div>
											</div>
										</li>
									</ul>
									<input type="button" class="ced_etsy_preview_btn ced_etsy_add_to_cart"><?php esc_html_e( 'Add to basket', 'woocommerce-etsy-integration' ); ?>
									
								</div>
							</div>

						</div>
					</div>
					<div class="ced_etsy_product_wrapper">
						<h3 class="ced_etsy_product_title"><?php esc_html_e( 'Overview', 'woocommerce-etsy-integration' ); ?></h3>
						<ul class="ced_etsy_product_listing">
							<li>
								<div class="ced_etsy_product_value"><?php esc_html_e( 'Material', 'woocommerce-etsy-integration' ); ?></div>
								<div class="ced_etsy_product_desc"><?php echo isset( $previewData['materials'][0] ) ? esc_html( $previewData['materials'][0] ) : ''; ?></div>
							</li>
							
						</ul>
						<h6 class="ced_etsy_product_title"><?php esc_html_e( 'Product Description', 'woocommerce-etsy-integration' ); ?></h6>
						<p class="ced_etsy_product_para"><?php echo esc_html( $previewData['description'] ); ?></p>
					</div>
				</div>
				<?php
			}
			wp_die();

		}
	}

	/**
	 * Etsy Inventory Scheduler
	 *
	 * @since    1.0.0
	 */
	public function ced_etsy_inventory_schedule_manager() {

		$hook    = current_action();
		$shop_id = str_replace( 'ced_etsy_inventory_scheduler_job_', '', $hook );
		$shop_id = trim( $shop_id );

		if ( empty( $shop_id ) ) {
			$shop_id = get_option( 'ced_etsy_shop_name', '' );
		}

		$products_to_sync = get_option( 'ced_etsy_chunk_products', array() );
		if ( empty( $products_to_sync ) ) {
			$store_products   = get_posts(
				array(
					'numberposts' => -1,
					'post_type'   => 'product',
					'meta_query'  => array(
						array(
							'key'     => '_ced_etsy_listing_id_' . $shop_id,
							'compare' => 'EXISTS',
						),
					),
				)
			);
			$store_products   = wp_list_pluck( $store_products, 'ID' );
			$products_to_sync = array_chunk( $store_products, 10 );

		}
		if ( is_array( $products_to_sync[0] ) && ! empty( $products_to_sync[0] ) ) {
			$get_products = $this->ced_etsy_product->prepareDataForUpdatingInventory( $products_to_sync[0], $shop_id, true );
			unset( $products_to_sync[0] );
			$products_to_sync = array_values( $products_to_sync );
			update_option( 'ced_etsy_chunk_products', $products_to_sync );
		}
	}


	public function ced_etsy_auto_upload_products() {
		$shop_name     = str_replace( 'ced_etsy_auto_upload_products_', '', current_action() );
		$shop_name     = trim( $shop_name );
		$product_chunk = get_option( 'ced_etsy_product_upload_chunk_' . $shop_name, array() );
		if ( empty( $product_chunk ) ) {
			$woo_categories = get_option( 'ced_etsy_auto_upload_categories_' . $shop_name, array() );
			$woo_categories = array_unique( $woo_categories );

			if ( ! empty( $woo_categories ) ) {
				$products = array();
				foreach ( $woo_categories as $term_id ) {
					$store_products = get_posts(
						array(
							'numberposts' => -1,
							'post_type'   => 'product',
							'fields'      => 'ids',
							'tax_query'   => array(
								array(
									'taxonomy' => 'product_cat',
									'field'    => 'term_id',
									'terms'    => $term_id,
									'operator' => 'IN',
								),
							),
							'meta_query'  => array(
								array(
									'key'     => '_ced_etsy_listing_id_' . $shop_name,
									'compare' => 'NOT EXISTS',
								),

							),
						)
					);
					$products = array_unique( array_merge( $products, $store_products ) );
				}
				$products      = array_reverse( $products );
				$product_chunk = array_chunk( $products, 20 );
			}
		}
		if ( isset( $product_chunk[0] ) && is_array( $product_chunk[0] ) && ! empty( $product_chunk[0] ) ) {
			$response = $ced_etsy_product->prepareDataForUploading( $product_chunk[0], $shop_name );
			unset( $product_chunk[0] );
			$product_chunk = array_values( $product_chunk );
			update_option( 'ced_etsy_product_upload_chunk_' . $shop_name, $product_chunk );
		}
	}


	/**
	 * Etsy Sync existing products scheduler
	 *
	 * @since    1.0.5
	 */
	public function ced_etsy_sync_existing_products() {

		$hook      = current_action();
		$shop_name = str_replace( 'ced_etsy_sync_existing_products_job_', '', $hook );
		$shop_name = trim( $shop_name );

		if ( empty( $shop_name ) ) {
			$shop_name = get_option( 'ced_etsy_shop_name', '' );
		}
		$saved_etsy_details = get_option( 'ced_etsy_details', true );
		$shopDetails        = $saved_etsy_details[ $shop_name ];
		$shop_id            = $shopDetails['details']['shop_id'];
		$offset             = get_option( 'ced_etsy_get_offset_' . $shop_name, '' );
		if ( empty( $offset ) ) {
			$offset = 0;
		}
		$query_args = array(
			'offset' => $offset,
			'limit'  => 25,
			'state'  => 'active',
		);
		$action     = "application/shops/{$shop_id}/listings";
		$response   = etsy_request()->get( $action, $shop_name, $query_args );
		if ( isset( $response['results'][0] ) ) {
			foreach ( $response['results'] as $key => $value ) {
				$skus = isset( $value['skus'] ) ? $value['skus'] : '';
				if ( ! empty( $skus ) ) {
					foreach ( $skus as $sku ) {
						$product_id = wc_get_product_id_by_sku( $sku );
						if ( $product_id ) {
							$_product = wc_get_product( $product_id );
							if ( 'variation' == $_product->get_type() ) {
								$product_id = $_product->get_parent_id();
							}
							update_post_meta( $product_id, '_ced_etsy_state_' . $shop_name, $value['state'] );
							update_post_meta( $product_id, '_ced_etsy_url_' . $shop_name, $value['url'] );
							update_post_meta( $product_id, '_ced_etsy_listing_id_' . $shop_name, $value['listing_id'] );
							break;
						}
					}
				}
			}
			if ( isset( $response['pagination']['next_offset'] ) && ! empty( $response['pagination']['next_offset'] ) ) {
				$next_offset = $response['pagination']['next_offset'];
			} else {
				$next_offset = 0;
			}
			update_option( 'ced_etsy_get_offset_' . $shop_name, $next_offset );
		} else {
			update_option( 'ced_etsy_get_offset_' . $shop_name, 0 );
		}
	}

	/**
	 * ****************************************************
	 *  AUTO IMPORT PRODUCT BY SCHEDULER GLOBAL SETTINGS
	 * ****************************************************
	 *
	 * @since    2.0.0
	 */

	public function ced_etsy_auto_import_schedule_manager() {
		$hook      = current_action();
		$shop_name = str_replace( 'ced_etsy_auto_import_schedule_job_', '', $hook );
		if ( empty( $shop_name ) ) {
			$shop_name = get_option( 'ced_etsy_shop_name', '' );
		}
		$shop_name_t        = 'ced_etsy_import_by_status_' . $shop_name;
		$saved_status       = get_option( $shop_name_t, '' );
		$saved_etsy_details = get_option( 'ced_etsy_details', array() );
		$shopDetails        = $saved_etsy_details[ $shop_name ];
		$shop_id            = $shopDetails['details']['shop_id'];
		$client             = ced_etsy_getOauthClientObject( $shop_name );
		$offset             = get_option( 'ced_etsy_get_import_offset', '' );

		if ( ! empty( $saved_status ) ) {
			$status = $saved_status;
		} else {
			$status = 'active';
		}

		if ( empty( $offset ) ) {
			$offset = 0;
		}
		$params = array(
			'offset' => $offset,
			'limit'  => 5,
		);

		$renderDataOnGlobalSettings = get_option( 'ced_etsy_global_settings', array() );
		$language                   = isset( $renderDataOnGlobalSettings[ $shop_name ]['product_data']['_etsy_language']['default'] ) ? $renderDataOnGlobalSettings[ $shop_name ]['product_data']['_etsy_language']['default'] : 'en';

		$success  = $client->CallAPI( 'https://openapi.etsy.com/v2/shops/' . $shop_id . '/listings/' . $status . '?language=' . $language, 'GET', $params, array( 'FailOnAccessError' => true ), $listings_details );
		$response = json_decode( json_encode( $listings_details ), true );

		if ( isset( $response['results'][0] ) ) {
			foreach ( $response['results'] as $key => $value ) {
				$this->importProduct->get_listing_details_auto_upload( $value, $shop_name, $shop_id );
			}
			if ( isset( $response['pagination']['next_offset'] ) && ! empty( $response['pagination']['next_offset'] ) ) {
				$next_offset = $response['pagination']['next_offset'];
			} else {
				$next_offset = 0;
			}
			update_option( 'ced_etsy_get_import_offset', $next_offset );
		} else {
			update_option( 'ced_etsy_get_import_offset', 0 );
		}
	}

	/**
	 * Etsy Order Scheduler
	 *
	 * @since    1.0.0
	 */
	public function ced_etsy_order_schedule_manager() {
		$hook    = current_action();
		$shop_id = str_replace( 'ced_etsy_order_scheduler_job_', '', $hook );
		$shop_id = trim( $shop_id );
		if ( empty( $shop_id ) ) {
			$shop_id = get_option( 'ced_etsy_shop_name', '' );
		}
		$getOrders = $this->ced_etsy_order->getOrders( $shop_id );
		if ( ! empty( $getOrders ) ) {
			$createOrder = $etsyOrdersInstance->createLocalOrder( $getOrders, $shop_id );
		}
	}

	/**
	 * Etsy Fetch Orders
	 *
	 * @since    1.0.0
	 */
	public function ced_etsy_get_orders() {
		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$shop_id        = isset( $_POST['shopid'] ) ? sanitize_text_field( wp_unslash( $_POST['shopid'] ) ) : '';
			$isShopInActive = ced_etsy_inactive_shops( $shop_id );
			if ( $isShopInActive ) {
				echo json_encode(
					array(
						'status'  => 400,
						'message' => __(
							'Shop is Not Active',
							'woocommerce-etsy-integration'
						),
					)
				);
				die;
			}
			$getOrders = $this->ced_etsy_order->getOrders( $shop_id );
			if ( ! empty( $getOrders ) ) {
				$createOrder = $etsyOrdersInstance->createLocalOrder( $getOrders, $shop_id );
			}
		}
	}

	/**
	 * Etsy Profiles List on popup
	 *
	 * @since    1.0.0
	 */
	public function ced_etsy_profiles_on_pop_up() {
		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$store_id = isset( $_POST['shopid'] ) ? sanitize_text_field( wp_unslash( $_POST['shopid'] ) ) : '';
			$prodId   = isset( $_POST['prodId'] ) ? sanitize_text_field( wp_unslash( $_POST['prodId'] ) ) : '';
			global $wpdb;
			$profiles = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_etsy_profiles WHERE `shop_name` = %s", $store_id ), 'ARRAY_A' );
			?>
			<div class="ced_etsy_profile_popup_content">
				<div id="profile_pop_up_head_main">
					<h2><?php esc_html_e( 'CHOOSE PROFILE FOR THIS PRODUCT', 'woocommerce-etsy-integration' ); ?></h2>
					<div class="ced_etsy_profile_popup_close">X</div>
				</div>
				<div id="profile_pop_up_head"><h3><?php esc_html_e( 'Available Profiles', 'woocommerce-etsy-integration' ); ?></h3></div>
				<div class="ced_etsy_profile_dropdown">
					<select name="ced_etsy_profile_selected_on_popup" class="ced_etsy_profile_selected_on_popup">
						<option class="profile_options" value=""><?php esc_html_e( '---Select Profile---', 'woocommerce-etsy-integration' ); ?></option>
						<?php
						foreach ( $profiles as $key => $value ) {
							echo '<option  class="profile_options" value="' . esc_html( $value['id'] ) . '">' . esc_html( $value['profile_name'] ) . '</option>';
						}
						?>
					</select>
				</div>	
				<div id="save_profile_through_popup_container">
					<button data-prodId="<?php echo esc_html( $prodId ); ?>" class="ced_etsy_custom_button" id="save_etsy_profile_through_popup"  data-shopid="<?php echo esc_html( $store_id ); ?>"><?php esc_html_e( 'Assign Profile', 'woocommerce-etsy-integration' ); ?></button>
				</div>
			</div>
			<?php
			wp_die();
		}
	}

	/**
	 * Etsy Refreshing Categories
	 *
	 * @since    1.0.0
	 */
	public function ced_etsy_category_refresh() {
		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$shop_name      = isset( $_POST['shop_name'] ) ? sanitize_text_field( wp_unslash( $_POST['shop_name'] ) ) : '';
			$isShopInActive = ced_etsy_inactive_shops( $shop_name );
			if ( $isShopInActive ) {
				echo json_encode(
					array(
						'status'  => 400,
						'message' => __(
							'Shop is Not Active',
							'woocommerce-etsy-integration'
						),
					)
				);
				die;
			}
			$file = CED_ETSY_DIRPATH . 'admin/lib/etsyCategory.php';
			if ( ! file_exists( $file ) ) {
				return;
			}
			require_once $file;
			$etsyCategoryInstance = Class_Ced_Etsy_Category::get_instance();
			$fetchedCategories    = $etsyCategoryInstance->getEtsyCategories( $shop_name );
			if ( isset( $fetchedCategories['results'] ) && ! empty( $fetchedCategories['results'] ) ) {
				$categories = $this->Ced_Etsy_Manager->StoreCategories( $fetchedCategories );
				echo json_encode( array( 'status' => 200 ) );
				wp_die();
			} else {
				echo json_encode( array( 'status' => 400 ) );
				wp_die();
			}
		}
	}

	/**
	 * Etsy Save profile On Product level
	 *
	 * @since    1.0.0
	 */
	public function save_etsy_profile_through_popup() {
		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$shopid     = isset( $_POST['shopid'] ) ? sanitize_text_field( wp_unslash( $_POST['shopid'] ) ) : '';
			$prodId     = isset( $_POST['prodId'] ) ? sanitize_text_field( wp_unslash( $_POST['prodId'] ) ) : '';
			$profile_id = isset( $_POST['profile_id'] ) ? sanitize_text_field( wp_unslash( $_POST['profile_id'] ) ) : '';
			if ( '' == $profile_id ) {
				echo 'null';
				wp_die();
			}

			update_post_meta( $prodId, 'ced_etsy_profile_assigned' . $shopid, $profile_id );
		}
	}

	/**
	 * Etsy Bulk Operations
	 *
	 * @since    1.0.0
	 */
	public function ced_etsy_process_bulk_action() {
		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$shop_name        = isset( $_POST['shopname'] ) ? sanitize_text_field( wp_unslash( $_POST['shopname'] ) ) : '';
			$operation        = isset( $_POST['operation_to_be_performed'] ) ? sanitize_text_field( wp_unslash( $_POST['operation_to_be_performed'] ) ) : '';
			$product_id       = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
			$isShopInActive   = ced_etsy_inactive_shops( $shop_name );
			if ( $isShopInActive ) {
				echo json_encode(
					array(
						'status'  => 400,
						'message' => __(
							'Shop is Not Active',
							'woocommerce-etsy-integration'
						),
					)
				);
				die;
			}
			if ( 'upload_product' == $operation ) {
				$prodIDs          = $product_id;
				$already_uploaded = get_post_meta( $prodIDs, '_ced_etsy_listing_id_' . $shop_name, true );
				if ( $already_uploaded ) {
					echo json_encode(
						array(
							'status'  => 400,
							'message' => __(
								'Product ' . $prodIDs . ' Already Uploaded',
								'woocommerce-etsy-integration'
							),
						)
					);
					die;
				} else {
					$get_product_detail = $this->ced_etsy_product->prepareDataForUploading( $prodIDs, $shop_name );
					if ( isset( $get_product_detail['results'][0]['listing_id'] ) ) {
						echo json_encode(
							array(
								'status'  => 200,
								'message' => $get_product_detail['results'][0]['title'] . ' Uploaded Successfully',
								'prodid'  => $prodIDs,
							)
						);
						die;
					} else {
						echo json_encode(
							array(
								'status'  => 400,
								'message' => isset( $get_product_detail['msg'] ) ? $get_product_detail['msg'] : json_encode( $get_product_detail ),
								'prodid'  => $prodIDs,
							)
						);
						die;
					}
				}
			} elseif ( 'update_product' == $operation ) {
				$prodIDs          = $product_id;
				$already_uploaded = get_post_meta( $prodIDs, '_ced_etsy_listing_id_' . $shop_name, true );
				if ( $already_uploaded ) {
					$get_product_detail = $this->ced_etsy_product->prepareDataForUpdating( $prodIDs, $shop_name );
					if ( isset( $get_product_detail['results'][0]['listing_id'] ) ) {
						echo json_encode(
							array(
								'status'  => 200,
								'message' => $get_product_detail['results'][0]['title'] . ' Updated Successfully',
							)
						);
						die;
					} else {
						echo json_encode(
							array(
								'status'  => 400,
								'message' => isset( $get_product_detail['msg'] ) ? $get_product_detail['msg'] : json_encode( $get_product_detail ),
							)
						);
						die;
					}
				} else {
					echo json_encode(
						array(
							'status'  => 400,
							'message' => __(
								'Product ' . $prodIDs . ' Not Found On Etsy',
								'woocommerce-etsy-integration'
							),
						)
					);
					die;
				}
			} elseif ( 'remove_product' == $operation ) {
				$prodIDs          = $product_id;
				$already_uploaded = get_post_meta( $prodIDs, '_ced_etsy_listing_id_' . $shop_name, true );
				if ( $already_uploaded ) {
					$get_product_detail = $this->ced_etsy_product->prepareDataForDelete( $prodIDs, $shop_name );
					if ( isset( $get_product_detail['results'] ) ) {
						echo json_encode(
							array(
								'status'  => 200,
								'message' => 'Product ' . $prodIDs . ' Deleted Successfully',
								'prodid'  => $prodIDs,
							)
						);
						die;
					} else {
						echo json_encode(
							array(
								'status'  => 400,
								'message' => isset( $get_product_detail['msg'] ) ? $get_product_detail['msg'] : json_encode( $get_product_detail ),
							)
						);
						die;
					}
				} else {
					echo json_encode(
						array(
							'status'  => 400,
							'message' => __(
								'Product ' . $prodIDs . ' Not Found On Etsy',
								'woocommerce-etsy-integration'
							),
						)
					);
					die;
				}
			} elseif ( 'update_inventory' == $operation ) {
				$prodIDs          = $product_id;
				$already_uploaded = get_post_meta( $prodIDs, '_ced_etsy_listing_id_' . $shop_name, true );
				if ( $already_uploaded ) {
					$get_product_detail = $this->ced_etsy_product->prepareDataForUpdatingInventory( $prodIDs, $shop_name );
					if ( isset( $get_product_detail['results'] ) ) {
						echo json_encode(
							array(
								'status'  => 200,
								'message' => __(
									'Inventory Updated Successfully',
									'woocommerce-etsy-integration'
								),
							)
						);
						die;
					} else {
						echo json_encode(
							array(
								'status'  => 400,
								'message' => isset( $get_product_detail['msg'] ) ? $get_product_detail['msg'] : json_encode( $get_product_detail ),
							)
						);
						die;
					}
				} else {
					echo json_encode(
						array(
							'status'  => 400,
							'message' => __(
								'Product ' . $prodIDs . ' Not Found On Etsy',
								'woocommerce-etsy-integration'
							),
						)
					);
					die;
				}
			} elseif ( 'deactivate_product' == $operation ) {
				$prodIDs          = $product_id;
				$already_uploaded = get_post_meta( $prodIDs, '_ced_etsy_listing_id_' . $shop_name, true );
				if ( $already_uploaded ) {
					$get_product_detail = $this->ced_etsy_product->deactivate_products( $prodIDs, $shop_name );
					if ( isset( $get_product_detail ) ) {
						echo json_encode(
							array(
								'status'  => 200,
								'message' => __(
									'Product Deactivated Successfully',
									'woocommerce-etsy-integration'
								),
							)
						);
						die;
					} else {
						echo json_encode(
							array(
								'status'  => 400,
								'message' => isset( $get_product_detail['msg'] ) ? $get_product_detail['msg'] : json_encode( $get_product_detail ),
							)
						);
						die;
					}
				} else {
					echo json_encode(
						array(
							'status'  => 400,
							'message' => __(
								'Product ' . $prodIDs . ' Not Found On Etsy',
								'woocommerce-etsy-integration'
							),
						)
					);
					die;
				}
			} elseif ( 'update_image' == $operation ) {
				$prodIDs          = $product_id;
				$already_uploaded = get_post_meta( $prodIDs, '_ced_etsy_listing_id_' . $shop_name, true );
				if ( $already_uploaded ) {
					$get_product_detail = $this->ced_etsy_product->ced_update_images_on_etsy( $prodIDs, $shop_name );
					if ( isset( $get_product_detail ) ) {
						echo json_encode(
							array(
								'status'  => 200,
								'message' => __(
									'Product Image Updated Successfully',
									'woocommerce-etsy-integration'
								),
							)
						);
						die;
					} else {
						echo json_encode(
							array(
								'status'  => 400,
								'message' => isset( $get_product_detail['msg'] ) ? $get_product_detail['msg'] : json_encode( $get_product_detail ),
							)
						);
						die;
					}
				} else {
					echo json_encode(
						array(
							'status'  => 400,
							'message' => __(
								'Product ' . $prodIDs . ' Not Found On Etsy',
								'woocommerce-etsy-integration'
							),
						)
					);
					die;
				}
			}
		}
	}

	/**
	 * Etsy Import Products Bulk Operations.
	 *
	 * @since    1.1.2
	 */
	public function ced_etsy_import_products_bulk_action() {

		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {

			$import_product_file = CED_ETSY_DIRPATH . 'admin/etsy/lib/etsyImportProducts.php';
			if ( file_exists( $import_product_file ) ) {
				require_once $import_product_file;
			}
			$sanitized_array         = filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING );
			$instance_import_product = Class_Ced_Etsy_Import_Product::get_instance();
			$operation               = isset( $sanitized_array['operation_to_be_performed'] ) ? $sanitized_array['operation_to_be_performed'] : '';
			$listing_ids             = isset( $sanitized_array['listing_id'] ) ? $sanitized_array['listing_id'] : '';
			$shop_name               = isset( $sanitized_array['shop_name'] ) ? $sanitized_array['shop_name'] : '';

			foreach ( $listing_ids as $key => $listing_id ) {
				$if_product_exists = etsy_get_product_id_by_shopname_and_listing_id( $shop_name, $listing_id );
				if ( ! empty( $if_product_exists ) ) {
					echo json_encode(
						array(
							'status'  => 200,
							'message' => __(
								'Product exists in store !'
							),
						)
					);
				} else {
					$response = $instance_import_product->ced_etsy_import_products( $listing_id, $shop_name );
					echo json_encode(
						array(
							'status'  => 200,
							'message' => __(
								'Product Imported Successfully !'
							),
						)
					);
				}
				break;
			}
			wp_die();
		}
	}


	/**
	 * ******************************************************************
	 * Function to Delete for mapped profiles in the profile-view page
	 * ******************************************************************
	 *
	 *  @since version 1.0.8.
	 */
	public function ced_esty_delete_mapped_profiles() {

		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			global $wpdb;
			$profile_id = isset( $_POST['profile_id'] ) ? sanitize_text_field( $_POST['profile_id'] ) : '';
			$shop_name  = isset( $_POST['shop_name'] ) ? sanitize_text_field( $_POST['shop_name'] ) : '';
			$tableName  = $wpdb->prefix . 'ced_etsy_profiles';
			$result     = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM  {$wpdb->prefix}ced_etsy_profiles WHERE `shop_name`= %d ", $shop_name ), 'ARRAY_A' );
			foreach ( $result as $key => $value ) {
				if ( $value['id'] === $profile_id ) {
					$wpdb->query(
						$wpdb->prepare(
							" DELETE FROM {$wpdb->prefix}ced_etsy_profiles WHERE 
							`id` = %s AND shop_name = %s",
							$value['id'],
							$shop_name
						)
					);
					echo json_encode(
						array(
							'status'  => 200,
							'message' => __(
								'Profile Deleted Successfully !',
								'woocommerce-etsy-integration'
							),
						)
					);
				}
			}
			die;
		}
	}

	/**
	 * *****************************************
	 * UPDATE INVENTORY FROM ETSY SHOP TO WOO
	 * *****************************************
	 *
	 * @since version 1.0.8.
	 */
	public function ced_update_inventory_etsy_to_woocommerce() {

		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {

			$listing_id                 = isset( $_POST['listing_id'] ) ? sanitize_text_field( $_POST['listing_id'] ) : '';
			$shop_name                  = isset( $_POST['shop_name'] ) ? sanitize_text_field( $_POST['shop_name'] ) : '';
			$renderDataOnGlobalSettings = get_option( 'ced_etsy_global_settings', array() );
			$language                   = isset( $renderDataOnGlobalSettings[ $shop_name ]['etsy_language'] ) ? $renderDataOnGlobalSettings[ $shop_name ]['etsy_language'] : '';
			$saved_etsy_details         = get_option( 'ced_etsy_details', array() );
			$shopDetails                = $saved_etsy_details[ $shop_name ];
			$shop_id                    = $shopDetails['details']['shop_id'];
			$client                     = ced_etsy_getOauthClientObject( $shop_name );
			$offset                     = get_option( 'ced_etsy_get_import_offset', 0 );

			if ( empty( $offset ) ) {
				$offset = 0;
			}
			$params            = array(
				'offset'   => $offset,
				'limit'    => 20,
				'language' => $language,
			);
			$success           = $client->CallAPI( 'https://openapi.etsy.com/v2/listings/' . $listing_id, 'GET', $params, array( 'FailOnAccessError' => true ), $listings_details );
			$listings_details  = json_decode( json_encode( $listings_details ), true );
			$product           = $listings_details['results'][0];
			$if_product_exists = etsy_get_product_id_by_shopname_and_listing_id( $shop_name, $listing_id );
			if ( ! empty( $if_product_exists ) ) {
				/**
				 * ******************************************************
				 *   Updating Changes From Etsy To Woocommerce products.
				 * ******************************************************
				 */
				// Get product id
				$product_id = $if_product_exists;

				$post      = array(
					'ID'           => esc_sql( $product_id ),
					'post_content' => wp_kses_post( $product['description'] ),
					'post_title'   => wp_strip_all_tags( $product['title'] ),
				);
				$parent_id = wp_update_post( $post, true );
				 // Update diamention for the product.
				update_post_meta( $product_id, '_weight', $product['item_weight'] );
				update_post_meta( $product_id, '_length', $product['item_length'] );
				update_post_meta( $product_id, '_width', $product['item_width'] );
				update_post_meta( $product_id, '_height', $product['item_height'] );

				if ( isset( $product['sku'][0] ) ) {
					update_post_meta( $product_id, '_sku', $product['sku'][0] );
				} else {
					update_post_meta( $product_id, '_sku', $product['listing_id'] );
				}

				if ( $product['quantity'] > 0 ) {
					update_post_meta( $product_id, '_stock_status', 'instock' );
					update_post_meta( $product_id, '_manage_stock', 'yes' );
					update_post_meta( $product_id, '_stock', $product['quantity'] );
				} else {
					update_post_meta( $product_id, '_stock_status', 'outofstock' );
				}
				update_post_meta( $product_id, '_regular_price', $product['price'] );
				update_post_meta( $product_id, '_price', $product['price'] );

				$product_type = wc_get_product( $parent_id );
				if ( $product_type->is_type( 'variable' ) ) {
					$client = ced_etsy_getOauthClientObject( $shop_name );
					$params = array( 'listing_id' => $listing_id );
					 // Call for get inventory
					$response = $client->CallAPI( 'https://openapi.etsy.com/v2/listings/' . $listing_id . '/inventory', 'GET', $params, array( 'FailOnAccessError' => true ), $listings_variable );

					$listings_variable       = json_decode( json_encode( $listings_variable, true ), true );
					$etsy_variation_products = $listings_variable['results']['products'];
					foreach ( $etsy_variation_products as $key => $value ) {
						$variation_id = $value['product_id'];
						update_post_meta( $variation_id, '_reguler_price', $value['offerings'][0]['price']['currency_formatted_raw'] );
						update_post_meta( $variation_id, '_stock', $value['offerings'][0]['quantity'] );
					}
				}
				echo json_encode(
					array(
						'status'  => 200,
						'message' => __(
							'Etsy Product Inventory Updated Successfully In Woocommerce !',
							'woocommerce-etsy-integration'
						),
					)
				);
				wp_die();
			} else {
				wp_die();
			}
		}
	}
	
	/**
	 * ***********************************************************
	 * CED etsy prdouct field table on the simple product level .
	 * ***********************************************************
	 *
	 * @since 2.0.0
	 */
	public function ced_etsy_product_data_tabs( $tabs ) {
		$tabs['etsy_inventory'] = array(
			'label'  => __( 'Etsy', 'woocommerce-etsy-integration' ),
			'target' => 'etsy_inventory_options',
			'class'  => array( 'show_if_simple', 'show_if_variable'),
		);
		return $tabs;
	}


	/**
	 * ******************************************************************
	 * Woocommerce_Etsy_Integration_Admin ced_Etsy_product_data_panels.
	 * ******************************************************************
	 *
	 * @since 2.0.0
	 */
	public function ced_etsy_product_data_panels() {

		global $post;

		?>
		<div id='etsy_inventory_options' class='panel woocommerce_options_panel'><div class='options_group'>
			<?php
			echo "<div class='ced_etsy_simple_product_level_wrap'>";
			echo "<div class=''>";
			echo "<h2 class='etsy-cool'>Etsy Product Data";
			echo '</h2>';
			echo '</div>';
			echo "<div class='ced_etsy_simple_product_content'>";
			$this->ced_esty_render_fields_for_variation( $post->ID, true );
			echo '</div>';
			echo '</div>';
			?>
		</div></div>
		<?php

	}
	/**
	 * ******************************************************************
	 * Woocommerce_Etsy_Integration_Admin ced_Etsy_product_data_panels.
	 * ******************************************************************
	 *
	 * @since 2.0.0
	 */

	public function ced_etsy_render_product_fields( $loop, $variation_data, $variation ) {
		if ( ! empty( $variation_data ) ) {
			?>
			<div id='etsy_inventory_options_variable' class='panel woocommerce_options_panel'><div class='options_group'>
				<?php
				echo "<div class='ced_etsy_variation_product_level_wrap'>";
				echo "<div class='ced_etsy_parent_element'>";
				echo "<h2 class='etsy-cool'> Etsy Product Data";
				echo "<span class='dashicons dashicons-arrow-down-alt2 ced_etsy_instruction_icon'></span>";
				echo '</h2>';
				echo '</div>';
				echo "<div class='ced_etsy_variation_product_content ced_etsy_child_element'>";
				$this->ced_esty_render_fields_for_variation( $variation->ID, false );
				echo '</div>';
				echo '</div>';
				?>
			</div></div>
			<?php
		}
	}

	/**
	 * ********************************************************
	 * CREATE FIELDS AT EACH VARIATIONS LEVEL FOR ENTER PRICE
	 * ********************************************************
	 *
	 * @since 2.0.0
	 */

	public function ced_esty_render_fields_for_variation( $product_id = '', $simple_product = '' ) {

		$file_name = CED_ETSY_DIRPATH . 'admin/partials/product-fields.php';
		if ( file_exists( $file_name ) ) {
			require $file_name;
		}

		$productFieldInstance = Ced_Etsy_Product_Fields::get_instance();
		$product_fields       = $productFieldInstance->get_custom_products_fields();

		if ( ! empty( $product_fields ) ) {
			foreach ( $product_fields as $key => $value ) {

				$label          = isset( $value['fields']['label'] ) ? $value['fields']['label'] : '';
				$field_id       = isset( $value['fields']['id'] ) ? $value['fields']['id'] : '';
				$id             = 'ced_etsy_data[' . $product_id . '][' . $field_id . ']';
				$selected_value = get_post_meta( $product_id, $field_id, true );
				if ( '_select' == $value['type'] ) {
					$option_array     = array();
					$option_array[''] = '--select--';
					foreach ( $value['fields']['options'] as $option_key => $option ) {
						$option_array[ $option_key ] = $option;
					}
					woocommerce_wp_select(
						array(
							'id'          => $id,
							'label'       => $value['fields']['label'],
							'options'     => $option_array,
							'value'       => $selected_value,
							'desc_tip'    => 'true',
							'description' => $value['fields']['description'],
							'class'       => 'ced_etsy_product_select',
						)
					);
				} elseif ( '_text_input' == $value['type'] ) {
					woocommerce_wp_text_input(
						array(
							'id'          => $id,
							'label'       => $value['fields']['label'],
							'desc_tip'    => 'true',
							'description' => $value['fields']['description'],
							'type'        => 'text',
							'value'       => $selected_value,
						)
					);
				}
			}
		}
	}


	/**
	 * *****************************************************************
	 * Woocommerce_etsy_Integration_Admin ced_etsy_save_product_fields.
	 * *****************************************************************
	 *
	 * @since 2.0.0
	 */
	public function ced_etsy_save_product_fields_variation( $post_id = '', $i = '' ) {

		if ( empty( $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST['woocommerce_meta_nonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ) ) && false ) {
			$unused = true;
		}

		if ( isset( $_POST['ced_etsy_data'] ) ) {
			$sanitized_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING );
			if ( ! empty( $sanitized_array ) ) {
				foreach ( $sanitized_array['ced_etsy_data'] as $id => $value ) {
					foreach ( $value as $meta_key => $meta_val ) {
						update_post_meta( $id, $meta_key, $meta_val );
					}
				}
			}
		}
	}


	/**
	 * **************************************************************
	 * Woocommerce_Etsy_Integration_Admin ced_Etsy_save_meta_data
	 * **************************************************************
	 *
	 * @since 1.0.0
	 */
	public function ced_etsy_save_meta_data( $post_id = '' ) {

		if ( empty( $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST['woocommerce_meta_nonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ) ) && false ) {
			$unused = true;
		}
		if ( isset( $_POST['ced_etsy_data'] ) ) {
			$sanitized_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING );
			if ( ! empty( $sanitized_array ) ) {
				foreach ( $sanitized_array['ced_etsy_data'] as $id => $value ) {
					foreach ( $value as $meta_key => $meta_val ) {
						update_post_meta( $id, $meta_key, $meta_val );
					}
				}
			}
		}
	}

	public function ced_etsy_map_shipping_profiles_woo_cat(){
		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ($check_ajax) {
			$sanitized_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING );
			$woo_cat_id      = isset( $sanitized_array['woo_cat_id'] ) ? $sanitized_array['woo_cat_id']: '';
			$e_shiping_id    = isset( $sanitized_array['e_profile_id'] ) ?$sanitized_array['e_profile_id'] : '';
			$shop_name       = isset( $sanitized_array['shop_name'] ) ? $sanitized_array['shop_name'] : '';
			if ( '' != $shop_name && !empty( $woo_cat_id ) && !empty( $e_shiping_id ) ) {
				if ( is_array( @$woo_cat_id ) ) {
					 $cat_id = array_shift( array_reverse( $woo_cat_id ) );
				}else{
					$cat_id = $woo_cat_id;
				}
				// update_term_meta( $cat_id, 'ced_etsy_shipping_profile_with_woo_cat_'. $shop_name. '_'. $e_shiping_id, $e_shiping_id );
				update_term_meta( $cat_id, 'ced_etsy_shipping_profile_with_woo_cat_'. $shop_name . '_'. $e_shiping_id, $e_shiping_id );
				// $pre_saved_ids = get_term_meta( $cat_id, 'ced_etsy_shipping_profile_with_woo_cat_'. $shop_name, true );
				// $pre_saved_ids = !empty( $pre_saved_ids ) ? $pre_saved_ids : array();
				// $pre_saved_ids[$e_shiping_id][] = $cat_id;
				// echo "<pre>";
				// print_r( $pre_saved_ids );
				// update_term_meta( $e_shiping_id, 'ced_etsy_shipping_profile_with_woo_cat_'. $shop_name, $pre_saved_ids );

				// echo "Shipping Profile ID :-". $e_shiping_id . "<br>";
				// var_dump( update_term_meta( $e_shiping_id, 'ced_etsy_shipping_profile_with_woo_cat_'. $shop_name, $pre_saved_ids ) );

				$already_selected = get_option( 'ced_etsy_already_selected_profile_at_cat_'.$shop_name, array() );
				$already_selected[] = $cat_id;
				update_option( 'ced_etsy_already_selected_profile_at_cat_'.$shop_name, $already_selected );

				echo json_encode( array(
					'status'  => 200,
					'message' => __(
						'Profile is selected with this Category',
						'woocommerce-etsy-integration'
					),
				));
				wp_die();
			}else{
				echo json_encode( array(
					'status'  => 200,
					'message' => __(
						'Please select woo category with Etsy shipping profile',
						'woocommerce-etsy-integration'
					),
				));
				wp_die();
			}
		}
	}

	public function ced_etsy_delete_shipping_profile(){
		$check_ajax = check_ajax_referer( 'ced-etsy-ajax-seurity-string', 'ajax_nonce' );
		if ($check_ajax) {
			$sanitized_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING );
			$e_shiping_id     = isset( $sanitized_array['e_profile_id'] ) ?$sanitized_array['e_profile_id'] : array();
			$shop_name       = isset( $sanitized_array['shop_name'] ) ? $sanitized_array['shop_name'] : '';
			if ( '' != $shop_name && !empty( $e_shiping_id ) ) {
				$shop_id = get_etsy_shop_id( $shop_name );
				$action = 'application/shops/'.$shop_id.'/shipping-profiles/'.$e_shiping_id;
				// Refresh token if isn't.
				do_action( 'ced_etsy_refresh_token', $shop_name );
				$is_deleted = etsy_request()->delete($action, $shop_name, array(), 'DELETE');
				echo json_encode( array(
					'status'  => 200,
					'message' => __(
						'Profile is Deleted!',
						'woocommerce-etsy-integration'
					),
				));
				wp_die();
			}
		}
	}
}
