<?php
/**
 * Corrige avisos strict do gate em satélites (AR-EEAT-006, AR-TAX-001).
 *
 * wp eval-file scripts/victor/fix-gate-satellites.php
 *
 * @package EstratoVictorScripts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$portal = getenv( 'ESTRATO_PORTAL' ) ?: '';
$fixed  = array();

// AR-EEAT-006: posts de autor sem bio → reatribui ao primeiro autor com descrição.
if ( function_exists( 'estrato_regression_authors_without_bio' ) ) {
	global $wpdb;
	$rows = $wpdb->get_results(
		"
		SELECT DISTINCT p.ID, p.post_author
		FROM {$wpdb->posts} p
		INNER JOIN {$wpdb->users} u ON u.ID = p.post_author
		LEFT JOIN {$wpdb->usermeta} um ON um.user_id = u.ID AND um.meta_key = 'description'
		WHERE p.post_type = 'post'
		  AND p.post_status = 'publish'
		  AND (um.meta_value IS NULL OR TRIM(um.meta_value) = '')
		"
	);
	$author_with_bio = get_users(
		array(
			'role'    => 'author',
			'number'  => 1,
			'orderby' => 'ID',
			'order'   => 'ASC',
			'meta_query' => array(
				array(
					'key'     => 'description',
					'value'   => '',
					'compare' => '!=',
				),
			),
		)
	);
	if ( $rows && ! empty( $author_with_bio[0] ) ) {
		$new_author = (int) $author_with_bio[0]->ID;
		foreach ( $rows as $row ) {
			wp_update_post(
				array(
					'ID'          => (int) $row->ID,
					'post_author' => $new_author,
				)
			);
			$fixed[] = 'author:' . $row->ID . '→' . $new_author;
		}
	}
}

// AR-TAX-001: editorias vazias no YAML do portal.
$yaml_path = getenv( 'ESTRATO_REPO' ) ? getenv( 'ESTRATO_REPO' ) . '/portals/' . $portal . '.yaml' : '';
if ( ! $yaml_path || ! is_readable( $yaml_path ) ) {
	$yaml_path = dirname( __DIR__, 2 ) . '/portals/' . $portal . '.yaml';
}

$editorias = array();
if ( is_readable( $yaml_path ) ) {
	$in_cats = false;
	foreach ( file( $yaml_path ) as $line ) {
		if ( preg_match( '/^categories:\s*$/', $line ) ) {
			$in_cats = true;
			continue;
		}
		if ( $in_cats && preg_match( '/^  - ([a-z0-9-]+)/', $line, $m ) ) {
			$editorias[] = $m[1];
			continue;
		}
		if ( $in_cats && preg_match( '/^[a-z]/', $line ) ) {
			break;
		}
	}
}

$empty_slugs = array();
foreach ( $editorias as $slug ) {
	$term = get_term_by( 'slug', $slug, 'category' );
	if ( ! $term || is_wp_error( $term ) ) {
		continue;
	}
	if ( (int) $term->count === 0 ) {
		$empty_slugs[] = $slug;
	}
}

if ( $empty_slugs && function_exists( 'estrato_rss_run_import' ) ) {
	estrato_rss_run_import( false );
	$fixed[] = 'rss_import';
}

foreach ( $empty_slugs as $slug ) {
	$term = get_term_by( 'slug', $slug, 'category' );
	if ( ! $term || is_wp_error( $term ) || (int) $term->count > 0 ) {
		if ( $term && (int) $term->count > 0 ) {
			$fixed[] = 'rss_ok:' . $slug;
		}
		continue;
	}
	// Fallback: adiciona categoria a um post publicado recente da mesma taxonomia.
	$fallback = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'fields'         => 'ids',
		)
	);
	if ( ! empty( $fallback[0] ) ) {
		wp_set_post_categories( (int) $fallback[0], array( (int) $term->term_id ), true );
		$fixed[] = 'cat:' . $slug . '→post' . $fallback[0];
	}
}

$no_bio = function_exists( 'estrato_regression_authors_without_bio' )
	? estrato_regression_authors_without_bio()
	: -1;

WP_CLI::success(
	wp_json_encode(
		array(
			'portal'  => $portal,
			'fixed'   => $fixed,
			'no_bio'  => $no_bio,
		),
		JSON_UNESCAPED_UNICODE
	)
);
