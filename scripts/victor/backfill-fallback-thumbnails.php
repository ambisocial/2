<?php
/**
 * Aplica thumbnail fallback OG em posts publicados sem imagem destacada.
 */
if ( ! function_exists( 'estrato_bridge_set_fallback_thumbnail' ) ) {
	fwrite( STDERR, "estrato-publisher-bridge required\n" );
	exit( 1 );
}

$set = 0;
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
	if ( has_post_thumbnail( $post_id ) ) {
		continue;
	}
	if ( estrato_bridge_set_fallback_thumbnail( $post_id ) ) {
		++$set;
	}
}

echo "fallback_set=$set\n";
