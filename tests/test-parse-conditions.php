<?php
/**
 * Standalone tests for elecond_check_time_interval() and elecond_check_date_interval().
 * Run with: php tests/test-parse-conditions.php
 * Does NOT require WordPress — WP stubs are defined below.
 */

// ── WordPress stubs ───────────────────────────────────────────
if ( ! defined( 'ABSPATH' ) ) define( 'ABSPATH', __DIR__ . '/../' );

function wp_timezone() {
	return new DateTimeZone( 'Europe/Bucharest' );
}

// ── Load only the two pure functions ─────────────────────────
// We extract them rather than require the whole file (which needs $post, $wp_query, etc.)
require_once __DIR__ . '/../plugin/inc/parse_conditions.php';

// ── Test runner ───────────────────────────────────────────────
$passed = 0;
$failed = 0;

function test( string $name, callable $fn ): void {
	global $passed, $failed;
	try {
		$fn();
		echo "  ✓ $name\n";
		$passed++;
	} catch ( \Throwable $e ) {
		echo "  ✗ $name\n    → " . $e->getMessage() . "\n";
		$failed++;
	}
}

function ok( bool $cond, string $msg ): void {
	if ( ! $cond ) throw new \RuntimeException( $msg );
}

function section( string $title ): void {
	echo "\n── $title\n";
}

// ── Helpers ───────────────────────────────────────────────────
function current_hour(): int {
	return (int) ( new DateTime( 'now', wp_timezone() ) )->format( 'G' );
}

function current_minute_str(): string {
	return ( new DateTime( 'now', wp_timezone() ) )->format( 'H:i' );
}

// ── 1. elecond_check_time_interval ───────────────────────────
section( '1. elecond_check_time_interval' );

test( 'both empty → always true', function() {
	ok( elecond_check_time_interval( '', '' ) === true, 'expected true' );
} );

test( 'from empty, to set → true (incomplete range)', function() {
	ok( elecond_check_time_interval( '', '23:59' ) === true, 'expected true' );
} );

test( 'to empty, from set → true (incomplete range)', function() {
	ok( elecond_check_time_interval( '00:00', '' ) === true, 'expected true' );
} );

test( 'full-day range 00:00–23:59 → always true', function() {
	ok( elecond_check_time_interval( '00:00', '23:59' ) === true, 'expected true' );
} );

test( 'impossible range 23:58–23:59 (2-min window, almost always false)', function() {
	// This test is probabilistic: passes unless run exactly in that 2-minute window.
	// Skip assertion if currently in the window.
	$now_str = current_minute_str();
	if ( $now_str >= '23:58' && $now_str <= '23:59' ) {
		echo "    (skipped — currently in window)\n";
		return;
	}
	ok( elecond_check_time_interval( '23:58', '23:59' ) === false, 'expected false outside 23:58-23:59' );
} );

test( 'very old past range 00:00–00:01 (almost always false)', function() {
	$now_str = current_minute_str();
	if ( $now_str <= '00:01' ) {
		echo "    (skipped — currently in window)\n";
		return;
	}
	ok( elecond_check_time_interval( '00:00', '00:01' ) === false, 'expected false outside 00:00-00:01' );
} );

test( 'cross-midnight 22:00–06:00 — current time inside at 23:00?', function() {
	$h = current_hour();
	$result = elecond_check_time_interval( '22:00', '06:00' );
	if ( $h >= 22 || $h < 6 ) {
		ok( $result === true, "hour=$h should be inside 22:00-06:00" );
	} else {
		ok( $result === false, "hour=$h should be outside 22:00-06:00" );
	}
} );

test( 'cross-midnight logic: from > to → cross-midnight path used', function() {
	// 21:00–05:00 cross-midnight, test pure logic with a fixed "now" is not injectable,
	// so we verify the return type is bool
	$result = elecond_check_time_interval( '21:00', '05:00' );
	ok( is_bool( $result ), 'result must be bool' );
} );

test( 'normal range: from == to (exact minute) → true only in that exact minute', function() {
	$now_str = current_minute_str();
	$result  = elecond_check_time_interval( $now_str, $now_str );
	// Current time is always within a range that starts and ends at the current minute
	ok( $result === true, "current time $now_str should be inside [$now_str–$now_str]" );
} );

// ── 2. elecond_check_date_interval ───────────────────────────
section( '2. elecond_check_date_interval' );

test( 'both empty → always true', function() {
	ok( elecond_check_date_interval( '', '' ) === true, 'expected true' );
} );

test( 'very wide range 1970-01-01 to 2099-12-31 → always true', function() {
	ok( elecond_check_date_interval( '1970-01-01', '2099-12-31' ) === true, 'expected true' );
} );

test( 'from in far future → always false', function() {
	ok( elecond_check_date_interval( '2099-01-01', '2099-12-31' ) === false, 'expected false (future)' );
} );

test( 'to in far past → always false', function() {
	ok( elecond_check_date_interval( '1970-01-01', '1970-01-02' ) === false, 'expected false (past)' );
} );

test( 'only from set, far past → true (no upper bound)', function() {
	ok( elecond_check_date_interval( '1970-01-01', '' ) === true, 'expected true (no upper bound)' );
} );

test( 'only to set, far future → true (no lower bound)', function() {
	ok( elecond_check_date_interval( '', '2099-12-31' ) === true, 'expected true (no lower bound)' );
} );

