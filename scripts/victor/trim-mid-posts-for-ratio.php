<?php
/**
 * Move para rascunho os posts 200–299 mais antigos até ratio 300+ ≥ 0.80.
 *
 * wp eval-file scripts/victor/trim-mid-posts-for-ratio.php
 *
 * @package EstratoVictorScripts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$target_ratio = (float) ( getenv( 'ESTRATO_RATIO_TARGET' ) ?: 0.80 );
$min_words    = defined( 'ESTRATO_TARGET_WORDS' ) ? (int) ESTRATO_TARGET_WORDS : 300;
$drafted      = 0;

$candidates = array();
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
	$words = function_exists( 'estrato_content_post_word_count' )
		? estrato_content_post_word_count( $post_id )
		: 0;
	if ( $words >= 200 && $words < $min_words ) {
		$candidates[] = (int) $post_id;
	}
}

while ( $candidates && function_exists( 'estrato_regression_word_ratio' ) ) {
	$ratio = estrato_regression_word_ratio();
	if ( $ratio >= $target_ratio ) {
		break;
	}
	$post_id = array_shift( $candidates );
	wp_update_post(
		array(
			'ID'          => $post_id,
			'post_status' => 'draft',
		)
	);
	++$drafted;
}

$final = function_exists( 'estrato_regression_word_ratio' ) ? estrato_regression_word_ratio() : -1;

if ( defined( 'ESTRATO_OPS_EMBED' ) ) {
	$GLOBALS['estrato_ops_trim_stats'] = array(
		'drafted'   => $drafted,
		'ratio_300' => $final,
	);
	return;
}

WP_CLI::success( "drafted=$drafted ratio_300=$final" );
