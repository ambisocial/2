<?php
/**
 * Enriquece posts finos em lote (Sprint 4).
 *
 * Uso: wp eval-file enrich-thin-posts.php --path=/var/www/estrato.cc [--batch=100] [--offset=0]
 */
if ( ! function_exists( 'estrato_content_enrich_post' ) ) {
	fwrite( STDERR, "estrato-portal-bootstrap content-quality.php must be loaded\n" );
	exit( 1 );
}

$batch  = (int) ( getenv( 'ESTRATO_ENRICH_BATCH' ) ?: 100 );
$offset = (int) ( getenv( 'ESTRATO_ENRICH_OFFSET' ) ?: 0 );

$ids = get_posts(
	array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => $batch,
		'offset'         => $offset,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'fields'         => 'ids',
	)
);

$stats = array(
	'processed' => 0,
	'updated'   => 0,
	'drafted'   => 0,
	'skipped'   => 0,
);

foreach ( $ids as $post_id ) {
	++$stats['processed'];
	$result = estrato_content_enrich_post( (int) $post_id );
	if ( ! empty( $result['updated'] ) ) {
		++$stats['updated'];
	}
	if ( ! empty( $result['drafted'] ) ) {
		++$stats['drafted'];
	}
	if ( empty( $result['updated'] ) ) {
		++$stats['skipped'];
	}
}

echo wp_json_encode( $stats, JSON_PRETTY_PRINT ) . "\n";
