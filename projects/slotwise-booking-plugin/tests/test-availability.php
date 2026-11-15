<?php
/**
 * Unit tests for the slot calculation, run without WordPress: php tests/test-availability.php
 * The few WordPress functions the class uses are replaced with small stand-ins below.
 */

define( 'ABSPATH', __DIR__ );

class WP_Error {
	public $code;
	public function __construct( $code ) { $this->code = $code; }
}
function is_wp_error( $x ) { return $x instanceof WP_Error; }
function __( $s ) { return $s; }
function absint( $n ) { return abs( (int) $n ); }
function wp_parse_args( $a, $d ) { return array_merge( $d, $a ); }
function wp_timezone() { return new DateTimeZone( 'Asia/Karachi' ); }
$GLOBALS['opts']     = array();
$GLOBALS['bookings'] = array();
function get_option( $k, $d = false ) { return $GLOBALS['opts'][ $k ] ?? $d; }
function get_posts( $q ) {
	$date = $q['meta_query'][0]['value'];
	return array_keys( array_filter( $GLOBALS['bookings'], function ( $b ) use ( $date ) { return $b['_sw_date'] === $date; } ) );
}
function get_post_meta( $id, $k ) { return $GLOBALS['bookings'][ $id ][ $k ]; }

require __DIR__ . '/../slotwise/includes/class-slotwise-settings.php';
require __DIR__ . '/../slotwise/includes/class-slotwise-availability.php';

$pass = 0;
$fail = 0;
function check( $name, $cond ) {
	global $pass, $fail;
	if ( $cond ) { $pass++; echo "  ok   $name\n"; } else { $fail++; echo "  FAIL $name\n"; }
}

$tz       = wp_timezone();
$next_mon = ( new DateTimeImmutable( 'next monday', $tz ) )->format( 'Y-m-d' );
$next_sun = ( new DateTimeImmutable( 'next sunday', $tz ) )->format( 'Y-m-d' );
$GLOBALS['opts']['slotwise_settings'] = array( 'days' => array( 1, 2, 3, 4, 5, 6 ), 'open' => '09:00', 'close' => '12:00', 'interval' => 30, 'buffer' => 0, 'max_days' => 60, 'notify' => 'a@b.c' );

check( 'to_min and to_hm round-trip', Slotwise_Availability::to_hm( Slotwise_Availability::to_min( '09:45' ) ) === '09:45' );
check( 'touching ranges do not overlap', ! Slotwise_Availability::overlaps( 540, 570, 570, 600 ) );
check( 'nested range overlaps', Slotwise_Availability::overlaps( 540, 600, 550, 560 ) );

$s = Slotwise_Availability::slots( $next_mon, 60 );
check( 'empty morning gives 5 one-hour starts (9:00 to 11:00)', $s === array( '09:00', '09:30', '10:00', '10:30', '11:00' ) );

$GLOBALS['bookings'][1] = array( '_sw_date' => $next_mon, '_sw_start' => 600, '_sw_end' => 630 );
$s = Slotwise_Availability::slots( $next_mon, 60 );
check( 'booking 10:00-10:30 blocks overlapping 60-minute starts', $s === array( '09:00', '10:30', '11:00' ) );

$GLOBALS['opts']['slotwise_settings']['buffer'] = 15;
$s = Slotwise_Availability::slots( $next_mon, 30 );
check( '15-minute buffer after 10:30 end removes the 10:30 start', ! in_array( '10:30', $s, true ) && in_array( '11:00', $s, true ) );

check( 'closed day (Sunday) is refused', is_wp_error( Slotwise_Availability::slots( $next_sun, 30 ) ) && Slotwise_Availability::slots( $next_sun, 30 )->code === 'sw_closed' );
check( 'past date is refused', Slotwise_Availability::slots( '2020-01-06', 30 )->code === 'sw_past' );
check( 'malformed date is refused', Slotwise_Availability::slots( '2026-02-31', 30 )->code === 'sw_bad_date' );
check( 'date beyond booking window is refused', Slotwise_Availability::slots( ( new DateTimeImmutable( '+400 days', $tz ) )->format( 'Y-m-d' ), 30 )->code === 'sw_far' );
check( 'service longer than opening hours has no slots', Slotwise_Availability::slots( $next_mon, 240 ) === array() );

echo "\n$pass passed, $fail failed\n";
exit( $fail ? 1 : 0 );
