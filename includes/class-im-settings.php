<?php
/**
 * Settings storage and sanitization for Icontec Maintenance Mode.
 *
 * All plugin configuration lives in a single option (array) managed
 * through the WordPress Settings API.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IM_Settings {

	const OPTION = 'im_settings';

	/**
	 * Default values for every setting.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			// General state.
			'enabled'          => 0,
			// Appearance.
			'title'            => __( 'Site under maintenance', 'icontec-maintenance' ),
			'message'          => __( 'We are performing scheduled maintenance. We will be back soon.', 'icontec-maintenance' ),
			'bg_color'         => '#f4f6f9',
			'text_color'       => '#1a1a1a',
			'accent_color'     => '#0085CA',
			'logo_url'         => IM_PLUGIN_URL . 'assets/img/logoIcontec.jpg',
			'bg_image_url'     => '',
			'show_dates'       => 1,
			'language'         => '', // '' = site default, 'es_ES', 'en_US'.
			// Roles allowed to browse the site during maintenance.
			'allowed_roles'    => array( 'administrator', 'maintenance-mode' ),
			// Scheduling (WP Cron).
			'schedule_enabled' => 0,
			'start_datetime'   => '', // Local time, format Y-m-d\TH:i.
			'end_datetime'     => '',
			// Donations (PayPal REST / JS SDK).
			'paypal_client_id' => '',
			'paypal_currency'  => 'USD',
		);
	}

	/**
	 * Get all settings merged with defaults.
	 *
	 * @return array
	 */
	public static function all() {
		$saved = get_option( self::OPTION, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return wp_parse_args( $saved, self::defaults() );
	}

	/**
	 * Get a single setting.
	 *
	 * @param string $key Setting key.
	 * @return mixed
	 */
	public static function get( $key ) {
		$all = self::all();
		return isset( $all[ $key ] ) ? $all[ $key ] : null;
	}

	/**
	 * Update a single setting.
	 *
	 * @param string $key   Setting key.
	 * @param mixed  $value New value.
	 */
	public static function set( $key, $value ) {
		$all         = self::all();
		$all[ $key ] = $value;
		update_option( self::OPTION, $all );
	}

	/**
	 * Sanitize callback for the Settings API.
	 *
	 * Each admin tab submits only its own subset of fields, so the
	 * incoming values are merged over the existing option.
	 *
	 * @param array $input Raw input from the form.
	 * @return array Sanitized, complete settings array.
	 */
	public static function sanitize( $input ) {
		$out = self::all();

		if ( ! is_array( $input ) ) {
			return $out;
		}

		foreach ( $input as $key => $value ) {
			switch ( $key ) {
				case 'enabled':
				case 'schedule_enabled':
				case 'show_dates':
					$out[ $key ] = $value ? 1 : 0;
					break;

				case 'title':
				case 'paypal_client_id':
					$out[ $key ] = sanitize_text_field( $value );
					break;

				case 'message':
					$out[ $key ] = sanitize_textarea_field( $value );
					break;

				case 'bg_color':
				case 'text_color':
				case 'accent_color':
					$color       = sanitize_hex_color( $value );
					$out[ $key ] = $color ? $color : $out[ $key ];
					break;

				case 'logo_url':
				case 'bg_image_url':
					$out[ $key ] = esc_url_raw( $value );
					break;

				case 'start_datetime':
				case 'end_datetime':
					$out[ $key ] = preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', (string) $value ) ? $value : '';
					break;

				case 'allowed_roles':
					$valid       = array_keys( wp_roles()->roles );
					$out[ $key ] = array_values( array_intersect( array_map( 'sanitize_key', (array) $value ), $valid ) );
					break;

				case 'language':
					$out[ $key ] = in_array( $value, array( '', 'es_ES', 'en_US' ), true ) ? $value : '';
					break;

				case 'paypal_currency':
					$out[ $key ] = in_array( $value, array( 'USD', 'EUR', 'MXN', 'COP' ), true ) ? $value : 'USD';
					break;
			}
		}

		return $out;
	}

	/**
	 * Convert a local "Y-m-d\TH:i" value to a UTC timestamp.
	 *
	 * @param string $local_datetime Datetime string in the site timezone.
	 * @return int|false UTC timestamp or false on failure.
	 */
	public static function to_timestamp( $local_datetime ) {
		if ( empty( $local_datetime ) ) {
			return false;
		}
		try {
			$dt = new DateTimeImmutable( $local_datetime, wp_timezone() );
			return $dt->getTimestamp();
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Whether the maintenance window is currently active
	 * (manual switch OR inside the scheduled window).
	 *
	 * @return bool
	 */
	public static function is_maintenance_active() {
		$s = self::all();

		if ( ! empty( $s['enabled'] ) ) {
			return true;
		}

		if ( ! empty( $s['schedule_enabled'] ) ) {
			$now   = time();
			$start = self::to_timestamp( $s['start_datetime'] );
			$end   = self::to_timestamp( $s['end_datetime'] );

			if ( $start && $now >= $start && ( ! $end || $now < $end ) ) {
				return true;
			}
		}

		return false;
	}
}
