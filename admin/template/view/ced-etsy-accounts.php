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
				'singular' => __( 'etsy Account', 'product-lister-etsy' ), // singular name of the listed records
				'plural'   => __( 'etsy Accounts', 'product-lister-etsy' ), // plural name of the listed records
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
		esc_html_e( 'No Accounts Linked.', 'product-lister-etsy' );
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

		$configure = '<a class="button-primary" href="' . admin_url( 'admin.php?page=ced_etsy&section=ced-etsy-settings&shop_name=' . $item['details']['ced_etsy_shop_name'] . '' ) . '">' . __( 'Configure', 'product-lister-etsy' ) . '</a>';
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
			'name'      => __( 'Shop Name', 'product-lister-etsy' ),
			'userid'    => __( 'Shop User ID', 'product-lister-etsy' ),
			'username'  => __( 'Shop Username', 'product-lister-etsy' ),
			'configure' => __( 'Configure', 'product-lister-etsy' ),
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
			<?php
			$notice = get_transient( 'ced_etsy_add_account_notice' );
			if ( $notice ) {
				if ( 'yes' == $notice ) {
					$class   = 'notice-success';
					$message = 'Account added successfully.';
				} else {
					$class   = 'notice-error';
					$message = 'Account not added . Etsy shop name invalid.';
					update_option( 'ced_etsy_access_token', '' );
					unset( $_SESSION['OAUTH_ACCESS_TOKEN'] );
				}
				echo "<div class='notice " . esc_attr( $class ) . "'><p>" . esc_attr( $message ) . '</p></div>';
			}
			?>
			<div class="ced_etsy_setting_header cedcommerce-top-border">
				<?php esc_attr( ced_etsy_cedcommerce_logo() ); ?>
				<label class="manage_labels"><b><?php esc_html_e( 'ETSY ACCOUNT', 'product-lister-etsy' ); ?></b></label>
				<?php
				$count = self::get_count();
				if ( $count < 1 ) {
					echo '<a href="javascript:void(0)" class="ced_etsy_add_account_button ced_etsy_add_button button-primary">Add Account</a>';
				}
				?>
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
					<h5><?php esc_html_e( 'Authorise your Etsy Account', 'product-lister-etsy' ); ?></h5>
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

				$access_tokens = get_option( 'ced_etsy_access_tokens', array() );
				unset( $access_tokens[ $value ] );
				update_option( 'ced_etsy_access_tokens', $access_tokens );

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

			require_once CED_ETSY_DIRPATH . 'admin/template/view/' . $this->current_action() . '.php';
		}
	}

}

if ( isset( $_POST['ced_etsy_authorise_account_button'] ) && 'Authorize' == $_POST['ced_etsy_authorise_account_button'] ) {
	if ( ! isset( $_POST['etsy_accounts_actions'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['etsy_accounts_actions'] ) ), 'etsy_accounts' ) ) {
		return;
	}
	unset( $_SESSION['OAUTH_ACCESS_TOKEN'] );
	$ced_h = Cedhandler::get_instance();
	$ced_h->etsy_vendor_signature();
}

if ( isset( $_GET['oauth_token'] ) && isset( $_GET['oauth_verifier'] ) ) {
	$ced_h = Cedhandler::get_instance();
	$ced_h->revieve_etsy_consent();
}

$ced_etsy_account_obj = new Ced_Etsy_Account_Table();
$ced_etsy_account_obj->prepare_items();
