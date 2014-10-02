<?php

// save token into the database
global $wpdb;
global $wp_paywall_id;
global $wp_paywall_token;
$res = $wpdb->replace ( $wpdb->prefix . "paywall_tokens", array (
		'post_id' => $wp_paywall_id,
		'pay_token' => $wp_paywall_token ), array (
		'%d',
		'%s' 
) );

if ( !$res ) {
	wp_write_paywall_log ( __FUNCTION__, "Failed to do insert entry into table " . $wpdb->prefix . "paywall_tokens, post_id: [$wp_paywall_id], pay_token: [$wp_paywall_token]" );
} else {
	// close PayPal or token recovery window
	echo '<script>document.cookie="payToken=' . $wp_paywall_token . '"; if (window.opener){ window.opener.location.reload(); window.close();} else if (top.paywall_dg_flow.isOpen() == true) {top.paywall_dg_flow.closeFlow();} else if (top.paywall_token_flow.isOpen() == true) {top.paywall_token_flow.closeFlow();}</script>';
	echo 'If this page does not redirect <a href="' . get_permalink($wp_paywall_id) . '">Click Here</a>';
}
