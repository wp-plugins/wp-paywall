<?php

// # Function: Add Quick Tag For Paywall In TinyMCE >= WordPress 2.5
add_action ( 'init', 'paywall_tinymce_addbuttons' );
function paywall_tinymce_addbuttons() {
	if (! current_user_can ( 'edit_posts' ) && ! current_user_can ( 'edit_pages' )) {
		return;
	}
	
	if (get_user_option ( 'rich_editing' ) == 'true') {
		add_filter ( "mce_external_plugins", "paywall_tinymce_addplugin" );
		add_filter ( 'mce_buttons', 'paywall_tinymce_registerbutton' );
	}
}

function paywall_tinymce_registerbutton( $buttons ) {
	array_push ( $buttons, 'separator', 'paywall' );
	return $buttons;
}

function paywall_tinymce_addplugin( $plugin_array ) {
	$plugin_array ['paywall'] = plugins_url ( 'tinymce/plugins/paywall/editor_plugin.js' );
	return $plugin_array;
}

function paywall_admin_footer() {
	echo '<script type="text/javascript" src="' . plugins_url ( 'wp-paywall-redaction.js', __FILE__ ) . '"></script>';
	echo '<style>#qt_content_ed_paywall{background:url(' . plugins_url ( 'wp-paywall-16x16.png', __FILE__ ) . ')  1% 60% no-repeat!important;"}</style>';
}
add_action ( 'admin_footer-post-new.php', 'paywall_admin_footer' );
add_action ( 'admin_footer-post.php', 'paywall_admin_footer' );
add_action ( 'admin_footer-page-new.php', 'paywall_admin_footer' );
add_action ( 'admin_footer-page.php', 'paywall_admin_footer' );

/**
 * Hook on post is saved/updated
 * Send the content to the remote server for redaction and save
 * the processed document into local database
 */
function save_post_callback($post_id) {
	global $shortcode_tags;
	global $wpdb;
	
	$post = get_post ( $post_id );
	$content = $post->post_content;
	
	// do redaction only if this is the main post (ignore any revisions)
	if ($post->post_parent !== 0) {
		return;
	}
	
	// do redaction only if paywall shortcode exists
	if (!has_shortcode ( $content, 'wp-paywall' )) {
		return;
	}
	
	// get all shortcodes, redaction will not touch shortcodes
	$shortcodes = array ('shortcodes' => array ());
	foreach ( $shortcode_tags as $sc => $desc ) {
		$shortcodes ['shortcodes'] [] = $sc;
	}
	
	// get initial redaction level and post price, 1) paywall default or 2) site default
	$pos0 = strpos($content, '[wp-paywall');
	$pos1 = strpos($content, ']', $pos0);
	$temp = substr($content, $pos0 + 1, $pos1 - $pos0 - 1); // remove '[' and ']'
	$attr = shortcode_parse_atts($temp);
	if ($attr['price']) {
		$price = floatval($attr['price']);
	} else {
		$price = floatval(get_option('wp_paywall_price', DEFAULT_WP_PAYWALL_PRICE));
	}
	$price = $price > 0 ? $price : 0;
	$currency = 'USD';
	if ($price < MIN_WP_PAYWALL_PRICE) {
		update_option('wp_paywall_admin_message', '<span style="background-color:yellow">Price must be at least $'. MIN_WP_PAYWALL_PRICE .'. Redaction was not applied.</span>');
		update_option('wp_paywall_admin_message_switch', 1);
		return;
	}

	// redaction effect
	if ($attr['display_percentage']) {
		$display_percentage = intval($attr['display_percentage']);
	} else {
		$display_percentage = intval(get_option('wp_paywall_display_percentage', DEFAULT_WP_PAYWALL_DISPLAY_PERCENTAGE));
	} 
	$display_percentage = $display_percentage < 100 ? $display_percentage : 100;
	$display_percentage = $display_percentage > 0 ? $display_percentage : 0;
	if ($display_percentage === 100) {
		update_option('wp_paywall_admin_message', '<span style="background-color:yellow">You chose to display all content. Redaction was not applied.</span>');
		update_option('wp_paywall_admin_message_switch', 1);
		return;
	}
	
	// get application code, to validate the account
	$application_code = get_option ( 'wp_paywall_application_code' );
	
	// http://codex.wordpress.org/Function_Reference/get_the_author_meta
	$author_id = $post->post_author;
	$data = array (
			'post_url' => $post->guid,
			'post_content' => $content,
			'post_title' => get_the_title (),
			'amount' => $price,
			'currency' => $currency,
			'shortcodes' => json_encode ( $shortcodes ),
			'display_percentage' => $display_percentage,
			'application_code' => $application_code,
			'data_type' => 'json' 
	);
	
	// Get cURL resource
	$curl = curl_init ();
	
	// Set some options - we are passing in a useragent too here
	curl_setopt_array ( $curl, array (
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => PAYWALL_API_REDACT,
			CURLOPT_POST => 1, // POST request
			CURLOPT_POSTFIELDS => $data 
	));
	
	// Send the request & save response to $resp
	$curlResult = curl_exec ( $curl );
	//var_dump($content);
	$jsonReturn = json_decode ( $curlResult );
	if ($jsonReturn == NULL) {
		update_option('wp_paywall_admin_message', '<span style="background-color:yellow">Cannot run redaction. Pease try again later.</span>');
		update_option('wp_paywall_admin_message_switch', 1);
		return;
	}
	//var_dump($jsonReturn);
	
	// Close request to clear up some resources
	curl_close ( $curl );

	// process return results
	if ($jsonReturn->rc === 0) {
		$processedPost = $jsonReturn->redacted_content;
		//var_dump($processedPost);
		$paywallUuid = $jsonReturn->uuid;
		
		wp_write_paywall_log ( __FUNCTION__, "Redaction done for post_id: [$post_id], size of processed post: [" . strlen ( $processedPost ) . "]" );
		
		
		$res = $wpdb->replace ( $wpdb->prefix . "paywall_posts", array (
				'post_id' => $post_id,
				'uuid' => $paywallUuid,
				'price' => $price,
				'currency' => $currency,
				'post' => $processedPost 
		), array ('%d', '%s', '%f', '%s', '%s'));
		
		if ($res === false) {
			wp_write_paywall_log ( __FUNCTION__, 'Failed to save result into table '  . $wpdb->prefix . "paywall_posts, post_id: [$post_id]" );
			update_option('wp_paywall_admin_message', '<span style="background-color:yellow">Failed to save result into your site.</span>');
			update_option('wp_paywall_admin_message_switch', 1);
		} else {
			update_option('wp_paywall_admin_message', '<span style="color:green">The content was redacted successfully.</span>');
			update_option('wp_paywall_admin_message_switch', 1);
		}
		
	} else {
		wp_write_paywall_log ( __FUNCTION__, 'Failed to redact post, error: ' . $jsonReturn->err_msg );
		wp_redirect(admin_url('post.php?post='.$post_id.'&action=edit&message=11'));
		
		update_option('wp_paywall_admin_message', '<span style="background-color:yellow">Cannot run redaction. Pease try again later.</span>');
		update_option('wp_paywall_admin_message_switch', 1);
	}
}
add_action ( 'save_post', 'save_post_callback' );

