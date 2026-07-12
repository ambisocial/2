<?php
/**
 * Remove posts publicados com thumbnail fallback (sem imagem original).
 */
if ( ! function_exists( 'estrato_bridge_unpublish_without_original_image' ) ) {
	fwrite( STDERR, "estrato-publisher-bridge required\n" );
	exit( 1 );
}

$removed = estrato_bridge_unpublish_without_original_image( 500 );
echo "unpublished_no_original_image=$removed\n";
