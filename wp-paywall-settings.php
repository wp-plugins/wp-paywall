<?php
/**
 * Add Paywall to admin setting menu
 */
add_action ( 'admin_menu', 'wp_paywall_menu' );
function wp_paywall_menu() {
	add_options_page ( 'wp paywall', '<img  style="position:relative;top:4px" src="' . plugins_url ( 'wp-paywall-16x16.png', __FILE__ ) . '"/>&nbsp;Paywall', 'manage_options', 'wp_paywall_settings_page', 'wp_paywall_settings_callback' );
}

/**
 * Display message on the admin page, called after redirection
 */
add_action('admin_head-post.php', 'add_paywall_admin_notice');
function add_paywall_admin_notice() {
	// check whether to display the message
	if (get_option('wp_paywall_admin_message_switch')) { 
		add_action('admin_notices' , create_function( '', "echo '" . get_option('wp_paywall_admin_message') . "';" ) );
		
		// turn off the message
		update_option('wp_paywall_admin_message_switch', 0); 
	}
}

/**
 * Show the Paywall setting page
 */
function wp_paywall_settings_callback() {
	echo '<div class="wrap"><div id="icon-tools" class="icon32"></div>';
	echo '<h2>WordPress Paywall</h2>';
	echo '<form action="options.php" method="POST">';
	settings_fields ( 'wp_paywall_settings_group' );
	do_settings_sections ( 'wp_paywall_settings_page' );
	submit_button ();
	echo '</form>';
	echo '</div>';
}

/**
 * Add setting items to the Paywall setting page
 */
function wp_paywall_settings() {
	add_settings_section ( 'wp_paywall_setting_section', '', 'wp_paywall_setting_section_callback', 'wp_paywall_settings_page' );
	
	// applicaton code created after signup at PAYWALL_SERVICE_HOST
	add_settings_field ( 'wp_paywall_application_code', 'Application Code', 'wp_paywall_setting_application_code_callback', 'wp_paywall_settings_page', 'wp_paywall_setting_section' );
	
	//// your PayPal email address to receive payment
	//add_settings_field ( 'wp_paywall_email', 'PayPal Email Address', 'wp_paywall_setting_email_callback', 'wp_paywall_settings_page', 'wp_paywall_setting_section' );
	
	// Sale price
	add_settings_field ( 'wp_paywall_price', 'Default Price', 'wp_paywall_setting_price_callback', 'wp_paywall_settings_page', 'wp_paywall_setting_section' );
	
	// Display percent
	add_settings_field ( 'wp_paywall_display_percentage', 'Display Percentage', 'wp_paywall_setting_display_percentage_callback', 'wp_paywall_settings_page', 'wp_paywall_setting_section' );
	
	// our callback function just has to echo the <input>
	register_setting ( 'wp_paywall_settings_group', 'wp_paywall_application_code' );
	//register_setting ( 'wp_paywall_settings_group', 'wp_paywall_email' );
	register_setting ( 'wp_paywall_settings_group', 'wp_paywall_price' );
	register_setting ( 'wp_paywall_settings_group', 'wp_paywall_display_percentage' );
}
add_action ( 'admin_init', 'wp_paywall_settings' );

/**
 * The hook to display the wp-paywall setting section content
 */
function wp_paywall_setting_section_callback() {
	echo '<p>Paywall helps you make money on your content.</p>';
}

/**
 * The hook to connect Grabimo application code
 */
function wp_paywall_setting_application_code_callback() {
	$value = get_option ( 'wp_paywall_application_code', '' );
	$output = '<input type="text" id="wp_paywall_application_code" name="wp_paywall_application_code" size="50"  value="' . $value . '" /> <p class="description">To get your application code, sign up at <a href="https://paywall.grabimo.com">https://paywall.grabimo.com</a>.</p>';
	
	echo $output;
}

/**
 * The hook to display the blogger's email address (PayPal account)
 */
function wp_paywall_setting_email_callback() {
	$value = get_option ( 'wp_paywall_email', '' );
	$output = '<input type="text" id="wp_paywall_email" name="wp_paywall_email" size="30" value="' . $value . '" /> <p class="description">Use your PalPay email address to receive money from your readers.';
	echo $output;
}

/**
 * The hook handler to display the price for the blog
 * Default to $1.00
 */
function wp_paywall_setting_price_callback() {
	$value = get_option ( 'wp_paywall_price', DEFAULT_WP_PAYWALL_PRICE );
	$output = '$<input type="text" id="wp_paywall_price" name="wp_paywall_price" size="30" value="' . $value . '" /> <p class="description">Set the price for reading your post, e.g., $1.00 per post. Minimun is $' . MIN_WP_PAYWALL_PRICE . '.</p>';
	echo $output;
}

/**
 * The hook handler to display the display percentage setting on admin setting page
 * 0: hide totally
 * 100: show everything
 */
function wp_paywall_setting_display_percentage_callback() {
	$value = get_option ( 'wp_paywall_display_percentage', DEFAULT_WP_PAYWALL_DISPLAY_PERCENTAGE );
	$output = '<input type="text" id="wp_paywall_display_percentage" name="wp_paywall_display_percentage" size="30" value="' . $value . '" />% <p class="description">0%: hides all; 100%: shows everything.</p>';
	echo $output;
}

/**
 * Customize the Paywall plugin on the plugin list page
 * Add link to Paywall settings
 */
function wp_paywall_admin_action_links($links, $file) {	
	if (strpos($file, 'wp-paywall') !== false ) {
		$settings_link = '<img  style="position:relative;top:4px" src="' . plugins_url ( 'wp-paywall-16x16.png', __FILE__ ) . '"/>&nbsp;<a href="' . get_admin_url ( null, 'options-general.php?page=wp_paywall_settings_page' ) . '">Settings</a>';
		array_unshift ( $links, $settings_link );
	}
	
	return $links;	
}
add_filter ( 'plugin_action_links', 'wp_paywall_admin_action_links', 10, 4 );

