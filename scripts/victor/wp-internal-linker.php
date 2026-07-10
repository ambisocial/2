<?php
/**
 * Garante 3 links internos em posts publicados (Sprint 4 linker WP).
 *
 * Uso: wp eval-file wp-internal-linker.php --path=/var/www/estrato.cc [--batch=80]
 */
if ( ! function_exists( 'estrato_content_inject_internal_links' ) ) {
	fwrite( STDERR, "content-quality.php required\n" );
	exit( 1 );
}

$batch = 80;
foreach ( array_slice( $GLOBALS['argv'] ?? array(), 1 ) as $arg ) {
	if ( preg_match( '/^--batch=(\d+)$/', $arg, $m ) ) {
		$batch = (int) $m[1];
	}
}

$updated = 0;
$checked = 0;

foreach (
	get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => $batch,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	) as $post
) {
	++$checked;
	if ( false !== strpos( $post->post_content, 'estrato-internal-links' ) ) {
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

echo wp_json_encode(
	array(
		'checked' => $checked,
		'updated' => $updated,
	),
	JSON_PRETTY_PRINT
) . "\n";
