<?php
/**
 * Boost de volume nos satélites:
 * 1) poda GUIDs cujo post não está mais publicado/rascunho
 * 2) restaura trash on-matrix com thumbnail
 * 3) sobe limites do import e dispara uma passagem
 *
 * Uso: ESTRATO_PORTAL=estrato-culture wp eval-file scripts/victor/boost-satellite-rss.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$report = array(
	'portal'            => getenv( 'ESTRATO_PORTAL' ) ?: home_url( '/' ),
	'guids_before'      => 0,
	'guids_pruned'      => 0,
	'guids_after'       => 0,
	'trash_restored'    => 0,
	'trash_skipped'     => 0,
	'import'            => null,
	'publish_before'    => (int) wp_count_posts( 'post' )->publish,
	'publish_after'     => 0,
);

$allowed_hosts = array();
$matrix        = get_option( 'estrato_rss_import_matrix', array() );
if ( is_array( $matrix ) ) {
	foreach ( $matrix as $entry ) {
		if ( ! is_array( $entry ) ) {
			continue;
		}
		$feeds = array();
		if ( ! empty( $entry['feeds'] ) && is_array( $entry['feeds'] ) ) {
			$feeds = $entry['feeds'];
		}
		foreach ( (array) $feeds as $feed ) {
			$url  = is_string( $feed ) ? $feed : (string) ( $feed['url'] ?? '' );
			$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
			if ( $host ) {
				$allowed_hosts[ $host ]                   = true;
				$allowed_hosts[ preg_replace( '/^www\./', '', $host ) ] = true;
			}
		}
	}
}

// 1) Index GUID → post vivos.
$live_guids = array();
$live_q     = new WP_Query(
	array(
		'post_type'      => 'post',
		'post_status'    => array( 'publish', 'draft', 'future', 'pending' ),
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'meta_query'     => array(
			array(
				'key'     => '_estrato_rss_guid',
				'compare' => 'EXISTS',
			),
		),
	)
);
foreach ( $live_q->posts as $pid ) {
	$g = (string) get_post_meta( (int) $pid, '_estrato_rss_guid', true );
	if ( $g ) {
		$live_guids[ $g ] = true;
	}
}

$guids = get_option( 'estrato_rss_imported_guids', array() );
if ( ! is_array( $guids ) ) {
	$guids = array();
}
$report['guids_before'] = count( $guids );

$kept = array();
foreach ( $guids as $guid => $flag ) {
	$host = strtolower( (string) wp_parse_url( (string) $guid, PHP_URL_HOST ) );
	$bare = preg_replace( '/^www\./', '', $host );
	// Mantém GUID se ainda tem post vivo OU se o host é off-matrix (anti recontaminação).
	$on_matrix = $host && ( isset( $allowed_hosts[ $host ] ) || isset( $allowed_hosts[ $bare ] ) );
	if ( isset( $live_guids[ $guid ] ) ) {
		$kept[ $guid ] = $flag;
		continue;
	}
	if ( $host && ! $on_matrix ) {
		// Off-matrix residual: manter bloqueado.
		$kept[ $guid ] = $flag;
		continue;
	}
	++$report['guids_pruned'];
}
$guids = $kept;
update_option( 'estrato_rss_imported_guids', $guids, false );
$report['guids_after'] = count( $guids );

// 2) Restaura trash on-matrix com imagem.
$trash = get_posts(
	array(
		'post_type'      => 'post',
		'post_status'    => 'trash',
		'posts_per_page' => 80,
		'orderby'        => 'date',
		'order'          => 'DESC',
	)
);
foreach ( $trash as $post ) {
	$src = (string) get_post_meta( $post->ID, '_estrato_rss_source_url', true );
	if ( ! $src ) {
		$src = (string) get_post_meta( $post->ID, '_estrato_source_url', true );
	}
	if ( ! $src ) {
		++$report['trash_skipped'];
		continue;
	}
	$host = strtolower( (string) wp_parse_url( $src, PHP_URL_HOST ) );
	$bare = preg_replace( '/^www\./', '', $host );
	if ( ! $host || ( ! isset( $allowed_hosts[ $host ] ) && ! isset( $allowed_hosts[ $bare ] ) ) ) {
		++$report['trash_skipped'];
		continue;
	}
	if ( ! has_post_thumbnail( $post->ID ) && function_exists( 'estrato_bridge_set_editorial_thumbnail' ) ) {
		estrato_bridge_set_editorial_thumbnail( (int) $post->ID );
	}
	if ( ! has_post_thumbnail( $post->ID ) ) {
		++$report['trash_skipped'];
		continue;
	}
	// Pular duplicata de título já publicada.
	$dup = post_exists( $post->post_title, '', '', 'post' );
	if ( $dup && (int) $dup !== (int) $post->ID && 'publish' === get_post_status( $dup ) ) {
		++$report['trash_skipped'];
		continue;
	}
	wp_untrash_post( (int) $post->ID );
	wp_update_post(
		array(
			'ID'          => (int) $post->ID,
			'post_status' => 'publish',
		)
	);
	++$report['trash_restored'];
	if ( $report['trash_restored'] >= 25 ) {
		break;
	}
}

// 3) Boost settings temporário + import.
if ( function_exists( 'estrato_rss_get_settings' ) ) {
	$settings                     = estrato_rss_get_settings();
	$settings['items_per_feed']   = max( (int) ( $settings['items_per_feed'] ?? 3 ), 8 );
	$settings['items_first_run']  = max( (int) ( $settings['items_first_run'] ?? 5 ), 12 );
	$settings['max_per_run']      = max( (int) ( $settings['max_per_run'] ?? 30 ), 80 );
	update_option( 'estrato_rss_settings', $settings, false );
}

if ( function_exists( 'estrato_rss_run_import' ) ) {
	$report['import'] = estrato_rss_run_import( true );
}

// 4) Backfill thumbs residuais.
if ( function_exists( 'estrato_bridge_set_editorial_thumbnail' ) ) {
	$missing = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 40,
			'meta_query'     => array(
				array(
					'key'     => '_thumbnail_id',
					'compare' => 'NOT EXISTS',
				),
			),
		)
	);
	foreach ( $missing as $p ) {
		estrato_bridge_set_editorial_thumbnail( (int) $p->ID );
	}
}

$report['publish_after'] = (int) wp_count_posts( 'post' )->publish;

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::success( wp_json_encode( $report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
} else {
	echo wp_json_encode( $report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
}
