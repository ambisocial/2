<?php
/**
 * Sprint G1 — Google News / Publisher Center readiness (S23).
 *
 * Uso: ESTRATO_PORTAL=estrato-mind wp eval-file setup-sprint-g1-portal-google-news.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$portal = getenv( 'ESTRATO_PORTAL' ) ?: ( function_exists( 'estrato_nav_current_portal_id' ) ? estrato_nav_current_portal_id() : '' );

if ( ! function_exists( 'estrato_google_news_save_readiness' ) ) {
	WP_CLI::error( 'google-news.php ausente no plugin' );
}

$payload = estrato_google_news_save_readiness();

$log_dir = WP_CONTENT_DIR . '/uploads/estrato-logs';
if ( ! is_dir( $log_dir ) ) {
	wp_mkdir_p( $log_dir );
}
$file = $log_dir . '/google-news-' . sanitize_key( $portal ) . '.json';
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
file_put_contents( $file, wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );

if ( function_exists( 'estrato_aeo_ping_sitemaps_cron' ) ) {
	estrato_aeo_ping_sitemaps_cron();
}

WP_CLI::success(
	wp_json_encode(
		array(
			'portal'     => $portal,
			'recent_48h' => $payload['recent_48h'],
			'ready'      => $payload['ready'],
			'checklist'  => $file,
		),
		JSON_UNESCAPED_UNICODE
	)
);
