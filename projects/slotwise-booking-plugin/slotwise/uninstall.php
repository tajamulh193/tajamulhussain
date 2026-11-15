<?php
/**
 * Remove plugin data when the plugin is deleted from the Plugins screen.
 *
 * @package Slotwise
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'slotwise_settings' );

foreach ( array( 'sw_booking', 'sw_service' ) as $slotwise_type ) {
	$slotwise_ids = get_posts(
		array(
			'post_type'      => $slotwise_type,
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);
	foreach ( $slotwise_ids as $slotwise_id ) {
		wp_delete_post( $slotwise_id, true );
	}
}
