<?php
/*
Plugin Name: Icontec Maintenance Mode
Plugin URI: https://anderson526.github.io/portfolio-profesional/
Description: Ventana de mantenimiento personalizable con control por roles, programación con cron jobs, donaciones vía PayPal y soporte multilenguaje (ES/EN).
Version: 2.0.0
Author: Anderson D Chila P
Text Domain: window- maintenance
Domain Path: /languages
Requires at least: 5.8
Requires PHP: 7.2
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'IM_VERSION', '2.0.0' );
if ( ! defined( 'IM_PLUGIN_DIR' ) ) {
	define( 'IM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
	define( 'IM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

require_once IM_PLUGIN_DIR . 'includes/class-im-settings.php';
require_once IM_PLUGIN_DIR . 'includes/class-im-cron.php';
require_once IM_PLUGIN_DIR . 'includes/class-im-admin.php';
require_once IM_PLUGIN_DIR . 'includes/class-im-frontend.php';

class IM_Maintenance {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'load_textdomain' ) );

		IM_Cron::init();
		IM_Frontend::init();
		if ( is_admin() ) {
			IM_Admin::init();
		}

		register_activation_hook( __FILE__, array( __CLASS__, 'activate' ) );
		register_deactivation_hook( __FILE__, array( __CLASS__, 'deactivate' ) );
	}

	public static function load_textdomain() {
		load_plugin_textdomain( 'icontec-maintenance', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	public static function activate() {
		// Seed settings and migrate legacy v1 options if present.
		$settings = IM_Settings::all();

		$legacy_enabled = get_option( 'im_maintenance_enabled', null );
		$legacy_message = get_option( 'im_maintenance_message', null );
		if ( null !== $legacy_enabled ) {
			$settings['enabled'] = (int) $legacy_enabled;
			delete_option( 'im_maintenance_enabled' );
		}
		if ( null !== $legacy_message && '' !== $legacy_message ) {
			$settings['message'] = sanitize_textarea_field( $legacy_message );
			delete_option( 'im_maintenance_message' );
		}
		update_option( IM_Settings::OPTION, $settings );

		// Dedicated role that bypasses maintenance mode.
		if ( ! get_role( 'maintenance-mode' ) ) {
			add_role( 'maintenance-mode', __( 'Maintenance Mode', 'icontec-maintenance' ), array( 'read' => true ) );
		}
	}

	public static function deactivate() {
		IM_Cron::clear();
	}
}

IM_Maintenance::init();
