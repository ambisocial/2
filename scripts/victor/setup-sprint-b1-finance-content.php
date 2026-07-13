<?php
/**
 * Sprint B1 — Conteúdo finance (estrato.cc).
 *
 * - Backfill thumbnails
 * - Enrich posts finos / mid
 *
 * Uso: ESTRATO_PORTAL=estrato-finance wp eval-file setup-sprint-b1-finance-content.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$stats = array(
	'fallback_thumbs' => 0,
	'thin_enriched'   => 0,
	'mid_enriched'    => 0,
);

$backfill = dirname( __FILE__ ) . '/backfill-fallback-thumbnails.php';
if ( is_readable( $backfill ) && function_exists( 'estrato_bridge_set_fallback_thumbnail' ) ) {
	foreach (
		get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		) as $post_id
	) {
		if ( has_post_thumbnail( $post_id ) ) {
			continue;
		}
		if ( estrato_bridge_set_fallback_thumbnail( $post_id ) ) {
			++$stats['fallback_thumbs'];
		}
	}
} elseif ( is_readable( $backfill ) ) {
	ob_start();
	require $backfill;
	$out = ob_get_clean();
	if ( preg_match( '/fallback_set=(\d+)/', $out, $m ) ) {
		$stats['fallback_thumbs'] = (int) $m[1];
	}
}

if ( function_exists( 'estrato_content_enrich_post' ) && ! defined( 'ESTRATO_ENRICHING' ) ) {
	define( 'ESTRATO_ENRICHING', true );
	foreach (
		get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 150,
				'fields'         => 'ids',
				'orderby'        => 'modified',
				'order'          => 'ASC',
			)
		) as $post_id
	) {
		if ( ! function_exists( 'estrato_content_post_word_count' ) ) {
			break;
		}
		$words = estrato_content_post_word_count( $post_id );
		if ( $words < 200 ) {
			$r = estrato_content_enrich_post( (int) $post_id );
			if ( ! empty( $r['updated'] ) ) {
				++$stats['thin_enriched'];
			}
		} elseif ( $words < 300 ) {
			$r = estrato_content_enrich_post( (int) $post_id );
			if ( ! empty( $r['updated'] ) ) {
				++$stats['mid_enriched'];
			}
		}
	}
}

$no_thumb = function_exists( 'estrato_regression_posts_without_thumbnail' )
	? estrato_regression_posts_without_thumbnail()
	: -1;
$thin = function_exists( 'estrato_regression_thin_posts' )
	? estrato_regression_thin_posts()
	: -1;

WP_CLI::success(
	wp_json_encode(
		array_merge(
			$stats,
			array(
				'no_thumb' => $no_thumb,
				'thin'     => $thin,
			)
		),
		JSON_UNESCAPED_UNICODE
	)
);
