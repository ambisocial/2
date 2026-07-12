<?php
/**
 * Sprint D1 — internal linker (3 links/post) em lote completo.
 *
 * Uso: ESTRATO_PORTAL=estrato-mind wp eval-file setup-sprint-d1-portal-linker.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

if ( ! function_exists( 'estrato_content_inject_internal_links' ) ) {
	WP_CLI::error( 'estrato_content_inject_internal_links ausente (content-quality.php)' );
}

$batch   = max( 20, (int) ( getenv( 'ESTRATO_LINKER_BATCH' ) ?: 100 ) );
$offset  = 0;
$updated = 0;
$checked = 0;
$skipped = 0;

do {
	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => $batch,
			'offset'         => $offset,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		)
	);

	foreach ( $posts as $post ) {
		++$checked;
		if ( false !== strpos( $post->post_content, 'estrato-internal-links' ) ) {
			++$skipped;
			continue;
		}

		$new_content = estrato_content_inject_internal_links( $post->post_content, $post->ID );
		if ( $new_content !== $post->post_content ) {
			wp_update_post(
				array(
					'ID'           => $post->ID,
					'post_content' => $new_content,
				)
			);
			++$updated;
		}
	}

	$offset += $batch;
} while ( count( $posts ) === $batch );

$without = 0;
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
	$content = get_post_field( 'post_content', $post_id );
	if ( false === strpos( $content, 'estrato-internal-links' ) ) {
		++$without;
	}
}

WP_CLI::success(
	wp_json_encode(
		array(
			'portal'  => getenv( 'ESTRATO_PORTAL' ) ?: estrato_nav_current_portal_id(),
			'checked' => $checked,
			'updated' => $updated,
			'skipped' => $skipped,
			'without' => $without,
		),
		JSON_UNESCAPED_UNICODE
	)
);
