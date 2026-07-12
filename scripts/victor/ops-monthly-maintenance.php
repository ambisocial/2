<?php
/**
 * Sprint 10 — Manutenção mensal: feeds + thin posts + gate.
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
	'estrato-sustain'   => 'brasil-sustain',
	'estrato-culture'   => 'brasil-culture',
);
$preset      = $presets[ $portal ] ?? get_option( 'estrato_rss_preset', 'brasil-financeiro' );
$is_satellite = 'estrato-finance' !== $portal;

$stats = array(
	'portal'   => $portal,
	'preset'   => $preset,
	'curation' => array(),
	'thin'     => 0,
	'mid'      => 0,
	'gate'     => 0,
	'sync'     => array(),
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

if ( function_exists( 'estrato_content_enrich_post' ) && ! defined( 'ESTRATO_ENRICHING' ) ) {
	define( 'ESTRATO_ENRICHING', true );
	foreach (
		get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'fields'         => 'ids',
				'orderby'        => 'modified',
				'order'          => 'ASC',
			)
		) as $post_id
	) {
		$words = estrato_content_post_word_count( $post_id );
		if ( $words < 200 ) {
			$r = estrato_content_enrich_post( (int) $post_id );
			if ( ! empty( $r['updated'] ) ) {
				++$stats['thin'];
			}
		} elseif ( $words < 300 ) {
			$r = estrato_content_enrich_post( (int) $post_id );
			if ( ! empty( $r['updated'] ) ) {
				++$stats['mid'];
			}
		}
	}
}

$merge_file = dirname( __FILE__ ) . '/merge-legacy-categories.php';
if ( ! $is_satellite && is_readable( $merge_file ) ) {
	require $merge_file;
}

$gate_file = dirname( __FILE__ ) . '/setup-sprint7-gate.php';
if ( ! $is_satellite && is_readable( $gate_file ) ) {
	require $gate_file;
	$stats['gate'] = 1;
}

if ( class_exists( 'WPSEO_Sitemaps_Cache' ) ) {
	WPSEO_Sitemaps_Cache::clear();
}

update_option( 'estrato_ops_last_monthly_maintenance', gmdate( 'c' ), false );

echo 'monthly_maintenance=' . wp_json_encode( $stats ) . "\n";
