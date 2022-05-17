<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
class Cedhandler {

	// Directory name
	public $dir_name;
	// To get object of this class
	private static $_instance;
	/**
	 * Get instace of the class
	 *
	 * @since 1.0.0
	 */

	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	/**
	 * Requrie imoprtant template.
	 *
	 * @since 1.0.0
	 */
	public function ced_require( $file_name = '' ) {
		if ( '' != $file_name && '' != $this->dir_name ) {
			if ( file_exists( CED_ETSY_DIRPATH . $this->dir_name . $file_name . '.php' ) ) {
				require_once CED_ETSY_DIRPATH . $this->dir_name . $file_name . '.php';
			}
		}
	}

	public static function ced_header() {
		require_once CED_ETSY_DIRPATH . 'admin/template/view/class-ced-view-header.php';
		// $header = new Cedcommerce\Template\View\CedEtsyHeader;
		// $header = new CedEtsyHeader();
	}

	public static function show_notice_top( $shop_name = '' ) {
		$hour = gmdate( 'H' );
		if ( $hour >= 20 ) {
			$greetings = 'Good Evening';
		} elseif ( $hour > 17 ) {
			$greetings = 'Good Evening';
		} elseif ( $hour > 11 ) {
			$greetings = 'Good Afternoon';
		} elseif ( $hour < 12 ) {
			$greetings = 'Good Morning';
		}
		$shop_name = 'there';
		$content   = '
		<div class="ced_etsy_heading ced_etsy_upgrade_notice" >
		<h4>You are using free version of <a>Product Lister for Etsy</a> !</h4>
		<p> Hi ' . $shop_name . ' , ' . ucfirst( $greetings ) . ' ! With the free version you are restricted to sync max of 50 products from WooCommerce to Etsy and won\'t be able to process Etsy orders . Upgrade now to sync unlimited products and orders.</p>
		<hr>
		<p><a href="https://cedcommerce.com/woocommerce-extensions/etsy-integration-woocommerce" target="_blank"><button id="" type="submit" class="button-primary get_preminum">Upgrade Now</button></a></p>
		</div>
		';
		return $content;
	}

	public function etsy_vendor_signature() {
		$this->dir_name = 'admin/etsy/lib/vendor/';
		$this->ced_require( 'oauth_client' );
		$this->ced_require( 'http' );
		$client                = new oauth_client_class();
		$client->debug         = false;
		$client->debug_http    = true;
		$client->server        = 'Etsy';
		$client->redirect_uri  = admin_url( 'admin.php?page=ced_etsy' );
		$application_line      = __LINE__;
		$client->client_id     = get_option( 'ced_client_id', '' );
		$client->client_secret = get_option( 'ced_client_secret', '' );
		if ( strlen( $client->client_id ) != 0 || strlen( $client->client_secret ) != 0 ) {
			$success = $client->Initialize();
			if ( $success ) {
				$saved_etsy_details = get_option( 'ced_etsy_details', array() );

				if ( ! isset( $_POST['etsy_accounts_actions'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['etsy_accounts_actions'] ) ), 'etsy_accounts' ) ) {
					return;
				}

				$shopName = isset( $_POST['ced_etsy_shop_name'] ) ? sanitize_text_field( wp_unslash( $_POST['ced_etsy_shop_name'] ) ) : '';
				$saved_etsy_details[ $shopName ]['details']['ced_etsy_shop_name'] = $shopName;
				update_option( 'ced_etsy_details', $saved_etsy_details );
				update_option( 'ced_etsy_shop_name', $shopName );
				$client->Process();
			}
		}
	}

	public function revieve_etsy_consent() {
		$this->dir_name = 'admin/etsy/lib/vendor/';
		$this->ced_require( 'oauth_client' );
		$this->ced_require( 'http' );
		$client                = new oauth_client_class();
		$client->debug         = false;
		$client->debug_http    = true;
		$client->server        = 'Etsy';
		$client->redirect_uri  = admin_url( 'admin.php?page=ced_etsy' );
		$application_line      = __LINE__;
		$client->client_id     = get_option( 'ced_client_id', '' );
		$client->client_secret = get_option( 'ced_client_secret', '' );
		$client->Initialize();
		$client->Process();
		$shopName = get_option( 'ced_etsy_shop_name' );
		if ( isset( $_SESSION['OAUTH_ACCESS_TOKEN'] ) && ! empty( $_SESSION['OAUTH_ACCESS_TOKEN'] ) && ! empty( $shopName ) ) {
			$access_token               = $_SESSION['OAUTH_ACCESS_TOKEN'];
			$access_tokens              = get_option( 'ced_etsy_access_tokens', array() );
			$access_tokens[ $shopName ] = $access_token;
			update_option( 'ced_etsy_access_tokens', $access_tokens );
		}
		$saved_etsy_details = get_option( 'ced_etsy_details', array() );
		if ( ! empty( $shopName ) ) {
			$success = $client->CallAPI( "https://openapi.etsy.com/v2/shops/{$shopName}", 'GET', array(), array( 'FailOnAccessError' => true ), $authorisedShopDetails );
			if ( $success ) {
				$authorisedShopDetails = json_decode( json_encode( $authorisedShopDetails ), true );
				$user_id               = isset( $authorisedShopDetails['results'][0]['user_id'] ) ? $authorisedShopDetails['results'][0]['user_id'] : '';
				$user_name             = isset( $authorisedShopDetails['results'][0]['login_name'] ) ? $authorisedShopDetails['results'][0]['login_name'] : '';
				$shop_id               = isset( $authorisedShopDetails['results'][0]['shop_id'] ) ? $authorisedShopDetails['results'][0]['shop_id'] : '';
				if ( isset( $_SESSION['OAUTH_ACCESS_TOKEN'] ) && ! empty( $_SESSION['OAUTH_ACCESS_TOKEN'] ) && is_array( $_SESSION['OAUTH_ACCESS_TOKEN'] ) ) {
					foreach ( $_SESSION['OAUTH_ACCESS_TOKEN'] as $key => $value ) {
						$secret       = $value['secret'];
						$access_token = $value['value'];
					}
				}
				$saved_etsy_details[ $shopName ]['details']['user_id']                 = $user_id;
				$saved_etsy_details[ $shopName ]['details']['user_name']               = $user_name;
				$saved_etsy_details[ $shopName ]['details']['shop_id']                 = $shop_id;
				$saved_etsy_details[ $shopName ]['details']['ced_etsy_keystring']      = get_option( 'ced_client_id', '' );
				$saved_etsy_details[ $shopName ]['details']['ced_etsy_shared_string']  = get_option( 'ced_client_secret', '' );
				$saved_etsy_details[ $shopName ]['details']['ced_shop_account_status'] = 'Active';
				$saved_etsy_details[ $shopName ]['access_token']['oauth_token']        = $access_token;
				$saved_etsy_details[ $shopName ]['access_token']['oauth_token_secret'] = $secret;
				$saved_etsy_details[ $shopName ]['details']['nofttlprd']               = 50;
				if ( count( $saved_etsy_details ) < 2 ) {
					update_option( 'ced_etsy_details', $saved_etsy_details );
				}
			} else {
				delete_option( 'ced_etsy_details' );
			}
		}
	}
}
