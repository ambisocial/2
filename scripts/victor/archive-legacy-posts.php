<?php
/**
 * Arquiva posts legados: noindex para pré-2024 e permalinks com path /YYYY/.
 * Uso: ESTRATO_DRY_RUN=1 wp eval-file archive-legacy-posts.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$dry_run = '1' === getenv( 'ESTRATO_DRY_RUN' );

/**
 * Detecta permalinks com segmento de data legado (/2022/), não anos em slugs (ex.: messi-2022).
 *
 * @param string $url
 * @return bool
 */
function estrato_archive_url_has_legacy_date_path( $url ) {
	return (bool) preg_match( '#/(201[89]|202[0-3])(/|$)#', $url );
}

/**
 * @param int $post_id
 * @return bool
 */
function estrato_archive_should_noindex( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post || 'post' !== $post->post_type || 'publish' !== $post->post_status ) {
		return false;
	}

	if ( strtotime( $post->post_date ) < strtotime( '2024-01-01 00:00:00' ) ) {
		return true;
	}

	$permalink = get_permalink( $post );
	if ( $permalink && estrato_archive_url_has_legacy_date_path( $permalink ) ) {
		return true;
	}

	return false;
}

$ids = get_posts(
	array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'orderby'        => 'ID',
		'order'          => 'ASC',
	)
);

$marked   = 0;
$skipped  = 0;
$pre_2024 = 0;
$path_old = 0;

foreach ( $ids as $post_id ) {
	if ( ! estrato_archive_should_noindex( $post_id ) ) {
		++$skipped;
		continue;
	}

	$post = get_post( $post_id );
	if ( strtotime( $post->post_date ) < strtotime( '2024-01-01 00:00:00' ) ) {
		++$pre_2024;
	} else {
		++$path_old;
	}

	if ( $dry_run ) {
		++$marked;
		continue;
	}

	update_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', '1' );
	update_post_meta( $post_id, '_yoast_wpseo_meta-robots-nofollow', '0' );

	wp_update_post(
		array(
			'ID'          => $post_id,
			'post_status' => 'draft',
		)
	);

	++$marked;
}

if ( ! $dry_run && class_exists( 'WPSEO_Sitemaps_Cache' ) ) {
	WPSEO_Sitemaps_Cache::clear();
}

$mode = $dry_run ? 'DRY-RUN' : 'APPLIED';
echo "{$mode}: noindex/draft em {$marked} posts (pre-2024: {$pre_2024}, path legado: {$path_old}), skipped {$skipped}\n";
