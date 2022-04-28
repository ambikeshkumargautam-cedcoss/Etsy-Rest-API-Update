<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

if ( ! session_id() ) {
	session_start();
}

class Ced_Etsy_Account_Table extends WP_List_Table {

	/** Class constructor */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'etsy Account', 'woocommerce-etsy-integration' ), // singular name of the listed records
				'plural'   => __( 'etsy Accounts', 'woocommerce-etsy-integration' ), // plural name of the listed records
				'ajax'     => false, // does this table support ajax?
			)
		);
	}

	public function prepare_items() {
		global $wpdb;

		$per_page = apply_filters( 'ced_etsy_account_list_per_page', 10 );
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		// Column headers
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$current_page = $this->get_pagenum();
		if ( 1 < $current_page ) {
			$offset = $per_page * ( $current_page - 1 );
		} else {
			$offset = 0;
		}

		$this->items = self::get_accounts( $per_page, $current_page );

		$count = self::get_count();

		// Set the pagination

		if ( ! $this->current_action() ) {

			$this->set_pagination_args(
				array(
					'total_items' => $count,
					'per_page'    => $per_page,
					'total_pages' => ceil( $count / $per_page ),
				)
			);

			$accounts = array();
			$accounts = self::get_accounts( $per_page, $current_page );
			if ( ! empty( $accounts ) ) {
				$this->items = $accounts;
			}
			$this->renderHTML();
		} else {
			$this->process_bulk_action();
		}
	}



	public function get_accounts( $per_page = 10, $page_number = 1 ) {

		$result = get_option( 'ced_etsy_details', array() );
		return array_filter( $result );
	}

	/**
	 * Function to count number of responses in result
	 */
	public function get_count() {

		$result = get_option( 'ced_etsy_details', array() );
		return count( array_filter( $result ) );

	}

	/** Text displayed when no customer data is available */
	public function no_items() {
		esc_html_e( 'No Account Linked.', 'woocommerce-etsy-integration' );
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		echo ' <input type="checkbox" value="' . esc_attr( $item['details']['ced_etsy_shop_name'] ) . '" name="etsy_account_name[]" > ';
	}


	/**
	 * Method for name column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	public function column_name( $item ) {

		echo '<b>' . esc_attr( $item['details']['ced_etsy_shop_name'] ) . '</b>';

	}

	public function column_username( $item ) {
		echo esc_attr( $item['details']['user_name'] );
	}

	public function column_userid( $item ) {

		echo esc_attr( $item['details']['user_id'] );
	}

	public function column_configure( $item ) {

		$configure = '<a class="btn btn-sm btn-primary" href="' . admin_url( 'admin.php?page=ced_etsy&section=settings&shop_name=' . $item['details']['ced_etsy_shop_name'] . '' ) . '">' . __( 'Configure', 'woocommerce-etsy-integration' ) . '</a>';
		return $configure;

	}

	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'        => '<input type="checkbox" />',
			'name'      => __( 'Shop Name', 'woocommerce-etsy-integration' ),
			'userid'    => __( 'Shop User ID', 'woocommerce-etsy-integration' ),
			'username'  => __( 'Shop Username', 'woocommerce-etsy-integration' ),
			'configure' => __( 'Configure', 'woocommerce-etsy-integration' ),
		);
		$columns = apply_filters( 'ced_etsy_alter_feed_table_columns', $columns );
		return $columns;
	}


	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array();
		return $sortable_columns;
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			// 'bulk-enable'  => 'Enable',
			// 'bulk-disable' => 'Disable',
			'bulk-delete' => 'Delete',
		);
		return $actions;
	}

	/**
	 * Function to get changes in html
	 */
	public function renderHTML() {
		?>
		<div class="ced_etsy_wrap ced_etsy_wrap_extn">
			<div class="p-2 m-2 bg-light text-dark border-4 border-primary border-top d-flex justify-content-between">
				<div>
				<?php esc_attr( ced_etsy_cedcommerce_logo() ); ?>
				<label class="manage_labels">
					<b><?php esc_html_e( 'ETSY ACCOUNT', 'woocommerce-etsy-integration' ); ?></b>
				</label>
			</div>
			<div>
				<?php
				$count = self::get_count();
				if ( $count < 1 ) {
					echo '<input type="button" class="btn btn-primary m-2 ced_etsy_add_account_button" value="Add Account">';
				}
				?>
			</div>
			</div>
			<?php esc_attr( display_support_html() ); ?>
			<div>				
				<div id="post-body" class="metabox-holder columns-2">
					<div id="">
						<div class="meta-box-sortables ui-sortable">
							<form method="post">
								<?php
								wp_nonce_field( 'etsy_accounts', 'etsy_accounts_actions' );
								$this->display();
								?>
							</form>
						</div>
					</div>
					<div class="clear"></div>
				</div>
				<br class="clear">
			</div>
		</div>
		<div class="ced_etsy_add_account_popup_main_wrapper">
			<div class="ced_etsy_add_account_popup_content">
				<div class="ced_etsy_add_account_popup_header">
					<h5><?php esc_html_e( 'Authorise your Etsy Account', 'woocommerce-etsy-integration' ); ?></h5>
					<span class="ced_etsy_add_account_popup_close">X</span>
				</div>
				<div class="ced_etsy_add_account_popup_body">
					<h6>Steps to authorise your account:</h6>
					<ul>
						<li>Enter your Etsy shop name.It must be exactly as on Etsy .Click <a><i>Get Shop Name </i></a>to view Etsy shop name.</li>
						<li>Click on the "Authorize" button which will redirect you to <b>"https://openapi.etsy.com/v2".</b></li>
						<li>On the etsy authorization page you have to log in with your seller login details.</li>
						<li>You have to then click on "Allow Access" button to enable access to API.</li>
					</ul>
					<form action="" method="post">
						<?php
						wp_nonce_field( 'etsy_accounts', 'etsy_accounts_actions' );
						?>
						<div class="ced_etsy_popup_wrap">
							<div class="ced_etsy_popup_container">
								<div class="ced_etsy_popup_label"><label>Enter Etsy Shop Name</label></br><span><a class="get_etsy_sop_name" href="https://www.etsy.com/your/shops/me?ref=seller-platform-mcnav" target="#"><i>[ Get Shop Name -> ]</i></a></span></div>
								<div class="ced_etsy_popup_input"><input id="ced_etsy_shop_name" type="text" name="ced_etsy_shop_name" required=""></div>
							</div>
						</div>
						<div class="ced_etsy_add_account_button_wrapper">
							<input type="submit" value="Authorize" id="ced_etsy_authorise_account_button" name="ced_etsy_authorise_account_button" class="ced_etsy_add_button button-primary">
						</div>
					</form>
				</div>
			</div>
		</div>


		<?php
	}

	public function current_action() {

		if ( isset( $_GET['section'] ) ) {
			$action = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '';
			return $action;
		} elseif ( isset( $_POST['action'] ) ) {
			if ( ! isset( $_POST['etsy_accounts_actions'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['etsy_accounts_actions'] ) ), 'etsy_accounts' ) ) {
				return;
			}
			$action = isset( $_POST['action'] ) ? sanitize_text_field( wp_unslash( $_POST['action'] ) ) : '';
			return $action;
		} elseif ( isset( $_POST['action2'] ) ) {
			if ( ! isset( $_POST['etsy_accounts_actions'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['etsy_accounts_actions'] ) ), 'etsy_accounts' ) ) {
				return;
			}
			$action = isset( $_POST['action2'] ) ? sanitize_text_field( wp_unslash( $_POST['action2'] ) ) : '';
			return $action;
		}
	}

	public function process_bulk_action() {

		if ( ! session_id() ) {
			session_start();
		}

		if ( 'bulk-delete' === $this->current_action() || ( isset( $_GET['action'] ) && 'bulk-delete' === $_GET['action'] ) || isset( $_GET['action2'] ) && 'bulk-delete' === $_GET['action2'] ) {
			if ( ! isset( $_POST['etsy_accounts_actions'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['etsy_accounts_actions'] ) ), 'etsy_accounts' ) ) {
				return;
			}
			$sanitized_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING );
			$accountNames    = isset( $sanitized_array['etsy_account_name'] ) ? $sanitized_array['etsy_account_name'] : array();
			foreach ( $accountNames as $key => $value ) {
				$shops  = get_option( 'ced_etsy_details', '' );
				$tokens = get_option( 'ced_etsy_access_token', array() );
				unset( $shops[ $value ] );
				update_option( 'ced_etsy_details', $shops );
				update_option( 'ced_etsy_access_token', '' );
				unset( $_SESSION['OAUTH_ACCESS_TOKEN'] );

			}
			$redirectURL = get_admin_url() . 'admin.php?page=ced_etsy';
			wp_redirect( $redirectURL );
		} elseif ( 'bulk-enable' === $this->current_action() || ( isset( $_GET['action'] ) && 'bulk-enable' === $_GET['action'] ) ) {
			if ( ! isset( $_POST['etsy_accounts_actions'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['etsy_accounts_actions'] ) ), 'etsy_accounts' ) ) {
				return;
			}
			$sanitized_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING );
			$accountNames    = isset( $sanitized_array['etsy_account_name'] ) ? $sanitized_array['etsy_account_name'] : array();
			foreach ( $accountNames as $key => $value ) {
				$shops                             = get_option( 'ced_etsy_details', '' );
				$shops[ $value ]['account_status'] = 'Active';
				update_option( 'ced_etsy_details', $shops );
			}
			$redirectURL = get_admin_url() . 'admin.php?page=ced_etsy';
			wp_redirect( $redirectURL );
		} elseif ( 'bulk-disable' === $this->current_action() || ( isset( $_GET['action'] ) && 'bulk-disable' === $_GET['action'] ) ) {
			if ( ! isset( $_POST['etsy_accounts_actions'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['etsy_accounts_actions'] ) ), 'etsy_accounts' ) ) {
				return;
			}
			$sanitized_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING );
			$accountNames    = isset( $sanitized_array['etsy_account_name'] ) ? $sanitized_array['etsy_account_name'] : array();
			foreach ( $accountNames as $key => $value ) {
				$shops                             = get_option( 'ced_etsy_details', '' );
				$shops[ $value ]['account_status'] = 'InActive';
				update_option( 'ced_etsy_details', $shops );
			}
			$redirectURL = get_admin_url() . 'admin.php?page=ced_etsy';
			wp_redirect( $redirectURL );

		} elseif ( isset( $_GET['section'] ) ) {
			require_once CED_ETSY_DIRPATH . 'admin/template/view/class-ced-view-' . $this->current_action() . '.php';
		}
	}
}

