<?php
/**
 * Enriquece todos os posts publicados abaixo de 300 palavras.
 */
if ( ! function_exists( 'estrato_content_enrich_post' ) ) {
	fwrite( STDERR, "content-quality.php required\n" );
	exit( 1 );
}

if ( ! defined( 'ESTRATO_ENRICHING' ) ) {
	define( 'ESTRATO_ENRICHING', true );
}

$target   = (int) ( getenv( 'ESTRATO_ENRICH_TARGET' ) ?: 300 );
$max_pass = (int) ( getenv( 'ESTRATO_ENRICH_MAX' ) ?: 0 );

$stats = array(
	'checked'  => 0,
	'updated'  => 0,
	'drafted'  => 0,
	'still_thin' => 0,
);

foreach (
	get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'orderby'        => 'ID',
			'order'          => 'ASC',
		)
	) as $post_id
) {
	++$stats['checked'];
	$words = estrato_content_post_word_count( $post_id );
	if ( $words >= $target ) {
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
	if ( $after < 200 ) {
		++$stats['still_thin'];
	}

	if ( $max_pass > 0 && $stats['updated'] >= $max_pass ) {
		break;
	}
}

echo wp_json_encode( $stats, JSON_PRETTY_PRINT ) . "\n";
