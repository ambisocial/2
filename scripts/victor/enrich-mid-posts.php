<?php
/**
 * Segunda passagem: extensão para posts entre 200–299 palavras.
 */
if ( ! function_exists( 'estrato_content_enrich_post' ) ) {
	exit( 1 );
}

if ( ! defined( 'ESTRATO_ENRICHING' ) ) {
	define( 'ESTRATO_ENRICHING', true );
}

$updated = 0;
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
	$words = estrato_content_post_word_count( $post_id );
	if ( $words >= 300 || $words < 200 ) {
		continue;
	}
	$result = estrato_content_enrich_post( (int) $post_id );
	if ( ! empty( $result['updated'] ) ) {
		++$updated;
	}
}

echo "extension_updated=$updated\n";
