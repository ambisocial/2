<?php
/**
 * Remove da fila pública posts com thumbnail fallback (sem imagem original).
 *
 * Uso: ESTRATO_PORTAL=estrato-finance wp eval-file scripts/victor/purge-fallback-image-posts.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

if ( ! function_exists( 'estrato_bridge_unpublish_without_original_image' ) ) {
	fwrite( STDERR, "estrato-publisher-bridge required\n" );
	exit( 1 );
}

$removed = estrato_bridge_unpublish_without_original_image( 5000 );
echo 'unpublished_no_original_image=' . (int) $removed . PHP_EOL;
