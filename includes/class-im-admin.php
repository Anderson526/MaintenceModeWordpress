<?php
/**
 * Admin panel: top-level menu "Ventana de Mantenimiento" with tabs
 * (Appearance, Roles, Scheduling, Donations) plus a manual toggle.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IM_Admin {

	const MENU_SLUG = 'im-maintenance';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_im_toggle_maintenance', array( __CLASS__, 'handle_toggle' ) );
		add_action( 'admin_bar_menu', array( __CLASS__, 'admin_bar_notice' ), 100 );
	}

	public static function add_menu() {
		add_menu_page(
			__( 'Maintenance Window', 'icontec-maintenance' ),
			__( 'Maintenance Window', 'icontec-maintenance' ),
			'manage_options',
			self::MENU_SLUG,
			array( __CLASS__, 'render_page' ),
			'dashicons-hammer',
			80
		);

		$tabs = self::tabs();
		foreach ( $tabs as $tab => $label ) {
			// First tab reuses the parent slug so it replaces the duplicate entry.
			$slug = ( 'appearance' === $tab ) ? self::MENU_SLUG : self::MENU_SLUG . '-' . $tab;
			add_submenu_page(
				self::MENU_SLUG,
				$label,
				$label,
				'manage_options',
				$slug,
				array( __CLASS__, 'render_page' )
			);
		}
	}

	/**
	 * @return array tab-slug => label
	 */
	public static function tabs() {
		return array(
			'appearance' => __( 'Appearance', 'icontec-maintenance' ),
			'roles'      => __( 'Roles', 'icontec-maintenance' ),
			'schedule'   => __( 'Scheduling', 'icontec-maintenance' ),
			'donations'  => __( 'Donations', 'icontec-maintenance' ),
		);
	}

	public static function register_settings() {
		register_setting(
			'im_settings_group',
			IM_Settings::OPTION,
			array( 'sanitize_callback' => array( 'IM_Settings', 'sanitize' ) )
		);
	}

	public static function assets( $hook ) {
		if ( strpos( $hook, self::MENU_SLUG ) === false ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'im-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css', array(), '5.3.0' );
		wp_enqueue_style( 'im-admin-css', IM_PLUGIN_URL . 'assets/css/admin.css', array(), IM_VERSION );
		wp_enqueue_script( 'im-admin-js', IM_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery', 'wp-color-picker' ), IM_VERSION, true );
		wp_localize_script(
			'im-admin-js',
			'imAdmin',
			array(
				'chooseImage' => __( 'Choose image', 'icontec-maintenance' ),
				'useImage'    => __( 'Use this image', 'icontec-maintenance' ),
			)
		);
	}

	/**
	 * Handle the manual ON/OFF toggle (admin-post, nonce protected).
	 */
	public static function handle_toggle() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'icontec-maintenance' ) );
		}
		check_admin_referer( 'im_toggle_maintenance' );

		$enabled = IM_Settings::get( 'enabled' ) ? 0 : 1;
		IM_Settings::set( 'enabled', $enabled );

		wp_safe_redirect( add_query_arg( array( 'page' => self::MENU_SLUG, 'im-toggled' => $enabled ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Red badge on the admin bar while maintenance is active.
	 *
	 * @param WP_Admin_Bar $bar Admin bar instance.
	 */
	public static function admin_bar_notice( $bar ) {
		if ( ! current_user_can( 'manage_options' ) || ! IM_Settings::is_maintenance_active() ) {
			return;
		}
		$bar->add_node(
			array(
				'id'    => 'im-maintenance-active',
				'title' => '<span style="background:#d63638;color:#fff;padding:2px 8px;border-radius:3px;">' . esc_html__( 'Maintenance ON', 'icontec-maintenance' ) . '</span>',
				'href'  => admin_url( 'admin.php?page=' . self::MENU_SLUG ),
			)
		);
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$tabs        = self::tabs();
		$current_tab = 'appearance';
		// Tab comes from the submenu slug (im-maintenance-roles) or ?tab= param.
		$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 0 === strpos( $page, self::MENU_SLUG . '-' ) ) {
			$current_tab = substr( $page, strlen( self::MENU_SLUG ) + 1 );
		}
		if ( isset( $_GET['tab'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$current_tab = sanitize_key( $_GET['tab'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		if ( ! isset( $tabs[ $current_tab ] ) ) {
			$current_tab = 'appearance';
		}
		$s      = IM_Settings::all();
		$active = IM_Settings::is_maintenance_active();
		?>
		<div class="wrap im-admin-wrap">
			<h1><span class="dashicons dashicons-hammer"></span> <?php esc_html_e( 'Maintenance Window', 'icontec-maintenance' ); ?></h1>

			<?php if ( isset( $_GET['im-toggled'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible"><p>
					<?php echo $_GET['im-toggled'] ? esc_html__( 'Maintenance mode activated.', 'icontec-maintenance' ) : esc_html__( 'Maintenance mode deactivated.', 'icontec-maintenance' ); ?>
				</p></div>
			<?php endif; ?>

			<div class="im-status-panel <?php echo $active ? 'im-status-on' : 'im-status-off'; ?>">
				<div class="im-status-info">
					<span class="im-status-dot"></span>
					<strong>
						<?php echo $active ? esc_html__( 'Maintenance mode is ACTIVE', 'icontec-maintenance' ) : esc_html__( 'Maintenance mode is inactive', 'icontec-maintenance' ); ?>
					</strong>
				</div>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="im_toggle_maintenance">
					<?php wp_nonce_field( 'im_toggle_maintenance' ); ?>
					<button type="submit" class="button <?php echo $s['enabled'] ? 'button-secondary' : 'button-primary'; ?>">
						<?php echo $s['enabled'] ? esc_html__( 'Deactivate now', 'icontec-maintenance' ) : esc_html__( 'Activate now', 'icontec-maintenance' ); ?>
					</button>
				</form>
			</div>

			<nav class="nav-tab-wrapper">
				<?php foreach ( $tabs as $tab => $label ) : ?>
					<?php $tab_slug = ( 'appearance' === $tab ) ? self::MENU_SLUG : self::MENU_SLUG . '-' . $tab; ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $tab_slug ) ); ?>"
					   class="nav-tab <?php echo $current_tab === $tab ? 'nav-tab-active' : ''; ?>">
						<?php echo esc_html( $label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<div class="im-tab-content">
				<?php
				if ( 'donations' === $current_tab ) {
					self::render_donations_tab( $s );
				} else {
					?>
					<form method="post" action="options.php">
						<?php settings_fields( 'im_settings_group' ); ?>
						<?php
						switch ( $current_tab ) {
							case 'roles':
								self::render_roles_tab( $s );
								break;
							case 'schedule':
								self::render_schedule_tab( $s );
								break;
							default:
								self::render_appearance_tab( $s );
						}
						submit_button();
						?>
					</form>
					<?php
				}
				?>
			</div>
		</div>
		<?php
	}

	/* ---------------------------------------------------------------- */

	private static function render_appearance_tab( $s ) {
		$opt = IM_Settings::OPTION;
		?>
		<h2><?php esc_html_e( 'Appearance of the maintenance page', 'icontec-maintenance' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="im-title"><?php esc_html_e( 'Title', 'icontec-maintenance' ); ?></label></th>
				<td><input type="text" id="im-title" class="regular-text" name="<?php echo esc_attr( $opt ); ?>[title]" value="<?php echo esc_attr( $s['title'] ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="im-message"><?php esc_html_e( 'Message', 'icontec-maintenance' ); ?></label></th>
				<td>
					<textarea id="im-message" rows="5" class="large-text" name="<?php echo esc_attr( $opt ); ?>[message]"><?php echo esc_textarea( $s['message'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Message shown to visitors during maintenance.', 'icontec-maintenance' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Colors', 'icontec-maintenance' ); ?></th>
				<td class="im-colors">
					<label><?php esc_html_e( 'Background', 'icontec-maintenance' ); ?>
						<input type="text" class="im-color-field" name="<?php echo esc_attr( $opt ); ?>[bg_color]" value="<?php echo esc_attr( $s['bg_color'] ); ?>">
					</label>
					<label><?php esc_html_e( 'Text', 'icontec-maintenance' ); ?>
						<input type="text" class="im-color-field" name="<?php echo esc_attr( $opt ); ?>[text_color]" value="<?php echo esc_attr( $s['text_color'] ); ?>">
					</label>
					<label><?php esc_html_e( 'Accent / buttons', 'icontec-maintenance' ); ?>
						<input type="text" class="im-color-field" name="<?php echo esc_attr( $opt ); ?>[accent_color]" value="<?php echo esc_attr( $s['accent_color'] ); ?>">
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="im-logo"><?php esc_html_e( 'Corporate logo', 'icontec-maintenance' ); ?></label></th>
				<td>
					<input type="url" id="im-logo" class="regular-text im-media-url" name="<?php echo esc_attr( $opt ); ?>[logo_url]" value="<?php echo esc_attr( $s['logo_url'] ); ?>">
					<button type="button" class="button im-media-btn" data-target="#im-logo"><?php esc_html_e( 'Select from media library', 'icontec-maintenance' ); ?></button>
					<?php if ( $s['logo_url'] ) : ?>
						<p><img src="<?php echo esc_url( $s['logo_url'] ); ?>" alt="" style="max-height:60px;"></p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="im-bg-image"><?php esc_html_e( 'Background image (optional)', 'icontec-maintenance' ); ?></label></th>
				<td>
					<input type="url" id="im-bg-image" class="regular-text im-media-url" name="<?php echo esc_attr( $opt ); ?>[bg_image_url]" value="<?php echo esc_attr( $s['bg_image_url'] ); ?>">
					<button type="button" class="button im-media-btn" data-target="#im-bg-image"><?php esc_html_e( 'Select from media library', 'icontec-maintenance' ); ?></button>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Maintenance dates', 'icontec-maintenance' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( $opt ); ?>[show_dates]" value="1" <?php checked( 1, $s['show_dates'] ); ?>>
						<?php esc_html_e( 'Show scheduled start/end dates to visitors on the maintenance page.', 'icontec-maintenance' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="im-language"><?php esc_html_e( 'Plugin language', 'icontec-maintenance' ); ?></label></th>
				<td>
					<select id="im-language" name="<?php echo esc_attr( $opt ); ?>[language]">
						<option value="" <?php selected( '', $s['language'] ); ?>><?php esc_html_e( 'Site default', 'icontec-maintenance' ); ?></option>
						<option value="es_ES" <?php selected( 'es_ES', $s['language'] ); ?>>Español</option>
						<option value="en_US" <?php selected( 'en_US', $s['language'] ); ?>>English</option>
					</select>
					<p class="description"><?php esc_html_e( 'Language used on the maintenance page and plugin texts.', 'icontec-maintenance' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	private static function render_roles_tab( $s ) {
		$opt   = IM_Settings::OPTION;
		$roles = wp_roles()->roles;
		?>
		<h2><?php esc_html_e( 'Access control by role', 'icontec-maintenance' ); ?></h2>
		<p><?php esc_html_e( 'Users with the selected roles will be able to keep browsing the site while maintenance mode is active. Everyone else will see the maintenance page.', 'icontec-maintenance' ); ?></p>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Allowed roles', 'icontec-maintenance' ); ?></th>
				<td>
					<fieldset class="im-roles-list">
						<?php foreach ( $roles as $slug => $role ) : ?>
							<label class="im-role-item">
								<input type="checkbox"
									name="<?php echo esc_attr( $opt ); ?>[allowed_roles][]"
									value="<?php echo esc_attr( $slug ); ?>"
									<?php checked( in_array( $slug, (array) $s['allowed_roles'], true ) ); ?>
									<?php disabled( 'administrator' === $slug ); ?>>
								<?php echo esc_html( translate_user_role( $role['name'] ) ); ?>
								<?php if ( 'administrator' === $slug ) : ?>
									<em>(<?php esc_html_e( 'always allowed', 'icontec-maintenance' ); ?>)</em>
								<?php endif; ?>
							</label>
						<?php endforeach; ?>
						<?php // Administrators always bypass; keep them stored. ?>
						<input type="hidden" name="<?php echo esc_attr( $opt ); ?>[allowed_roles][]" value="administrator">
					</fieldset>
				</td>
			</tr>
		</table>
		<?php
	}

	private static function render_schedule_tab( $s ) {
		$opt  = IM_Settings::OPTION;
		$next = IM_Cron::next_events();
		$fmt  = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		?>
		<h2><?php esc_html_e( 'Automatic scheduling (cron jobs)', 'icontec-maintenance' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable scheduling', 'icontec-maintenance' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( $opt ); ?>[schedule_enabled]" value="1" <?php checked( 1, $s['schedule_enabled'] ); ?>>
						<?php esc_html_e( 'Automatically activate and deactivate maintenance mode at the dates below.', 'icontec-maintenance' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="im-start"><?php esc_html_e( 'Maintenance start', 'icontec-maintenance' ); ?></label></th>
				<td>
					<input type="datetime-local" id="im-start" name="<?php echo esc_attr( $opt ); ?>[start_datetime]" value="<?php echo esc_attr( $s['start_datetime'] ); ?>">
					<?php if ( $next['start'] ) : ?>
						<p class="description"><?php printf( /* translators: %s: date */ esc_html__( 'Scheduled: %s', 'icontec-maintenance' ), esc_html( wp_date( $fmt, $next['start'] ) ) ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="im-end"><?php esc_html_e( 'Maintenance end', 'icontec-maintenance' ); ?></label></th>
				<td>
					<input type="datetime-local" id="im-end" name="<?php echo esc_attr( $opt ); ?>[end_datetime]" value="<?php echo esc_attr( $s['end_datetime'] ); ?>">
					<?php if ( $next['end'] ) : ?>
						<p class="description"><?php printf( /* translators: %s: date */ esc_html__( 'Scheduled: %s', 'icontec-maintenance' ), esc_html( wp_date( $fmt, $next['end'] ) ) ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Timezone', 'icontec-maintenance' ); ?></th>
				<td><code><?php echo esc_html( wp_timezone_string() ); ?></code>
					<p class="description"><?php esc_html_e( 'Dates use the site timezone configured in Settings → General.', 'icontec-maintenance' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	private static function render_donations_tab( $s ) {
		?>
		<style>
			.rm-donate-card {
				width: 100%;
				max-width: 100%;
				margin: 24px auto 0;
				padding: 32px 28px;
				border-radius: 18px;
				text-align: center;
				background: linear-gradient(180deg, #fffdf7 0%, #ffffff 100%);
				border: 1px solid rgba(233, 193, 98, 0.55);
				box-shadow: 0 12px 24px rgba(77, 59, 17, 0.08), 0 2px 8px rgba(77, 59, 17, 0.04);
				box-sizing: border-box;
			}
			.rm-donate-card h2 {
				margin: 12px 0 8px;
				font-size: 22px;
				line-height: 1.3;
				color: #1d2327;
			}
			.rm-donate-card p {
				margin: 0 0 18px;
				color: #50575e;
				font-size: 14px;
				line-height: 1.6;
			}
			.rm-coffee-emoji {
				display: inline-flex;
				align-items: center;
				justify-content: center;
				width: 64px;
				height: 64px;
				font-size: 38px;
				border-radius: 16px;
				background: rgba(255, 196, 57, 0.12);
				box-shadow: inset 0 0 0 1px rgba(255, 196, 57, 0.25);
				animation: rm-steam 2.4s ease-in-out infinite;
			}
			@keyframes rm-steam {
				0%, 100% { transform: translateY(0) rotate(0deg); }
				50% { transform: translateY(-4px) rotate(-4deg); }
			}
			.rm-donate-button {
				display: inline-flex;
				align-items: center;
				justify-content: center;
				gap: 10px;
				min-width: 220px;
				padding: 14px 28px;
				border: none;
				border-radius: 999px;
				background: linear-gradient(135deg, #ffd76a 0%, #f9b52a 100%);
				color: #3c2a00;
				font-size: 15px;
				font-weight: 800;
				text-decoration: none;
				letter-spacing: 0.01em;
				box-shadow: 0 10px 18px rgba(198, 136, 15, 0.28);
				transition: transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
			}
			.rm-donate-button:hover,
			.rm-donate-button:focus-visible {
				transform: translateY(-2px) scale(1.02);
				box-shadow: 0 16px 24px rgba(198, 136, 15, 0.35);
				filter: brightness(1.02);
				color: #3c2a00;
			}
			.rm-donate-button:active {
				transform: translateY(0) scale(0.99);
			}
			.rm-donate-button-coffee {
				display: inline-block;
				font-size: 18px;
			}
			.rm-donate-link-note {
				margin-top: 16px;
				font-size: 12px;
				color: #6b7280;
			}
			@media (max-width: 480px) {
				.rm-donate-card {
					padding: 24px 18px;
				}
				.rm-donate-button {
					width: 100%;
				}
			}
		</style>
		<div class="im-donations-hero">
			<div class="rm-card rm-donate-card">
				<div class="rm-coffee-emoji" aria-hidden="true">☕</div>
				<h2><?php esc_html_e( '¿Te resulta útil Maintenance Window?', 'icontec-maintenance' ); ?></h2>
				<p><?php esc_html_e( 'Tu apoyo ayuda a mantener y mejorar este plugin. Puedes hacer una donación directa desde PayPal.', 'icontec-maintenance' ); ?></p>
				<a href="<?php echo esc_url( 'https://paypal.me/AndersonChila?locale.x=en_US&country.x=CO' ); ?>" class="rm-donate-button" target="_blank" rel="noopener noreferrer">
					<span class="rm-donate-button-coffee" aria-hidden="true">☕</span>
					<?php esc_html_e( 'Donar con PayPal', 'icontec-maintenance' ); ?>
				</a>
				<p class="rm-donate-link-note"><?php esc_html_e( 'Se abrirá PayPal en una nueva ventana.', 'icontec-maintenance' ); ?></p>
			</div>
		</div>

		<?php
	}
}
