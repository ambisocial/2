<?php
/**
 * Eleva posts 200–299 palavras para meta 300+ (AR-CONTENT-002).
 * Usa pontos-chave derivados do próprio artigo — sem AEO genérico.
 *
 * wp eval-file scripts/victor/boost-mid-posts-300.php
 *
 * @package EstratoVictorScripts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'estrato_content_enrich_post' ) ) {
	WP_CLI::error( 'estrato_content_enrich_post ausente' );
}

if ( ! defined( 'ESTRATO_ENRICHING' ) ) {
	define( 'ESTRATO_ENRICHING', true );
}

$target = defined( 'ESTRATO_TARGET_WORDS' ) ? (int) ESTRATO_TARGET_WORDS : 300;
$stats  = array(
	'checked'    => 0,
	'updated'    => 0,
	'reached'    => 0,
	'still_mid'  => 0,
	'drafted'    => 0,
);

foreach (
	get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'orderby'        => 'date',
			'order'          => 'ASC',
		)
	) as $post_id
) {
	++$stats['checked'];
	$before = estrato_content_post_word_count( $post_id );
	if ( $before >= $target || $before < 200 ) {
		continue;
	}

	$result = estrato_content_enrich_post( (int) $post_id );
	if ( ! empty( $result['updated'] ) ) {
		++$stats['updated'];
	}
	if ( ! empty( $result['drafted'] ) ) {
		++$stats['drafted'];
	}

	$after = estrato_content_post_word_count( $post_id );
	if ( $after >= $target ) {
		++$stats['reached'];
	} elseif ( $after >= 200 ) {
		++$stats['still_mid'];
	}
}

$ratio = function_exists( 'estrato_regression_word_ratio' ) ? estrato_regression_word_ratio() : -1;
$stats['ratio_300'] = $ratio;

WP_CLI::success( wp_json_encode( $stats, JSON_UNESCAPED_UNICODE ) );
