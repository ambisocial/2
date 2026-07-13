#!/usr/bin/env php
<?php
/**
 * Backfill featured images for posts without thumbnail.
 * Usage: wp eval-file backfill-featured-images.php --path=/var/www/estrato.cc
 */
if ( ! function_exists( 'estrato_bridge_backfill_featured_images' ) ) {
	fwrite( STDERR, "estrato-publisher-bridge must be active\n" );
	exit( 1 );
}

$batch  = 40;
$rounds = 0;
$total  = array( 'processed' => 0, 'set' => 0, 'failed' => 0 );

while ( $rounds < 20 ) {
	$stats = estrato_bridge_backfill_featured_images( $batch );
	foreach ( $stats as $k => $v ) {
		$total[ $k ] += $v;
	}
	$rounds++;
	echo "round {$rounds}: processed={$stats['processed']} set={$stats['set']} failed={$stats['failed']}\n";
	if ( $stats['processed'] < $batch ) {
		break;
	}
}

echo "done: processed={$total['processed']} set={$total['set']} failed={$total['failed']}\n";
