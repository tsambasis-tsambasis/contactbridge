<?php
/** Always erase stored inquiries; optionally remove settings. @package Kontelio */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/** Clean the current site using a strictly prefixed query. */
function tscb_uninstall_site() {
	global $wpdb;
	wp_clear_scheduled_hook( 'tscb_cleanup' );
	// Inquiries must never survive uninstall, regardless of the preference for keeping settings.
	$last_id = 0;
	do {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Enumerate only plugin-owned records in bounded batches during uninstall.
		$inquiries = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND ID > %d ORDER BY ID ASC LIMIT 100", 'tscb_inquiry', $last_id ) );
		foreach ( $inquiries as $inquiry_id ) {
			$last_id = (int) $inquiry_id;
			wp_delete_post( $last_id, true );
		}
	} while ( count( $inquiries ) === 100 );
	$settings = get_option( 'tscb_settings', array() );
	if ( ! empty( $settings['delete_data'] ) ) {
		delete_option( 'tscb_settings' );
	}
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Enumerate only plugin-owned runtime keys during uninstall, then invalidate using delete_option.
	$names = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( 'tscb_lock_' ) . '%', $wpdb->esc_like( '_transient_tscb_' ) . '%', $wpdb->esc_like( '_transient_timeout_tscb_' ) . '%' ) );
	foreach ( $names as $name ) {
		delete_option( $name );
	}
	foreach ( array( 'email', 'telegram', 'whatsapp' ) as $channel ) {
		delete_transient( 'tscb_health_' . $channel );
	}
	delete_transient( 'tscb_mail_log' );
}

if ( is_multisite() ) {
	$tscb_offset = 0;
	do {
		$tscb_sites = get_sites( array( 'fields' => 'ids', 'number' => 100, 'offset' => $tscb_offset ) );
		foreach ( $tscb_sites as $tscb_site_id ) {
			switch_to_blog( $tscb_site_id );
			tscb_uninstall_site();
			restore_current_blog();
		}
		$tscb_offset += 100;
	} while ( count( $tscb_sites ) === 100 );
} else {
	tscb_uninstall_site();
}
