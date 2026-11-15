<?php
/**
 * Works out free time slots for a service on a given date.
 *
 * @package Slotwise
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Slot calculation and overlap checks. Times are minutes from midnight in the site's time zone.
 */
class Slotwise_Availability {

	/**
	 * Convert "HH:MM" to minutes.
	 *
	 * @param string $hm Time string.
	 * @return int
	 */
	public static function to_min( $hm ) {
		list( $h, $m ) = array_map( 'intval', explode( ':', $hm ) );
		return $h * 60 + $m;
	}

	/**
	 * Convert minutes to "HH:MM".
	 *
	 * @param int $min Minutes from midnight.
	 * @return string
	 */
	public static function to_hm( $min ) {
		return sprintf( '%02d:%02d', intdiv( $min, 60 ), $min % 60 );
	}

	/**
	 * Is the date bookable at all (open day, not in the past, within the booking window)?
	 *
	 * @param string $date Y-m-d.
	 * @return true|WP_Error
	 */
	public static function check_date( $date ) {
		$tz = wp_timezone();
		$d  = DateTimeImmutable::createFromFormat( '!Y-m-d', $date, $tz );
		if ( ! $d || $d->format( 'Y-m-d' ) !== $date ) {
			return new WP_Error( 'sw_bad_date', __( 'Please choose a valid date.', 'slotwise' ), array( 'status' => 400 ) );
		}
		$s     = Slotwise_Settings::get();
		$today = new DateTimeImmutable( 'today', $tz );
		if ( $d < $today ) {
			return new WP_Error( 'sw_past', __( 'That date has already passed.', 'slotwise' ), array( 'status' => 400 ) );
		}
		if ( $d > $today->modify( '+' . (int) $s['max_days'] . ' days' ) ) {
			return new WP_Error( 'sw_far', __( 'Bookings open a limited number of days ahead.', 'slotwise' ), array( 'status' => 400 ) );
		}
		if ( ! in_array( (int) $d->format( 'N' ), array_map( 'intval', $s['days'] ), true ) ) {
			return new WP_Error( 'sw_closed', __( 'We are closed on that day.', 'slotwise' ), array( 'status' => 400 ) );
		}
		return true;
	}

	/**
	 * Busy intervals on a date, as [start, end) minute pairs including the buffer.
	 *
	 * @param string $date Y-m-d.
	 * @return array
	 */
	public static function busy( $date ) {
		$s     = Slotwise_Settings::get();
		$posts = get_posts(
			array(
				'post_type'      => 'sw_booking',
				'post_status'    => array( 'publish', 'pending' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_query'     => array(
					array(
						'key'   => '_sw_date',
						'value' => $date,
					),
				),
			)
		);
		$busy = array();
		foreach ( $posts as $id ) {
			$start  = (int) get_post_meta( $id, '_sw_start', true );
			$end    = (int) get_post_meta( $id, '_sw_end', true );
			$busy[] = array( $start, $end + (int) $s['buffer'] );
		}
		return $busy;
	}

	/**
	 * Do two [start, end) ranges overlap?
	 *
	 * @param int $a1 Start A.
	 * @param int $a2 End A.
	 * @param int $b1 Start B.
	 * @param int $b2 End B.
	 * @return bool
	 */
	public static function overlaps( $a1, $a2, $b1, $b2 ) {
		return $a1 < $b2 && $b1 < $a2;
	}

	/**
	 * Free start times for a service on a date.
	 *
	 * @param string $date     Y-m-d.
	 * @param int    $duration Service length in minutes.
	 * @return array|WP_Error List of "HH:MM".
	 */
	public static function slots( $date, $duration ) {
		$ok = self::check_date( $date );
		if ( is_wp_error( $ok ) ) {
			return $ok;
		}
		$s     = Slotwise_Settings::get();
		$open  = self::to_min( $s['open'] );
		$close = self::to_min( $s['close'] );
		$busy  = self::busy( $date );
		$now   = new DateTimeImmutable( 'now', wp_timezone() );
		$min   = ( $now->format( 'Y-m-d' ) === $date ) ? ( (int) $now->format( 'G' ) * 60 + (int) $now->format( 'i' ) + 60 ) : 0;

		$out = array();
		for ( $t = $open; $t + $duration <= $close; $t += (int) $s['interval'] ) {
			if ( $t < $min ) {
				continue;
			}
			$clash = false;
			foreach ( $busy as $b ) {
				if ( self::overlaps( $t, $t + $duration, $b[0], $b[1] ) ) {
					$clash = true;
					break;
				}
			}
			if ( ! $clash ) {
				$out[] = self::to_hm( $t );
			}
		}
		return $out;
	}
}
