<?php
/**
 * Settings: opening hours, slot interval, buffer, notification email.
 *
 * @package Slotwise
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads and sanitises the plugin settings.
 */
class Slotwise_Settings {

	const OPTION = 'slotwise_settings';

	/**
	 * Default settings. Days use ISO numbers: 1 = Monday ... 7 = Sunday.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'days'        => array( 1, 2, 3, 4, 5, 6 ),
			'open'        => '09:00',
			'close'       => '17:00',
			'interval'    => 15,
			'buffer'      => 10,
			'max_days'    => 60,
			'notify'      => get_option( 'admin_email' ),
		);
	}

	/**
	 * Current settings merged over defaults.
	 *
	 * @return array
	 */
	public static function get() {
		$saved = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
	}

	/**
	 * Sanitise callback for register_setting().
	 *
	 * @param array $input Raw form input.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$d   = self::defaults();
		$out = array();

		$days        = isset( $input['days'] ) ? array_map( 'absint', (array) $input['days'] ) : array();
		$out['days'] = array_values( array_intersect( $days, range( 1, 7 ) ) );

		foreach ( array( 'open', 'close' ) as $key ) {
			$val         = isset( $input[ $key ] ) ? sanitize_text_field( $input[ $key ] ) : '';
			$out[ $key ] = preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $val ) ? $val : $d[ $key ];
		}
		if ( strtotime( $out['close'] ) <= strtotime( $out['open'] ) ) {
			add_settings_error( self::OPTION, 'hours', __( 'Closing time must be after opening time. Previous hours kept.', 'slotwise' ) );
			$prev         = self::get();
			$out['open']  = $prev['open'];
			$out['close'] = $prev['close'];
		}

		$out['interval'] = in_array( (int) ( $input['interval'] ?? 0 ), array( 5, 10, 15, 20, 30, 60 ), true ) ? (int) $input['interval'] : $d['interval'];
		$out['buffer']   = min( 120, absint( $input['buffer'] ?? $d['buffer'] ) );
		$out['max_days'] = max( 1, min( 365, absint( $input['max_days'] ?? $d['max_days'] ) ) );
		$email           = sanitize_email( $input['notify'] ?? '' );
		$out['notify']   = is_email( $email ) ? $email : $d['notify'];

		return $out;
	}
}
