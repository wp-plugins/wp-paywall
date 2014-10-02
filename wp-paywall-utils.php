<?php
/** Logging utilities **/
function wp_write_paywall_log($func_name, $log) {
	if (true === PAYWALL_DEBUG) {
		if (is_array ( $log ) || is_object ( $log )) {
			error_log ( "WPPayWall::$func_name::" . print_r ( $log, true ) );
		} else {
			error_log ( "WPPayWall::$func_name::$log" );
		}
	}
}
