<?php
/**
 * Move posts publicados antes de 2024-01-01 para lixeira (AR-SITEMAP-004).
 * Usa date_query — nunca WP-CLI --before em massa.
 *
 * Uso: ESTRATO_DRY_RUN=1 wp eval-file trash-pre2024-posts.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$dry_run = '1' === getenv( 'ESTRATO_DRY_RUN' );

$ids = get_posts(
	array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'orderby'        => 'ID',
		'order'          => 'ASC',
		'date_query'     => array(
			array(
				'before'    => '2024-01-01 00:00:00',
				'inclusive' => false,
				'column'    => 'post_date',
			),
		),
	)
);

$trashed = 0;
foreach ( $ids as $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post ) {
		continue;
	}
	if ( strtotime( $post->post_date ) >= strtotime( '2024-01-01 00:00:00' ) ) {
		continue;
	}
	if ( $dry_run ) {
		WP_CLI::log( "DRY-RUN trash #{$post_id} {$post->post_date} {$post->post_title}" );
		++$trashed;
		continue;
	}
	wp_trash_post( (int) $post_id );
	++$trashed;
}

$mode = $dry_run ? 'DRY-RUN' : 'APPLIED';
WP_CLI::success( "{$mode}: {$trashed} posts pré-2024 → trash" );
