<?php
/**
 * Syndication outbound — IndexNow + PlatPhorm + GSC ao publicar.
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ESTRATO_SYNDICATE_SCRIPT', '/var/www/estrato/repo/scripts/victor/syndicate-outbound.py' );
define( 'ESTRATO_SYNDICATE_LOG', '/var/log/estrato/syndicate.log' );

/**
 * @param int $post_id
 */
function estrato_syndicate_on_publish( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post || 'publish' !== $post->post_status || 'post' !== $post->post_type ) {
		return;
	}

	$script = estrato_syndicate_resolve_script_path();
	if ( ! $script ) {
		return;
	}

	$url     = get_permalink( $post_id );
	$title   = get_the_title( $post_id );
	$excerpt = get_the_excerpt( $post_id );
	if ( ! $url ) {
		return;
	}

	$cmd = sprintf(
		'python3 %s --url %s --title %s --content %s >> %s 2>&1 &',
		escapeshellarg( $script ),
		escapeshellarg( $url ),
		escapeshellarg( $title ),
		escapeshellarg( $excerpt ? $excerpt : wp_trim_words( wp_strip_all_tags( $post->post_content ), 40 ) ),
		escapeshellarg( ESTRATO_SYNDICATE_LOG )
	);

	// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
	exec( $cmd );
}
add_action( 'publish_post', 'estrato_syndicate_on_publish', 25, 1 );

/**
 * @return string
 */
function estrato_syndicate_resolve_script_path() {
	$candidates = array(
		ESTRATO_SYNDICATE_SCRIPT,
		dirname( __DIR__ ) . '/scripts/victor/syndicate-outbound.py',
	);
	foreach ( $candidates as $path ) {
		if ( is_readable( $path ) ) {
			return $path;
		}
	}
	return '';
}

/**
 * @return int Linhas no log de syndication (-1 se ausente).
 */
function estrato_regression_syndication_log_lines() {
	if ( ! is_readable( ESTRATO_SYNDICATE_LOG ) ) {
		return -1;
	}
	$lines = 0;
	$fh    = fopen( ESTRATO_SYNDICATE_LOG, 'r' );
	if ( ! $fh ) {
		return -1;
	}
	while ( ! feof( $fh ) ) {
		$line = fgets( $fh );
		if ( false !== $line && '' !== trim( $line ) ) {
			++$lines;
		}
	}
	fclose( $fh );
	return $lines;
}
