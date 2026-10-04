<?php
/**
 * WP Cron integration: automatically starts and ends the
 * maintenance window at the configured dates.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IM_Cron {

	const START_HOOK = 'im_maintenance_start_event';
	const END_HOOK   = 'im_maintenance_end_event';

	public static function init() {
		add_action( self::START_HOOK, array( __CLASS__, 'start_maintenance' ) );
		add_action( self::END_HOOK, array( __CLASS__, 'end_maintenance' ) );
		// Re-schedule whenever settings are saved.
		add_action( 'update_option_' . IM_Settings::OPTION, array( __CLASS__, 'reschedule' ), 10, 0 );
	}

	/**
	 * Cron callback: turn maintenance ON.
	 */
	public static function start_maintenance() {
		IM_Settings::set( 'enabled', 1 );
	}

	/**
	 * Cron callback: turn maintenance OFF.
	 */
	public static function end_maintenance() {
		IM_Settings::set( 'enabled', 0 );
	}

	/**
	 * Clear and (re)create the single events based on current settings.
	 */
	public static function reschedule() {
		wp_clear_scheduled_hook( self::START_HOOK );
		wp_clear_scheduled_hook( self::END_HOOK );

		$s = IM_Settings::all();
		if ( empty( $s['schedule_enabled'] ) ) {
			return;
		}

		$now   = time();
		$start = IM_Settings::to_timestamp( $s['start_datetime'] );
		$end   = IM_Settings::to_timestamp( $s['end_datetime'] );

		if ( $start && $start > $now ) {
			wp_schedule_single_event( $start, self::START_HOOK );
		}
		if ( $end && $end > $now ) {
			wp_schedule_single_event( $end, self::END_HOOK );
		}
	}

	/**
	 * Remove all scheduled events (used on deactivation).
	 */
	public static function clear() {
		wp_clear_scheduled_hook( self::START_HOOK );
		wp_clear_scheduled_hook( self::END_HOOK );
	}

	/**
	 * Next scheduled timestamps, for display in the admin.
	 *
	 * @return array { start: int|false, end: int|false }
	 */
	public static function next_events() {
		return array(
			'start' => wp_next_scheduled( self::START_HOOK ),
			'end'   => wp_next_scheduled( self::END_HOOK ),
		);
	}
}
