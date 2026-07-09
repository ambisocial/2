#!/usr/bin/env php
<?php
/**
 * Substitui thumbnails genéricos (Unsplash) por imagens originais das matérias.
 * Usage: wp eval-file refresh-original-images.php --path=/var/www/estrato.cc
 */
if ( ! function_exists( 'estrato_bridge_refresh_stock_thumbnails' ) ) {
	fwrite( STDERR, "estrato-publisher-bridge v1.2+ required\n" );
	exit( 1 );
}

$batch  = 25;
$rounds = 0;
$total  = array( 'processed' => 0, 'refreshed' => 0, 'failed' => 0 );

	while ( $rounds < 30 ) {
	$stats = estrato_bridge_refresh_stock_thumbnails( $batch );
	foreach ( $stats as $k => $v ) {
		if ( isset( $total[ $k ] ) ) {
			$total[ $k ] += $v;
		}
	}
	$rounds++;
	echo "round {$rounds}: processed={$stats['processed']} refreshed={$stats['refreshed']} failed={$stats['failed']}\n";
	if ( $stats['processed'] < 1 || ( 0 === $stats['refreshed'] && $stats['processed'] > 0 && $rounds > 2 ) ) {
		break;
	}
	if ( $stats['processed'] < $batch ) {
		break;
	}
	sleep( 1 );
}

echo "done: processed={$total['processed']} refreshed={$total['refreshed']} failed={$total['failed']}\n";
