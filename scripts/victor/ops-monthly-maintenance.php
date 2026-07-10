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

$stats = array(
	'curation' => array(),
	'thin'     => 0,
	'mid'      => 0,
	'gate'     => 0,
);

if ( function_exists( 'estrato_rss_apply_curation' ) ) {
	$stats['curation'] = estrato_rss_apply_curation( false );
}

if ( function_exists( 'estrato_rss_sync_portal_taxonomy' ) ) {
	estrato_rss_sync_portal_taxonomy();
}

$categories = function_exists( 'estrato_rss_create_categories' ) ? estrato_rss_create_categories() : array();
if ( function_exists( 'estrato_rss_rebuild_menus' ) && ! empty( $categories ) ) {
	estrato_rss_rebuild_menus( $categories );
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
if ( is_readable( $merge_file ) ) {
	require $merge_file;
}

$gate_file = dirname( __FILE__ ) . '/setup-sprint7-gate.php';
if ( is_readable( $gate_file ) ) {
	require $gate_file;
	$stats['gate'] = 1;
}

if ( class_exists( 'WPSEO_Sitemaps_Cache' ) ) {
	WPSEO_Sitemaps_Cache::clear();
}

update_option( 'estrato_ops_last_monthly_maintenance', gmdate( 'c' ), false );

echo 'monthly_maintenance=' . wp_json_encode( $stats ) . "\n";
