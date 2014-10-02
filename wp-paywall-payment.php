<?php

/**
 * To add wp-paywall specific javascript and css to the page 
 */
/*
 * function wp_paywall_scripts_files() { wp_register_script('paypal-js-file', 'https://www.paypalobjects.com/js/external/dg.js'); wp_enqueue_script( 'paypal-js-file' ); } add_action('wp_enqueue_scripts', 'wp_paywall_scripts_files');
 */
function wp_paywall_scripts_inline() {
	// echo '<script src="https://www.paypalobjects.com/js/external/dg.js" type="text/javascript"></script>';
	echo '<script src="' . plugins_url ( 'wp-paywall-payment.js', __FILE__ ) . '" type="text/javascript"></script>';
	echo '<script>var paywall_dg_flow = new PAYPAL.apps.DGFlow({trigger:"paypal_submit", sole: "false", expType:"instant"});</script>';
	echo '<script>var paywall_token_flow = new PAYPAL.apps.DGFlow({trigger:"token_recover", sole: "false", expType:"customized"});</script>';
}
add_action ( 'wp_footer', 'wp_paywall_scripts_inline' );


/**
 * To process the short code and display the PayPal pay now button if
 * viewer has not paid yet
 */
function wp_paywall_short_code($attrs) {
	global $wpdb;
	
	// get the current post ID
	$post_id = get_the_ID ();
	
	// Check if a paid cookie exists
	$wp_paywall_token = $_COOKIE ['payToken'];
	if (! empty ( $wp_paywall_token )) {
		$query = $wpdb->prepare ( "SELECT post_id FROM " . $wpdb->prefix . "paywall_tokens WHERE post_id=%d AND pay_token=%s", $post_id, $wp_paywall_token );
		$res = $wpdb->get_col ( $query );
		
		// paid before, don't show the PayPal button
		if ($res && count ( $res ) > 0) {
			return "";
		}
	}
	
	// get the UUID
	$query = $wpdb->prepare ( "SELECT uuid, price FROM " . $wpdb->prefix . "paywall_posts WHERE post_id=%d", $post_id );
	$res = $wpdb->get_row ( $query );
	if (!empty ( $res )) {
		$uuid = $res->uuid;
		$price = $res->price;
	} else {
		// no valid record, simply don't show the check out
		return "";
	}
	
	// create PayPal and token recovery button
	$success = home_url () . CHECKOUT_RETURN_SUCCESS_URL . '&' . CHECKOUT_RETURN_ID_KEY . '=' . $post_id . '&' . CHECKOUT_RETURN_TOKEN_KEY . '=';
	$cancel = home_url () . CHECKOUT_RETURN_CANCEL_URL;	
	$url = PAYWALL_API_PAY_TOKEN . '?success_url=' . urlencode($success) . '&cancel_url=' . urlencode($cancel) . '&uuid=' . $uuid;
	$html = '<form action="' . PAYWALL_API_CHECKOUT . '" method="post">'; 
	$html .=	'<input type="hidden" name="success_url" value="' . $success . '"/>';
	$html .=	'<input type="hidden" name="cancel_url" value="' . $cancel . '"/>';
	$html .=	'<input type="hidden" name="uuid" value="' . $uuid . '">';
	$html .=	'<input type="hidden" name="_csrf" value="{_csrf}" />';
	$html .=	'<input type="image" name="paypal_submit" id="paypal_submit" src="https://www.paypal.com/en_US/i/btn/btn_dg_pay_w_paypal.gif" style="border:0;padding:0" alt="Pay with PayPal"/>';
	$html .=	'<div style="width:150px;text-align:center;font-size:small">$' . $price . '&nbsp;&nbsp;<a title="Recover purchase token" href="#" onclick="paywall_token_flow.startFlow(\''. $url .'\')">Already Paid?</a></div>';
	$html .='</form>';

	return $html;
}
add_shortcode ( 'wp-paywall', 'wp_paywall_short_code' );

/**
 * filter post contents for redacted blogs
*/
function wp_paywall_content_filter($content) {	
	// Only filter the content if paywall short code exists in the post
	if (! has_shortcode ( $content, 'wp-paywall' )) {
		// this post doesn't have paywall installed
		return $content;
	}
	
	// check if the post has a valid redaction before
	global $wpdb;
	$isPaid = false;
	$post_id = get_the_ID ();
	
	// check if post was purchased before
	$wp_paywall_token = $_COOKIE ['payToken'];
	if (isset ( $wp_paywall_token )) {
		$query = $wpdb->prepare ( "SELECT post_id FROM " . $wpdb->prefix . "paywall_tokens WHERE post_id=%d AND pay_token=%s", $post_id, $wp_paywall_token );
		$res = $wpdb->get_col ( $query );
		if ($res && count ( $res ) > 0) {
			$isPaid = true;
		}
	}
	
	// show the conent
	if ($isPaid) {
		wp_write_paywall_log ( __FUNCTION__, "viewer has paid for post [$post_id]" );
		
		// has paid, then return the original post
		return $content;
	} else {
		// not paid, yet, return the processed post
		wp_write_paywall_log ( __FUNCTION__, "viewer has NOT paid for post [$post_id]" );
		$query = $wpdb->prepare ( "SELECT post FROM " . $wpdb->prefix . "paywall_posts WHERE post_id = %d", $post_id );
		$res = $wpdb->get_col ( $query );
		
		if ($res && count ( $res ) > 0) {
			return str_replace ( chr (6), '&#9633;',  $res[0] );
		} else {
			wp_write_paywall_log ( __FUNCTION__, "Redaction not exist for post [$post_id]" );
			return $content;
		}
	}
}
add_filter ( 'the_content', 'wp_paywall_content_filter' );

