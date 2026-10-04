<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$im = isset( $settings ) && is_array( $settings ) ? $settings : IM_Settings::all();
$im_fmt   = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
$im_start = IM_Settings::to_timestamp( $im['start_datetime'] );
$im_end   = IM_Settings::to_timestamp( $im['end_datetime'] );
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html( $im['title'] ); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php wp_head(); ?>
</head>
<body <?php body_class( 'im-maintenance-page' ); ?>>
<?php wp_body_open(); ?>
<main class="im-maintenance-wrapper">
  <article class="im-maintenance-card" aria-live="polite">
    <div class="im-accent-line"></div>
    <?php if ( ! empty( $im['logo_url'] ) ) : ?>
    <header class="im-branding">
      <img src="<?php echo esc_url( $im['logo_url'] ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" class="im-logo">
    </header>
    <?php endif; ?>
    <div class="im-status-badge">
      <span class="im-badge-dot"></span>
      <span><?php echo esc_html__( 'Maintenance Mode', 'icontec-maintenance' ); ?></span>
    </div>
    <h1 class="im-title"><?php echo esc_html( $im['title'] ); ?></h1>
    <p class="im-message"><?php echo wp_kses_post( nl2br( esc_html( $im['message'] ) ) ); ?></p>
    <?php if ( ! empty( $im['show_dates'] ) && ( $im_start || $im_end ) ) : ?>
    <div class="im-dates">
      <?php if ( $im_start ) : ?>
        <p class="im-date-item">
          <strong><?php echo esc_html__( 'Start:', 'icontec-maintenance' ); ?></strong>
          <?php echo esc_html( wp_date( $im_fmt, $im_start ) ); ?>
        </p>
      <?php endif; ?>
      <?php if ( $im_end ) : ?>
        <p class="im-date-item">
          <strong><?php echo esc_html__( 'Expected return:', 'icontec-maintenance' ); ?></strong>
          <?php echo esc_html( wp_date( $im_fmt, $im_end ) ); ?>
        </p>
      <?php endif; ?>
    </div>
    <?php endif; ?>
    <div class="im-animation" aria-hidden="true">
      <div class="im-spinner"></div>
    </div>
  </article>
</main>
<?php wp_footer(); ?>
</body>
</html>
