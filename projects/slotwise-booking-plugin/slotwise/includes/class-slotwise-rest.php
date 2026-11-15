<?php
/**
 * REST endpoints used by the booking form.
 *
 * @package Slotwise
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GET /slotwise/v1/slots and POST /slotwise/v1/bookings.
 */
class Slotwise_REST {

	/**
	 * Register routes.
	 */
	public static function register_routes() {
		register_rest_route(
			'slotwise/v1',
			'/slots',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_slots' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'service' => array( 'required' => true, 'type' => 'integer', 'minimum' => 1 ),
					'date'    => array( 'required' => true, 'type' => 'string', 'pattern' => '^\d{4}-\d{2}-\d{2}$' ),
				),
			)
		);

		register_rest_route(
			'slotwise/v1',
			'/bookings',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'create_booking' ),
				'permission_callback' => array( __CLASS__, 'check_nonce' ),
				'args'                => array(
					'service' => array( 'required' => true, 'type' => 'integer', 'minimum' => 1 ),
					'date'    => array( 'required' => true, 'type' => 'string', 'pattern' => '^\d{4}-\d{2}-\d{2}$' ),
					'time'    => array( 'required' => true, 'type' => 'string', 'pattern' => '^([01]\d|2[0-3]):[0-5]\d$' ),
					'name'    => array( 'required' => true, 'type' => 'string', 'minLength' => 2, 'maxLength' => 80 ),
					'email'   => array( 'required' => true, 'type' => 'string', 'format' => 'email' ),
					'phone'   => array( 'required' => false, 'type' => 'string', 'maxLength' => 30 ),
					'notes'   => array( 'required' => false, 'type' => 'string', 'maxLength' => 1000 ),
					'website' => array( 'required' => false, 'type' => 'string' ),
				),
			)
		);
	}

	/**
	 * Public form, so we check the wp_rest nonce printed with the form.
	 *
	 * @param WP_REST_Request $req Request.
	 * @return bool
	 */
	public static function check_nonce( $req ) {
		return (bool) wp_verify_nonce( $req->get_header( 'X-WP-Nonce' ), 'wp_rest' );
	}

	/**
	 * Look up a published service and its duration.
	 *
	 * @param int $id Service ID.
	 * @return WP_Post|WP_Error
	 */
	private static function service( $id ) {
		$p = get_post( $id );
		if ( ! $p || 'sw_service' !== $p->post_type || 'publish' !== $p->post_status ) {
			return new WP_Error( 'sw_service', __( 'That service is not available.', 'slotwise' ), array( 'status' => 404 ) );
		}
		return $p;
	}

	/**
	 * GET free slots.
	 *
	 * @param WP_REST_Request $req Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_slots( $req ) {
		$svc = self::service( (int) $req['service'] );
		if ( is_wp_error( $svc ) ) {
			return $svc;
		}
		$duration = max( 5, (int) get_post_meta( $svc->ID, '_sw_duration', true ) );
		$slots    = Slotwise_Availability::slots( $req['date'], $duration );
		if ( is_wp_error( $slots ) ) {
			return $slots;
		}
		return rest_ensure_response( array( 'date' => $req['date'], 'duration' => $duration, 'slots' => $slots ) );
	}

	/**
	 * POST a booking. Re-checks the slot inside a lock so two people cannot take the same time.
	 *
	 * @param WP_REST_Request $req Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function create_booking( $req ) {
		if ( '' !== (string) $req['website'] ) {
			// Honeypot field filled in: pretend success, store nothing.
			return rest_ensure_response( array( 'ok' => true ) );
		}
		$svc = self::service( (int) $req['service'] );
		if ( is_wp_error( $svc ) ) {
			return $svc;
		}

		$ip_key = 'sw_rate_' . md5( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '' );
		$count  = (int) get_transient( $ip_key );
		if ( $count >= 5 ) {
			return new WP_Error( 'sw_rate', __( 'Too many booking attempts. Please try again in a few minutes.', 'slotwise' ), array( 'status' => 429 ) );
		}
		set_transient( $ip_key, $count + 1, 10 * MINUTE_IN_SECONDS );

		$date     = $req['date'];
		$time     = $req['time'];
		$duration = max( 5, (int) get_post_meta( $svc->ID, '_sw_duration', true ) );

		$lock = 'sw_lock_' . $date;
		if ( get_transient( $lock ) ) {
			usleep( 300000 );
		}
		set_transient( $lock, 1, 10 );

		$free = Slotwise_Availability::slots( $date, $duration );
		if ( is_wp_error( $free ) ) {
			delete_transient( $lock );
			return $free;
		}
		if ( ! in_array( $time, $free, true ) ) {
			delete_transient( $lock );
			return new WP_Error( 'sw_taken', __( 'Sorry, that time has just been taken. Please pick another.', 'slotwise' ), array( 'status' => 409 ) );
		}

		$name  = sanitize_text_field( $req['name'] );
		$email = sanitize_email( $req['email'] );
		$start = Slotwise_Availability::to_min( $time );

		$id = wp_insert_post(
			array(
				'post_type'   => 'sw_booking',
				'post_status' => 'pending',
				/* translators: 1: client name, 2: service, 3: date, 4: time */
				'post_title'  => sprintf( __( '%1$s · %2$s · %3$s %4$s', 'slotwise' ), $name, $svc->post_title, $date, $time ),
				'meta_input'  => array(
					'_sw_service' => $svc->ID,
					'_sw_date'    => $date,
					'_sw_start'   => $start,
					'_sw_end'     => $start + $duration,
					'_sw_name'    => $name,
					'_sw_email'   => $email,
					'_sw_phone'   => sanitize_text_field( (string) $req['phone'] ),
					'_sw_notes'   => sanitize_textarea_field( (string) $req['notes'] ),
				),
			),
			true
		);
		delete_transient( $lock );
		if ( is_wp_error( $id ) ) {
			return new WP_Error( 'sw_save', __( 'We could not save your booking. Please try again.', 'slotwise' ), array( 'status' => 500 ) );
		}

		self::send_emails( $id, $svc, $date, $time, $name, $email );

		return rest_ensure_response(
			array(
				'ok'      => true,
				'id'      => $id,
				'message' => sprintf(
					/* translators: 1: service, 2: date, 3: time */
					__( 'Booked: %1$s on %2$s at %3$s. A confirmation email is on its way.', 'slotwise' ),
					$svc->post_title,
					date_i18n( get_option( 'date_format' ), strtotime( $date ) ),
					$time
				),
			)
		);
	}

	/**
	 * Confirmation to the client and a notice to the business.
	 *
	 * @param int     $id    Booking ID.
	 * @param WP_Post $svc   Service.
	 * @param string  $date  Date.
	 * @param string  $time  Time.
	 * @param string  $name  Client name.
	 * @param string  $email Client email.
	 */
	private static function send_emails( $id, $svc, $date, $time, $name, $email ) {
		$s    = Slotwise_Settings::get();
		$site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		/* translators: %s: site name */
		$subject = sprintf( __( 'Your booking with %s', 'slotwise' ), $site );
		$body    = sprintf(
			/* translators: 1: name, 2: service, 3: date, 4: time, 5: site */
			__( "Hello %1\$s,\n\nThank you for booking %2\$s on %3\$s at %4\$s.\nWe will confirm shortly.\n\n%5\$s", 'slotwise' ),
			$name,
			$svc->post_title,
			$date,
			$time,
			$site
		);
		wp_mail( $email, $subject, $body );
		wp_mail(
			$s['notify'],
			/* translators: %s: booking title */
			sprintf( __( 'New booking: %s', 'slotwise' ), get_the_title( $id ) ),
			admin_url( 'post.php?post=' . (int) $id . '&action=edit' )
		);
	}
}
