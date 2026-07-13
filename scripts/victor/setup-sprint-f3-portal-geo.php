<?php
/**
 * Sprint F3 — llms-full por portal + GEO/Speakable (S22).
 *
 * Uso: ESTRATO_PORTAL=estrato-mind wp eval-file setup-sprint-f3-portal-geo.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$portal = getenv( 'ESTRATO_PORTAL' ) ?: ( function_exists( 'estrato_nav_current_portal_id' ) ? estrato_nav_current_portal_id() : '' );

if ( ! function_exists( 'estrato_aeo_deploy_static_files' ) ) {
	WP_CLI::error( 'estrato_aeo_deploy_static_files ausente' );
}

$deployed = estrato_aeo_deploy_static_files();
foreach ( $deployed as $file => $ok ) {
	WP_CLI::log( ( $ok ? 'OK' : 'FAIL' ) . " $file" );
}

if ( function_exists( 'estrato_aeo_ensure_timezone' ) ) {
	estrato_aeo_ensure_timezone();
}

$hub_faq = function_exists( 'estrato_regression_hub_faq_schema' )
	? estrato_regression_hub_faq_schema()
	: -1;

$llms_ok = is_readable( ABSPATH . 'llms.txt' ) && is_readable( ABSPATH . 'llms-full.txt' );

WP_CLI::success(
	wp_json_encode(
		array(
			'portal'   => $portal,
			'llms'     => $llms_ok,
			'hub_faq'  => $hub_faq,
			'deployed' => $deployed,
		),
		JSON_UNESCAPED_UNICODE
	)
);
