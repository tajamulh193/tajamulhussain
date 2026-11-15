<?php
/**
 * Post types: services (public to editors) and bookings (admin only).
 *
 * @package Slotwise
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers sw_service and sw_booking and their meta.
 */
class Slotwise_Post_Types {

	/**
	 * Register post types and meta.
	 */
	public static function register() {
		register_post_type(
			'sw_service',
			array(
				'labels'       => array(
					'name'          => __( 'Services', 'slotwise' ),
					'singular_name' => __( 'Service', 'slotwise' ),
					'add_new_item'  => __( 'Add service', 'slotwise' ),
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => 'slotwise',
				'show_in_rest' => true,
				'supports'     => array( 'title', 'editor', 'thumbnail' ),
			)
		);

		register_post_type(
			'sw_booking',
			array(
				'labels'       => array(
					'name'          => __( 'Bookings', 'slotwise' ),
					'singular_name' => __( 'Booking', 'slotwise' ),
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => 'slotwise',
				'supports'     => array( 'title' ),
				'capabilities' => array( 'create_posts' => 'do_not_allow' ),
				'map_meta_cap' => true,
			)
		);

		register_post_meta(
			'sw_service',
			'_sw_duration',
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 30,
				'sanitize_callback' => 'absint',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
		register_post_meta(
			'sw_service',
			'_sw_price',
			array(
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}
}
