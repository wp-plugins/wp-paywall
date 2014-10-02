<?php

// If uninstall was not called from WordPress, then exit
if( !defined( 'WP_UNINSTALL_PLUGIN') )
	exit ();

// Delete all plugin related fields from the options table
delete_option( 'wp_paywall_application_code' );
delete_option( 'wp_paywall_email' );
delete_option( 'wp_paywall_price' );
delete_option( 'wp_paywall_display_percentage' );

// DB
delete_option ( "wp_paywall_db_version" );

// admin page
delete_option( 'wp_paywall_admin_message' );
delete_option( 'wp_paywall_admin_message_switch' );


//drop a custom db table
global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}paywall_posts" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}paywall_tokens" );

