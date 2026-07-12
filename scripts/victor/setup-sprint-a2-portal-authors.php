<?php
/**
 * Sprint A2 — Autores & bios por portal (satélites).
 *
 * - Sync taxonomia + autores por subcategoria
 * - Autores de editoria (parent) com bio
 * - Reatribui todos os posts a autores com bio
 *
 * Uso: ESTRATO_PORTAL=estrato-mind wp eval-file setup-sprint-a2-portal-authors.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$portal = getenv( 'ESTRATO_PORTAL' ) ?: '';
if ( '' === $portal ) {
	$host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
	$by_domain = array(
		'estrato.cc'           => 'estrato-finance',
		'mente.estrato.cc'     => 'estrato-mind',
		'lifestyle.estrato.cc' => 'estrato-lifestyle',
		'science.estrato.cc'   => 'estrato-science',
		'sustain.estrato.cc'   => 'estrato-sustain',
		'culture.estrato.cc'   => 'estrato-culture',
	);
	$portal = $by_domain[ $host ] ?? 'estrato-finance';
}

$presets = array(
	'estrato-finance'   => 'brasil-financeiro',
	'estrato-mind'      => 'brasil-mind',
	'estrato-lifestyle' => 'brasil-lifestyle',
	'estrato-science'   => 'brasil-science',
	'estrato-sustain'   => 'brasil-sustain',
	'estrato-culture'   => 'brasil-culture',
);

$loaders = array(
	'estrato-finance'   => 'estrato_rss_load_finance_taxonomy',
	'estrato-mind'      => 'estrato_rss_load_mind_taxonomy',
	'estrato-lifestyle' => 'estrato_rss_load_lifestyle_taxonomy',
	'estrato-science'   => 'estrato_rss_load_science_taxonomy',
	'estrato-sustain'   => 'estrato_rss_load_sustain_taxonomy',
	'estrato-culture'   => 'estrato_rss_load_culture_taxonomy',
);

$preset   = $presets[ $portal ] ?? 'brasil-financeiro';
$loader   = $loaders[ $portal ] ?? 'estrato_rss_load_finance_taxonomy';
$taxonomy = function_exists( $loader ) ? $loader() : array();

if ( empty( $taxonomy['categories'] ) ) {
	WP_CLI::error( "Taxonomia ausente para {$portal}" );
}

$sync = array( 'skipped' => true );
if ( function_exists( 'estrato_rss_sync_portal_taxonomy' ) ) {
	$sync = estrato_rss_sync_portal_taxonomy( $preset );
}

$editoria_authors = 0;
$cat_author_map   = array();

foreach ( $taxonomy['categories'] as $parent_slug => $cat ) {
	if ( ! is_array( $cat ) ) {
		continue;
	}
	$term = get_term_by( 'slug', $parent_slug, 'category' );
	if ( ! $term || is_wp_error( $term ) ) {
		continue;
	}
	if ( function_exists( 'estrato_eeat_ensure_author_for_term' ) ) {
		$uid = estrato_eeat_ensure_author_for_term(
			(int) $term->term_id,
			$parent_slug,
			$parent_slug,
			'editoria',
			$cat,
			'subcategory'
		);
		if ( $uid ) {
			$cat_author_map[ $parent_slug ] = (int) $uid;
			++$editoria_authors;
		}
	}
}

if ( ! empty( $cat_author_map ) ) {
	$existing = get_option( 'estrato_category_author_map', array() );
	if ( ! is_array( $existing ) ) {
		$existing = array();
	}
	update_option( 'estrato_category_author_map', array_merge( $existing, $cat_author_map ), false );
}

$reatrib = 0;
$post_ids = get_posts(
	array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);

if ( function_exists( 'estrato_eeat_resolve_author_for_post' ) ) {
	foreach ( $post_ids as $post_id ) {
		$new_author = estrato_eeat_resolve_author_for_post( (int) $post_id );
		if ( $new_author && (int) get_post_field( 'post_author', $post_id ) !== $new_author ) {
			wp_update_post(
				array(
					'ID'          => (int) $post_id,
					'post_author' => (int) $new_author,
				)
			);
			++$reatrib;
		}
	}
}

/**
 * @param int $user_id
 * @return bool
 */
function estrato_a2_user_has_bio( $user_id ) {
	$bio = get_user_meta( (int) $user_id, 'description', true );
	if ( is_string( $bio ) && '' !== trim( $bio ) ) {
		return true;
	}
	$user = get_user_by( 'id', (int) $user_id );
	return $user && is_string( $user->description ) && '' !== trim( $user->description );
}

/**
 * @return int
 */
function estrato_a2_first_author_with_bio() {
	$users = get_users(
		array(
			'role'   => 'author',
			'number' => 50,
			'fields' => array( 'ID' ),
		)
	);
	foreach ( $users as $user ) {
		if ( estrato_a2_user_has_bio( (int) $user->ID ) ) {
			return (int) $user->ID;
		}
	}
	return 0;
}

$fallback_author = estrato_a2_first_author_with_bio();
$forced          = 0;

foreach ( $post_ids as $post_id ) {
	$author_id = (int) get_post_field( 'post_author', $post_id );
	if ( estrato_a2_user_has_bio( $author_id ) ) {
		continue;
	}
	$replacement = 0;
	if ( function_exists( 'estrato_eeat_resolve_author_for_post' ) ) {
		$replacement = (int) estrato_eeat_resolve_author_for_post( (int) $post_id );
	}
	if ( ! $replacement || ! estrato_a2_user_has_bio( $replacement ) ) {
		$replacement = $fallback_author;
	}
	if ( $replacement && $replacement !== $author_id ) {
		wp_update_post(
			array(
				'ID'          => (int) $post_id,
				'post_author' => $replacement,
			)
		);
		++$forced;
	}
}

$authors_role = count(
	get_users(
		array(
			'role'   => 'author',
			'fields' => 'ID',
		)
	)
);
$no_bio = function_exists( 'estrato_regression_authors_without_bio' )
	? estrato_regression_authors_without_bio()
	: -1;

WP_CLI::success(
	wp_json_encode(
		array(
			'portal'           => $portal,
			'preset'           => $preset,
			'sync_ok'          => ! empty( $sync['ok'] ),
			'editoria_authors' => $editoria_authors,
			'reatrib'          => $reatrib,
			'forced'           => $forced,
			'authors_role'     => $authors_role,
			'no_bio_posts'     => $no_bio,
			'term_authors'     => function_exists( 'estrato_regression_term_authors_count' )
				? estrato_regression_term_authors_count()
				: 0,
		)
	)
);
