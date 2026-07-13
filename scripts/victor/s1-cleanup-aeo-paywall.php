<?php
/**
 * Sprint 1 — remove blocos AEO genéricos e paywall do banco.
 *
 * wp eval-file scripts/victor/s1-cleanup-aeo-paywall.php
 *
 * @package EstratoVictorScripts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'ESTRATO_AEO_MARKER' ) ) {
	WP_CLI::error( 'ESTRATO_AEO_MARKER ausente — ative estrato-portal-bootstrap' );
}

$updated = 0;
$skipped = 0;

foreach ( get_posts(
	array(
		'post_type'      => 'post',
		'post_status'    => array( 'publish', 'draft' ),
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
) as $post_id ) {
	$content = get_post_field( 'post_content', $post_id );
	if ( ! $content ) {
		++$skipped;
		continue;
	}
	$needs = false !== strpos( $content, ESTRATO_AEO_MARKER )
		|| preg_match( ESTRATO_PAYWALL_PATTERNS, wp_strip_all_tags( $content ) )
		|| preg_match( '/glbimg\.com|globo\.com\/multimedia/i', $content );
	if ( ! $needs ) {
		++$skipped;
		continue;
	}
	$clean = $content;
	if ( function_exists( 'estrato_content_strip_generic_aeo' ) ) {
		$clean = estrato_content_strip_generic_aeo( $clean );
	}
	if ( function_exists( 'estrato_pipeline_sanitize_content_html' ) ) {
		$clean = estrato_pipeline_sanitize_content_html( $clean );
	}
	if ( $clean === $content ) {
		++$skipped;
		continue;
	}
	wp_update_post(
		array(
			'ID'           => $post_id,
			'post_content' => $clean,
		)
	);
	if ( function_exists( 'estrato_pipeline_apply_category' ) ) {
		$post = get_post( $post_id );
		if ( $post ) {
			estrato_pipeline_apply_category( $post_id, $post->post_title, $clean );
		}
	}
	++$updated;
}

WP_CLI::success( "S1 cleanup: updated=$updated skipped=$skipped" );
