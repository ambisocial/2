<?php
/**
 * Sprint 6 — AEO/GEO: llms.txt, IndexNow, timezone, FAQ, cron.
 *
 * Uso: wp eval-file setup-sprint6-aeo.php
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

if ( function_exists( 'estrato_aeo_deploy_static_files' ) ) {
	$deployed = estrato_aeo_deploy_static_files();
	foreach ( $deployed as $file => $ok ) {
		WP_CLI::log( ( $ok ? 'OK' : 'FAIL' ) . " $file" );
	}
}

if ( function_exists( 'estrato_aeo_ensure_timezone' ) ) {
	estrato_aeo_ensure_timezone();
	WP_CLI::log( 'Timezone: ' . get_option( 'timezone_string' ) );
}

if ( function_exists( 'estrato_aeo_schedule_cron' ) ) {
	estrato_aeo_schedule_cron();
	WP_CLI::log( 'Cron news-sitemap ping agendado' );
}

// Executa ping inicial
if ( function_exists( 'estrato_aeo_ping_sitemaps_cron' ) ) {
	estrato_aeo_ping_sitemaps_cron();
	WP_CLI::log( 'Sitemaps pingados (Google/Bing)' );
}

$faqs = function_exists( 'estrato_regression_hub_faq_schema' ) ? estrato_regression_hub_faq_schema() : 0;
WP_CLI::log( "Hubs com FAQPage schema: $faqs" );

WP_CLI::success( 'Sprint 6 AEO/GEO configurado.' );
