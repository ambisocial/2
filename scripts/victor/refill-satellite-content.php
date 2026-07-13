<?php
/**
 * Refill satélites: limpa GUIDs off-matrix, publica drafts editoriais com thumb,
 * e dispara um import RSS.
 *
 * Uso: ESTRATO_PORTAL=estrato-culture wp eval-file scripts/victor/refill-satellite-content.php
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
	'analysis_published'=> 0,
	'analysis_skipped'  => 0,
	'import'            => null,
);

$matrix = get_option( 'estrato_rss_import_matrix', array() );
$allowed_hosts = array();
if ( is_array( $matrix ) ) {
	foreach ( $matrix as $entry ) {
		if ( ! is_array( $entry ) ) {
			continue;
		}
		$feeds = array();
		if ( ! empty( $entry['feeds'] ) && is_array( $entry['feeds'] ) ) {
			$feeds = $entry['feeds'];
		} elseif ( ! empty( $entry['node'] ) ) {
			$node = is_string( $entry['node'] ) ? json_decode( $entry['node'], true ) : $entry['node'];
			if ( is_array( $node ) && ! empty( $node['feeds'] ) ) {
				$feeds = $node['feeds'];
			}
		}
		foreach ( (array) $feeds as $feed ) {
			$url  = is_string( $feed ) ? $feed : (string) ( $feed['url'] ?? '' );
			$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
			if ( $host ) {
				$allowed_hosts[ $host ] = true;
				// aceitar variação sem www.
				$allowed_hosts[ preg_replace( '/^www\./', '', $host ) ] = true;
			}
		}
	}
}

$guids = get_option( 'estrato_rss_imported_guids', array() );
if ( ! is_array( $guids ) ) {
	$guids = array();
}
$report['guids_before'] = count( $guids );

if ( $allowed_hosts ) {
	$kept = array();
	foreach ( $guids as $guid => $flag ) {
		$host = strtolower( (string) wp_parse_url( (string) $guid, PHP_URL_HOST ) );
		$host_bare = preg_replace( '/^www\./', '', $host );
		if ( $host && ( isset( $allowed_hosts[ $host ] ) || isset( $allowed_hosts[ $host_bare ] ) ) ) {
			$kept[ $guid ] = $flag;
			continue;
		}
		++$report['guids_pruned'];
	}
	$guids = $kept;
	update_option( 'estrato_rss_imported_guids', $guids, false );
}
$report['guids_after'] = count( $guids );

// Publica drafts editoriais (analysis) — preferir o mais recente por título, com thumb.
$drafts = get_posts(
	array(
		'post_type'      => 'post',
		'post_status'    => 'draft',
		'posts_per_page' => 40,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'meta_key'       => '_estrato_content_mode',
		'meta_value'     => 'analysis',
	)
);

$seen_titles = array();
foreach ( $drafts as $post ) {
	$title_key = mb_strtolower( trim( wp_strip_all_tags( $post->post_title ) ) );
	if ( isset( $seen_titles[ $title_key ] ) ) {
		++$report['analysis_skipped'];
		continue;
	}
	// Já existe publicado com mesmo título?
	$existing_id = 0;
	if ( function_exists( 'post_exists' ) ) {
		$existing_id = (int) post_exists( $post->post_title, '', '', 'post' );
		if ( $existing_id && 'publish' !== get_post_status( $existing_id ) ) {
			$existing_id = 0;
		}
	}
	if ( $existing_id && (int) $existing_id !== (int) $post->ID ) {
		++$report['analysis_skipped'];
		$seen_titles[ $title_key ] = true;
		continue;
	}

	if ( ! has_post_thumbnail( $post->ID ) && function_exists( 'estrato_bridge_set_editorial_thumbnail' ) ) {
		estrato_bridge_set_editorial_thumbnail( (int) $post->ID );
	}
	if ( ! has_post_thumbnail( $post->ID ) ) {
		++$report['analysis_skipped'];
		continue;
	}

	wp_update_post(
		array(
			'ID'          => (int) $post->ID,
			'post_status' => 'publish',
		)
	);
	$seen_titles[ $title_key ] = true;
	++$report['analysis_published'];
}

if ( function_exists( 'estrato_rss_run_import' ) ) {
	$report['import'] = estrato_rss_run_import( false );
}

$pub = (int) wp_count_posts( 'post' )->publish;
$report['publish_total'] = $pub;

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::success( wp_json_encode( $report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
} else {
	echo wp_json_encode( $report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
}
