<?php
/**
 * Sprint B3 — Modelo G1 finance + RSS (estrato.cc).
 *
 * - Sync taxonomia v2 + colunas Radar B3 / Painel Selic
 * - PressGrid 1:1 com 7 editorias
 * - Hubs /tudo-sobre/ + nav Sprint 5
 * - Curadoria RSS pipeline_primary
 *
 * Uso: wp eval-file setup-sprint-b3-finance-g1.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$preset = 'brasil-financeiro';
$stats  = array( 'preset' => $preset );

if ( function_exists( 'estrato_rss_sync_portal_taxonomy' ) ) {
	$stats['sync'] = estrato_rss_sync_portal_taxonomy( $preset );
}

$s8 = dirname( __FILE__ ) . '/setup-sprint8-taxonomy.php';
$s5 = dirname( __FILE__ ) . '/setup-sprint5-nav.php';

if ( is_readable( $s5 ) ) {
	require $s5;
	$stats['sprint5'] = true;
}

if ( is_readable( $s8 ) ) {
	require $s8;
	$stats['sprint8'] = true;
}

if ( function_exists( 'estrato_rss_apply_content_mode' ) ) {
	$stats['rss_mode'] = estrato_rss_apply_content_mode( 'pipeline_primary' );
}

if ( function_exists( 'estrato_rss_apply_curation' ) ) {
	$stats['curation'] = estrato_rss_apply_curation( false );
}

$stats['import_nodes'] = count( get_option( 'estrato_rss_import_matrix', array() ) );
$stats['pressgrid']    = function_exists( 'estrato_regression_pressgrid_finance_sections' )
	? estrato_regression_pressgrid_finance_sections()
	: -1;

WP_CLI::success( wp_json_encode( $stats, JSON_UNESCAPED_UNICODE ) );
