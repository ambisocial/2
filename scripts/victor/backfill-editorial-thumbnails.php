<?php
/**
 * Thumbnails OG para longforms editoriais sem imagem destacada.
 *
 * wp eval-file scripts/victor/backfill-editorial-thumbnails.php
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

if ( ! function_exists( 'estrato_bridge_set_editorial_thumbnail' ) ) {
	WP_CLI::error( 'estrato_bridge_set_editorial_thumbnail ausente' );
}

$posts = get_posts(
	array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_key'       => '_estrato_content_mode',
		'meta_value'     => 'analysis',
	)
);

$set = 0;
$fail = 0;
foreach ( $posts as $post_id ) {
	if ( has_post_thumbnail( $post_id ) ) {
		continue;
	}
	if ( estrato_bridge_set_editorial_thumbnail( (int) $post_id ) ) {
		++$set;
	} else {
		++$fail;
	}
}

WP_CLI::success( wp_json_encode( array( 'set' => $set, 'failed' => $fail, 'total' => count( $posts ) ) ) );
