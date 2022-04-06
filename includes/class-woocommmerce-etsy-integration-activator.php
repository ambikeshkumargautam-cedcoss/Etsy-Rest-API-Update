<?php

/**
 * Fired during plugin activation
 *
 * @link       https://cedcommerce.com
 * @since      1.0.0
 *
 * @package    Woocommmerce_Etsy_Integration
 * @subpackage Woocommmerce_Etsy_Integration/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Woocommmerce_Etsy_Integration
 * @subpackage Woocommmerce_Etsy_Integration/includes
 */
class Woocommmerce_Etsy_Integration_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Account
		$tableName             = $wpdb->prefix . 'ced_etsy_accounts';
		$create_accounts_table =
		"CREATE TABLE $tableName (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(255) NOT NULL,
		account_status VARCHAR(255) NOT NULL,
		shop_id BIGINT(20) DEFAULT NULL,
		-- location VARCHAR(50) NOT NULL,
		shop_data TEXT DEFAULT NULL,
		PRIMARY KEY (id)
		);";
		dbDelta( $create_accounts_table );

		// Mail
		$sendString = get_option( 'admin_email' );
		$to         = 'shubhamagarwal@cedcommerce.com';
		$subject    = 'Etsy Integration for WooCommerce Activated by ' . $sendString;
		$body       = get_site_url();
		$headers[]  = " 'From:'" . $sendString . ' ';
		$headers[]  = 'Cc: ratandeepgupta@cedcommerce.com';
		$headers[]  = 'Content-Type: text/html; charset=utf-8';
		wp_mail( $to, $subject, $body, $headers );

		// Profile
		$tableName            = $wpdb->prefix . 'ced_etsy_profiles';
		$create_profile_table =
		"CREATE TABLE $tableName (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		profile_name VARCHAR(255) NOT NULL,
		profile_status VARCHAR(255) NOT NULL,
		shop_name VARCHAR(255) DEFAULT NULL,
		profile_data TEXT DEFAULT NULL,
		woo_categories TEXT DEFAULT NULL,
		PRIMARY KEY (id)
		);";
		dbDelta( $create_profile_table );

	}

}
