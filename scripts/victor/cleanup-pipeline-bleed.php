<?php
/**
 * Higieniza bleed de pipeline: posts publicados sem source, em cats legado,
 * ou sem thumbnail — move para draft/trash.
 *
 * Uso: wp eval-file scripts/victor/cleanup-pipeline-bleed.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$legacy = array( 'politica', 'tecnologia', 'brasil', 'sem-categoria' );
$report = array(
	'no_source_drafted'   => 0,
	'legacy_drafted'      => 0,
	'no_thumb_drafted'    => 0,
	'future_legacy_draft' => 0,
);

$q = new WP_Query(
	array(
		'post_type'      => 'post',
		'post_status'    => array( 'publish', 'future' ),
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	)
);

foreach ( $q->posts as $post_id ) {
	$post_id = (int) $post_id;
	$status  = get_post_status( $post_id );
	$mode    = (string) get_post_meta( $post_id, '_estrato_content_mode', true );
	$editorial = (string) get_post_meta( $post_id, '_estrato_editorial_source', true );
	$is_editorial = ( '' !== $editorial || 'analysis' === $mode );

	$src = (string) get_post_meta( $post_id, '_estrato_source_url', true );
	if ( ! $src ) {
		$src = (string) get_post_meta( $post_id, '_estrato_rss_source_url', true );
	}

	$slugs = wp_get_post_terms( $post_id, 'category', array( 'fields' => 'slugs' ) );
	if ( is_wp_error( $slugs ) ) {
		$slugs = array();
	}
	$legacy_hit = array_values( array_intersect( $legacy, $slugs ) );

	$reasons = array();
	if ( ! $is_editorial && '' === $src ) {
		$reasons[] = 'missing_source_url';
	}
	if ( $legacy_hit ) {
		$reasons[] = 'legacy_category:' . implode( ',', $legacy_hit );
	}
	if ( 'publish' === $status && ! has_post_thumbnail( $post_id ) ) {
		$reasons[] = 'no_thumbnail';
	}

	if ( ! $reasons ) {
		continue;
	}

	wp_update_post(
		array(
			'ID'          => $post_id,
			'post_status' => 'draft',
		)
	);
	update_post_meta( $post_id, '_estrato_skip_reason', implode( '|', $reasons ) );

	if ( in_array( 'missing_source_url', $reasons, true ) ) {
		++$report['no_source_drafted'];
	}
	if ( $legacy_hit ) {
		++$report['legacy_drafted'];
		if ( 'future' === $status ) {
			++$report['future_legacy_draft'];
		}
	}
	if ( in_array( 'no_thumbnail', $reasons, true ) ) {
		++$report['no_thumb_drafted'];
	}
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::success( wp_json_encode( $report, JSON_UNESCAPED_UNICODE ) );
} else {
	echo wp_json_encode( $report, JSON_UNESCAPED_UNICODE ) . "\n";
}
