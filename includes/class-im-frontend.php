<?php
/**
 * Frontend logic: blocks the site and shows the maintenance
 * template to users whose role is not in the allowed list.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IM_Frontend {

	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_show_maintenance' ), 0 );
		add_filter( 'plugin_locale', array( __CLASS__, 'filter_locale' ), 10, 2 );
	}

	/**
	 * Force the configured plugin language (es_ES / en_US) if set.
	 *
	 * @param string $locale Current locale.
	 * @param string $domain Text domain.
	 * @return string
	 */
	public static function filter_locale( $locale, $domain ) {
		if ( 'icontec-maintenance' !== $domain ) {
			return $locale;
		}
		$forced = IM_Settings::get( 'language' );
		return $forced ? $forced : $locale;
	}

	/**
	 * Whether the current user may browse the site during maintenance.
	 *
	 * @return bool
	 */
	public static function user_can_bypass() {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		$allowed = (array) IM_Settings::get( 'allowed_roles' );
		$user    = wp_get_current_user();

		foreach ( (array) $user->roles as $role ) {
			if ( in_array( $role, $allowed, true ) ) {
				return true;
			}
		}

		return false;
	}

	public static function maybe_show_maintenance() {
		if ( is_admin() ) {
			return;
		}
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}
		if ( isset( $_SERVER['REQUEST_URI'] ) && false !== strpos( $_SERVER['REQUEST_URI'], 'wp-login.php' ) ) {
			return;
		}
		if ( ! IM_Settings::is_maintenance_active() ) {
			return;
		}
		if ( self::user_can_bypass() ) {
			return;
		}

		$settings = IM_Settings::all();

		if ( ! headers_sent() ) {
			status_header( 503 );
			$end   = IM_Settings::to_timestamp( $settings['end_datetime'] );
			$retry = ( $end && $end > time() ) ? ( $end - time() ) : 3600;
			header( 'Retry-After: ' . (int) $retry );
			nocache_headers();
		}

		self::enqueue_assets();

		include IM_PLUGIN_DIR . 'template/maintenance-template.php';
		exit;
	}

	public static function enqueue_assets() {
		wp_enqueue_style( 'im-google-fonts', 'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap', array(), null );
		wp_enqueue_style( 'im-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css', array(), '5.3.0' );
		wp_enqueue_style( 'im-maintenance-css', IM_PLUGIN_URL . 'assets/css/maintenance.css', array( 'im-bootstrap' ), IM_VERSION );
		wp_add_inline_style( 'im-maintenance-css', self::inline_css() );

		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'im-maintenance-js', IM_PLUGIN_URL . 'assets/js/maintenance.js', array( 'jquery' ), IM_VERSION, true );
	}

	/**
	 * CSS variables generated from the appearance settings.
	 *
	 * @return string
	 */
	private static function inline_css() {
		$s   = IM_Settings::all();
		$css = sprintf(
			':root{--im-bg-gray:%1$s;--im-text-dark:%2$s;--im-primary-blue:%3$s;}',
			sanitize_hex_color( $s['bg_color'] ),
			sanitize_hex_color( $s['text_color'] ),
			sanitize_hex_color( $s['accent_color'] )
		);
		if ( ! empty( $s['bg_image_url'] ) ) {
			$css .= sprintf(
				'body.im-maintenance-page{background-image:url(%s);background-size:cover;background-position:center;}',
				esc_url( $s['bg_image_url'] )
			);
		}
		return $css;
	}
}
