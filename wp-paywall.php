<?php
/**

 * Plugin Name: WP Paywall

 * Plugin URI: http://downloads.wordpress.org/plugin/wp-paywall.zip

 * Description: The plugin allows you to setup a paywall for your valuable posts. Contents can be redacted and only fully accessible to paid viewers. 

 * Version: 1.0.1

 * Author: Grabimo

 * Author URI: https://www.grabimo.com

 * License: GPLv2 or later

 */

/**
 * Copyright 2014 Grabimo (email : admin@grabimo.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */

/**
 * Configurations *
 */
//define ( 'PAYWALL_SERVICE_HOST', 'http://localhost:9092' );
define ( 'PAYWALL_SERVICE_HOST', 'http://api.grabimo.com' );
define ( 'PAYWALL_API_REDACT', PAYWALL_SERVICE_HOST . '/redact' );
define ( 'PAYWALL_API_CHECKOUT', PAYWALL_SERVICE_HOST . '/paypal/set-express-checkout' );
define ( 'PAYWALL_API_PAY_TOKEN', PAYWALL_SERVICE_HOST . '/paypal/set-token-recovery' );
define ( CHECKOUT_RETURN_API_KEY, 'wp_paywall_return_api' );
define ( CHECKOUT_RETURN_ID_KEY, 'wp_paywall_return_id' );
define ( CHECKOUT_RETURN_TOKEN_KEY, 'wp_paywall_return_token' );
define ( CHECKOUT_RETURN_SUCCESS_PHP, 'wp-paywall-payment-success.php' );
define ( CHECKOUT_RETURN_CANCEL_PHP, 'wp-paywall-payment-cancel.php' );
define ( CHECKOUT_RETURN_SUCCESS_URL, '/index.php?' . CHECKOUT_RETURN_API_KEY . '=' . CHECKOUT_RETURN_SUCCESS_PHP );
define ( CHECKOUT_RETURN_CANCEL_URL, '/index.php?' . CHECKOUT_RETURN_API_KEY . '=' . CHECKOUT_RETURN_CANCEL_PHP );
define ( 'DEFAULT_WP_PAYWALL_DISPLAY_PERCENTAGE', 100 );
define ( 'DEFAULT_WP_PAYWALL_PRICE', 1.00 );
define ( 'MIN_WP_PAYWALL_PRICE', 0.40 );
define ( 'PAYWALL_DEBUG', false );

require_once 'wp-paywall-utils.php';
require_once 'wp-paywall-settings.php';
require_once 'wp-paywall-redaction.php';
require_once 'wp-paywall-payment.php';

/**
 * === create database ====================================
 *
 * https://codex.wordpress.org/Creating_Tables_with_Plugins
 *
 * =========================================================
 */
$wp_paywall_db_version = "1.0.0";
function paywall_db_install() {
	global $wpdb;
	global $wp_paywall_db_version;
	require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
	
	wp_write_paywall_log ( __FUNCTION__, "Create paywall DB..." );
	
	add_option ( "wp_paywall_db_version", $wp_paywall_db_version );
	
	$charset_collate = '';
	if ( ! empty( $wpdb->charset ) ) {
		$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
	}
	
	if ( ! empty( $wpdb->collate ) ) {
		$charset_collate .= " COLLATE {$wpdb->collate}";
	}
	
	// paywall posts persist processed post
	$table_name = $wpdb->prefix . "paywall_posts";
	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
		post_id bigint(20) unsigned NOT NULL,
		uuid VARCHAR(128) NOT NULL,
		price NUMERIC(15, 2),
		currency VARCHAR(3),
		post longtext NOT NULL,
		time_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (post_id)
	) $charset_collate;";
	dbDelta ( $sql );
	
	$table_name = $wpdb->prefix . "paywall_tokens";
	$sql = "CREATE TABLE IF NOT EXISTS $table_name  (
		post_id bigint(20) unsigned NOT NULL,
		pay_token VARCHAR(128) NOT NULL,
		time_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (pay_token, post_id)
	) $charset_collate;";
	dbDelta ( $sql );
}
register_activation_hook ( __FILE__, 'paywall_db_install' );

/**
 * === Create Ruturn URLs for payment flow ================
 * http://wordpress.stackexchange.com/questions/9870/how-do-you-create-a-virtual-page-in-wordpress
 *
 * =========================================================
 */
/* the parameter names need read from URL */
function add_paywall_return_query_vars($query_vars) {
	$query_vars [] = CHECKOUT_RETURN_API_KEY;
	$query_vars [] = CHECKOUT_RETURN_TOKEN_KEY;
	$query_vars [] = CHECKOUT_RETURN_ID_KEY;
	
	return $query_vars;
}
add_filter ( 'query_vars', 'add_paywall_return_query_vars' );

/* parse the URL based, only sucess and cancel PHP */
function add_paywall_return_parse_request($wp) {
	if (array_key_exists ( CHECKOUT_RETURN_API_KEY, $wp->query_vars )) {
		if ($wp->query_vars [CHECKOUT_RETURN_API_KEY] == CHECKOUT_RETURN_SUCCESS_PHP) {
			global $wp_paywall_id;
			$wp_paywall_id = $wp->query_vars [CHECKOUT_RETURN_ID_KEY];
			global $wp_paywall_token;
			$wp_paywall_token = $wp->query_vars [CHECKOUT_RETURN_TOKEN_KEY];
			
			wp_write_paywall_log ( __FUNCTION__, "post_id=" . $wp_paywall_id . " token=" . $wp_paywall_token );
			
			// call the success PHP fuciton to close the Popup and reload the image without redaction
			include CHECKOUT_RETURN_SUCCESS_PHP;
			exit ();
		} else {
			// call the cancel PHP fuciton to close the Popup
			include CHECKOUT_RETURN_CANCEL_PHP;
			exit ();
		}
	} else {
		// do nothing
		return;
	}
}
add_action ( 'parse_request', 'add_paywall_return_parse_request' );