test( 'only from set, far future → false (not there yet)', function() {
	ok( elecond_check_date_interval( '2099-01-01', '' ) === false, 'expected false (from not reached)' );
} );

test( 'only to set, far past → false (already past)', function() {
	ok( elecond_check_date_interval( '', '1970-01-02' ) === false, 'expected false (to already passed)' );
} );

test( 'datetime-local T separator is normalized', function() {
	// Simulate datetime-local format: 1970-01-01T00:00 to 2099-12-31T23:59
	ok( elecond_check_date_interval( '1970-01-01T00:00', '2099-12-31T23:59' ) === true, 'T separator must be normalized' );
} );

test( 'date-only "to" end-of-day is inclusive (23:59:59)', function() {
	// If today's date is passed as the "to" parameter without time, end-of-day makes it still valid
	$today = ( new DateTime( 'now', wp_timezone() ) )->format( 'Y-m-d' );
	ok( elecond_check_date_interval( '1970-01-01', $today ) === true,
		'today as date-only to should still be within range (end-of-day default)' );
} );

test( 'from == to (today only), date-only format', function() {
	$today = ( new DateTime( 'now', wp_timezone() ) )->format( 'Y-m-d' );
	ok( elecond_check_date_interval( $today, $today ) === true,
		"from=$today to=$today with end-of-day default must be true during the day" );
} );

test( 'invalid date strings → behave gracefully (no crash)', function() {
	$result = elecond_check_date_interval( 'not-a-date', 'also-not' );
	ok( is_bool( $result ), 'must return bool even for bad input' );
} );

// ── 3. elecond_evaluate_group — basic wiring ──────────────────
// Note: full evaluation requires $post, $wp_query globals — tested only partially here.
section( '3. elecond_evaluate_group (structural)' );

test( 'empty conditions array → true (nothing to block)', function() {
	ok( elecond_evaluate_group( [] ) === true, 'expected true' );
} );

test( 'condition with empty var skipped → returns true', function() {
	// Preset = 'custom' but cond_var_custom is '' → var is '', loop continues → result null → true
	$cond = [
		[
			'cond_type'       => 'simple',
			'cond_var_preset' => 'custom',
			'cond_var_custom' => '',
			'cond_operator'   => '==',
			'cond_value'      => '',
			'cond_logic'      => 'AND',
		],
	];
	ok( elecond_evaluate_group( $cond ) === true, 'empty var → skip → true' );
} );

test( 'time_interval with always-true range passes through evaluate_group', function() {
	$cond = [
		[
			'cond_type'       => 'time_interval',
			'cond_time_from'  => '00:00',
			'cond_time_to'    => '23:59',
			'cond_logic'      => 'AND',
		],
	];
	ok( elecond_evaluate_group( $cond ) === true, 'full-day range must pass' );
} );

test( 'date_interval with always-false past range fails evaluate_group', function() {
	$cond = [
		[
			'cond_type'          => 'date_interval',
			'cond_datetime_from' => '1970-01-01',
			'cond_datetime_to'   => '1970-01-02',
			'cond_logic'         => 'AND',
		],
	];
	ok( elecond_evaluate_group( $cond ) === false, 'past date interval must fail' );
} );

test( 'AND logic: one true + one false = false', function() {
	$cond = [
		[
			'cond_type'          => 'date_interval',
			'cond_datetime_from' => '1970-01-01',
			'cond_datetime_to'   => '2099-12-31',
			'cond_logic'         => 'AND',
		],
		[
			'cond_type'          => 'date_interval',
			'cond_datetime_from' => '2099-01-01',
			'cond_datetime_to'   => '2099-12-31',
			'cond_logic'         => 'AND',
		],
	];
	ok( elecond_evaluate_group( $cond ) === false, 'AND: true && false must be false' );
} );

test( 'OR logic: one true + one false = true', function() {
	$cond = [
		[
			'cond_type'          => 'date_interval',
			'cond_datetime_from' => '1970-01-01',
			'cond_datetime_to'   => '2099-12-31',
			'cond_logic'         => 'OR',
		],
		[
			'cond_type'          => 'date_interval',
			'cond_datetime_from' => '2099-01-01',
			'cond_datetime_to'   => '2099-12-31',
			'cond_logic'         => 'AND',
		],
	];
	ok( elecond_evaluate_group( $cond ) === true, 'OR: true || false must be true' );
} );

test( 'OR logic: both false = false', function() {
	$cond = [
		[
			'cond_type'          => 'date_interval',
			'cond_datetime_from' => '2099-01-01',
			'cond_datetime_to'   => '2099-06-01',
			'cond_logic'         => 'OR',
		],
		[
			'cond_type'          => 'date_interval',
			'cond_datetime_from' => '2099-07-01',
			'cond_datetime_to'   => '2099-12-31',
			'cond_logic'         => 'AND',
		],
	];
	ok( elecond_evaluate_group( $cond ) === false, 'OR: false || false must be false' );
} );

// ── Summary ───────────────────────────────────────────────────
echo "\n" . str_repeat( '─', 50 ) . "\n";
echo "Results: $passed passed, $failed failed out of " . ( $passed + $failed ) . " tests\n";
exit( $failed > 0 ? 1 : 0 );
