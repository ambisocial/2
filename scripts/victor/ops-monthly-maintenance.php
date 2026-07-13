<?php
/**
 * Sprint 10 — Manutenção mensal: feeds + thin/mid posts + gate contínuo.
 *
 * Uso: wp eval-file ops-monthly-maintenance.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$portal = getenv( 'ESTRATO_PORTAL' ) ?: '';
if ( '' === $portal ) {
	$host      = (string) wp_parse_url( home_url(), PHP_URL_HOST );
	$by_domain = array(
		'estrato.cc'           => 'estrato-finance',
		'mente.estrato.cc'     => 'estrato-mind',
		'lifestyle.estrato.cc' => 'estrato-lifestyle',
		'science.estrato.cc'   => 'estrato-science',
		'sustain.estrato.cc'   => 'estrato-sustain',
		'culture.estrato.cc'   => 'estrato-culture',
	);
	$portal = $by_domain[ $host ] ?? 'estrato-finance';
}

$presets = array(
	'estrato-finance'   => 'brasil-financeiro',
	'estrato-mind'      => 'brasil-mind',
	'estrato-lifestyle' => 'brasil-lifestyle',
	'estrato-science'   => 'brasil-science',
	'estrato-sustain'   => 'estrato-sustain',
	'estrato-culture'   => 'brasil-culture',
);
$preset       = $presets[ $portal ] ?? get_option( 'estrato_rss_preset', 'brasil-financeiro' );
$is_satellite = 'estrato-finance' !== $portal;
$script_dir   = dirname( __FILE__ );

if ( ! defined( 'ESTRATO_OPS_EMBED' ) ) {
	define( 'ESTRATO_OPS_EMBED', true );
}

$stats = array(
	'portal'        => $portal,
	'preset'        => $preset,
	'curation'      => array(),
	'draft_thin'    => 0,
	'boost'         => array(),
	'trim'          => null,
	'ratio_300'     => -1,
	'satellite_fix' => null,
	'data_page'     => null,
	'gate'          => 0,
	'sync'          => array(),
);

if ( function_exists( 'estrato_rss_apply_curation' ) ) {
	$stats['curation'] = estrato_rss_apply_curation( false );
}

if ( function_exists( 'estrato_rss_sync_portal_taxonomy' ) ) {
	$stats['sync'] = estrato_rss_sync_portal_taxonomy( $preset );
}

if ( ! $is_satellite ) {
	$categories = function_exists( 'estrato_rss_create_categories' ) ? estrato_rss_create_categories() : array();
	if ( function_exists( 'estrato_rss_rebuild_menus' ) && ! empty( $categories ) ) {
		estrato_rss_rebuild_menus( $categories );
	}
}

if ( is_readable( $script_dir . '/draft-thin-published.php' ) ) {
	require $script_dir . '/draft-thin-published.php';
	$stats['draft_thin'] = (int) ( $GLOBALS['estrato_ops_draft_thin'] ?? 0 );
}

if ( is_readable( $script_dir . '/boost-mid-posts-300.php' ) ) {
	require $script_dir . '/boost-mid-posts-300.php';
	$stats['boost'] = $GLOBALS['estrato_ops_boost_stats'] ?? array();
}

if ( function_exists( 'estrato_regression_word_ratio' ) ) {
	$stats['ratio_300'] = estrato_regression_word_ratio();
	$ratio_target       = (float) ( getenv( 'ESTRATO_RATIO_TARGET' ) ?: 0.80 );
	if ( $stats['ratio_300'] >= 0 && $stats['ratio_300'] < $ratio_target && is_readable( $script_dir . '/trim-mid-posts-for-ratio.php' ) ) {
		require $script_dir . '/trim-mid-posts-for-ratio.php';
		$stats['trim']      = $GLOBALS['estrato_ops_trim_stats'] ?? null;
		$stats['ratio_300'] = estrato_regression_word_ratio();
	}
}

if ( $is_satellite && is_readable( $script_dir . '/fix-gate-satellites.php' ) ) {
	require $script_dir . '/fix-gate-satellites.php';
	$stats['satellite_fix'] = $GLOBALS['estrato_ops_satellite_fix'] ?? null;
} elseif ( is_readable( $script_dir . '/ensure-data-page.php' ) ) {
	require $script_dir . '/ensure-data-page.php';
	$stats['data_page'] = $GLOBALS['estrato_ops_data_page'] ?? null;
}

$merge_file = $script_dir . '/merge-legacy-categories.php';
if ( ! $is_satellite && is_readable( $merge_file ) ) {
	require $merge_file;
}

$gate_file = $script_dir . '/setup-sprint7-gate.php';
if ( ! $is_satellite && is_readable( $gate_file ) ) {
	require $gate_file;
	$stats['gate'] = 1;
}

if ( class_exists( 'WPSEO_Sitemaps_Cache' ) ) {
	WPSEO_Sitemaps_Cache::clear();
}

update_option( 'estrato_ops_last_monthly_maintenance', gmdate( 'c' ), false );

echo 'monthly_maintenance=' . wp_json_encode( $stats ) . "\n";