if ( isset( $_POST['ced_etsy_authorise_account_button'] ) && 'Authorize' == $_POST['ced_etsy_authorise_account_button'] ) {
	$scopes = array(
		'address_r',
		'address_w',
		'billing_r',
		'cart_r',
		'cart_w',
		'email_r',
		'favorites_r',
		'favorites_w',
		'feedback_r',
		'listings_d',
		'listings_r',
		'listings_w',
		'profile_r',
		'profile_w',
		'recommend_r',
		'recommend_w',
		'shops_r',
		'shops_w',
		'transactions_r',
		'transactions_w',
	);

	$string = bin2hex( random_bytes( 32 ) );

	$verifier = strtr(
		trim(
			base64_encode( pack( 'H*', $string ) ),
			'='
		),
		'+/',
		'-_'
	);

	$code_challenge = strtr(
		trim(
			base64_encode( pack( 'H*', hash( 'sha256', $verifier ) ) ),
			'='
		),
		'+/',
		'-_'
	);

	$scopes       = urlencode( implode( ' ', $scopes ) );
	$client_id    = 'b2pa8bczfrwnuccpevnql8eh';
	$redirect_uri = admin_url( 'admin.php?page=ced_etsy' );
	$shop_name    = isset( $_POST['ced_etsy_shop_name'] ) ? $_POST['ced_etsy_shop_name'] : '';
	update_option( 'ced_etsy_shop_name', $shop_name );
	$auth_url = "https://www.etsy.com/oauth/connect?response_type=code&redirect_uri=$redirect_uri&scope=$scopes&client_id=$client_id&state=$verifier&code_challenge=$code_challenge&code_challenge_method=S256";
	$log      = '';
	$log     .= "Authorization process starts\n";
	$log     .= "Redirecting to www.etsy.com\n";
	etsy_write_logs( log_head() . $log, 'general', false );
	$auth_url = "https://woodemo.cedcommerce.com/woocommerce/authorize/etsy/authorize.php";
	wp_redirect( $auth_url );
	exit;
}


$ced_etsy_account_obj = new Ced_Etsy_Account_Table();
$ced_etsy_account_obj->prepare_items();
