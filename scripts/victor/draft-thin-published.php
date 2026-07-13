<?php
/**
 * Move posts publicados com <200 palavras para rascunho (gate AR-CONTENT-001).
 *
 * wp eval-file scripts/victor/draft-thin-published.php
 *
 * @package EstratoVictorScripts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$min = defined( 'ESTRATO_MIN_PUBLISH_WORDS' ) ? (int) ESTRATO_MIN_PUBLISH_WORDS : 200;
$drafted = 0;

foreach ( get_posts(
	array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
) as $post_id ) {
	$words = function_exists( 'estrato_content_post_word_count' )
		? estrato_content_post_word_count( $post_id )
		: 0;
	if ( $words >= $min ) {
		continue;
	}
	wp_update_post(
		array(
			'ID'          => $post_id,
			'post_status' => 'draft',
		)
	);
	if ( function_exists( 'estrato_content_sync_yoast_index' ) ) {
		estrato_content_sync_yoast_index( $post_id, $words );
	}
	++$drafted;
}

if ( defined( 'ESTRATO_OPS_EMBED' ) ) {
	$GLOBALS['estrato_ops_draft_thin'] = $drafted;
	return;
}

WP_CLI::success( "Thin posts drafted: $drafted (min=$min words)" );
