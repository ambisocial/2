<?php
/**
 * Remove categorias legado do financeiro em portais satélite e recategoriza.
 *
 * Uso: ESTRATO_PORTAL=estrato-mind wp eval-file cleanup-satellite-legacy-categories.php
 *      ESTRATO_DRY_RUN=1 wp eval-file cleanup-satellite-legacy-categories.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$dry_run = '1' === getenv( 'ESTRATO_DRY_RUN' );

$portal = getenv( 'ESTRATO_PORTAL' ) ?: '';
if ( '' === $portal ) {
	$host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
	$by_domain = array(
		'mente.estrato.cc'     => 'estrato-mind',
		'lifestyle.estrato.cc' => 'estrato-lifestyle',
		'science.estrato.cc'   => 'estrato-science',
		'sustain.estrato.cc'   => 'estrato-sustain',
		'culture.estrato.cc'   => 'estrato-culture',
	);
	$portal = $by_domain[ $host ] ?? '';
}

if ( 'estrato-finance' === $portal || '' === $portal ) {
	WP_CLI::error( 'Script apenas para satélites (estrato-mind, lifestyle, science, sustain, culture).' );
}

$loaders = array(
	'estrato-mind'      => 'estrato_rss_load_mind_taxonomy',
	'estrato-lifestyle' => 'estrato_rss_load_lifestyle_taxonomy',
	'estrato-science'   => 'estrato_rss_load_science_taxonomy',
	'estrato-sustain'   => 'estrato_rss_load_sustain_taxonomy',
	'estrato-culture'   => 'estrato_rss_load_culture_taxonomy',
);

$loader = $loaders[ $portal ] ?? null;
if ( ! $loader || ! function_exists( $loader ) ) {
	WP_CLI::error( "Taxonomia ausente para {$portal}" );
}

$taxonomy  = $loader();
$editorias = $taxonomy['index_order'] ?? array_keys( $taxonomy['categories'] ?? array() );

$finance_legacy = array(
	'economia',
	'mercados',
	'negocios',
	'financas-pessoais',
	'criptomoedas',
	'agronegocio',
	'mundo',
	'politica',
	'tecnologia',
	'brasil',
	'sem-categoria',
);

/**
 * @param string $slug
 * @return int
 */
function estrato_cleanup_term_id( $slug ) {
	$term = get_term_by( 'slug', $slug, 'category' );
	return ( $term && ! is_wp_error( $term ) ) ? (int) $term->term_id : 0;
}

/**
 * @param string $slug
 * @param string[] $editorias
 * @return bool
 */
function estrato_cleanup_is_portal_slug( $slug, $editorias ) {
	if ( in_array( $slug, $editorias, true ) ) {
		return true;
	}
	foreach ( $editorias as $ed ) {
		if ( 0 === strpos( $slug, $ed . '-' ) ) {
			return true;
		}
	}
	return false;
}

/**
 * @param string               $title
 * @param string               $body
 * @param array<string, mixed> $taxonomy
 * @param string               $parent_slug
 * @return int[]
 */
function estrato_cleanup_classify( $title, $body, $taxonomy, $parent_slug ) {
	$parent_id = estrato_cleanup_term_id( $parent_slug );
	if ( ! $parent_id ) {
		return array();
	}
	$sub_id = 0;
	if ( function_exists( 'estrato_rss_match_subcategory_term' ) ) {
		$sub_id = estrato_rss_match_subcategory_term( $parent_id, $title, $body, $taxonomy, $parent_slug );
	}
	$cats = array( $parent_id );
	if ( $sub_id ) {
		$cats[] = $sub_id;
	}
	return $cats;
}

/**
 * @param string               $title
 * @param string               $body
 * @param array<string, mixed> $taxonomy
 * @param string[]             $editorias
 * @return int[]
 */
function estrato_cleanup_best_editoria( $title, $body, $taxonomy, $editorias ) {
	$best      = array();
	$best_hits = 0;
	foreach ( $editorias as $slug ) {
		$cat = $taxonomy['categories'][ $slug ] ?? array();
		if ( empty( $cat ) ) {
			continue;
		}
		$keywords = function_exists( 'estrato_taxonomy_resolve_keywords' )
			? estrato_taxonomy_resolve_keywords( $taxonomy, $cat )
			: ( $cat['keywords'] ?? array() );
		if ( function_exists( 'estrato_rss_item_matches_keywords' )
			&& estrato_rss_item_matches_keywords( $title, $body, $keywords ) ) {
			$include = $keywords['include'] ?? array();
			$hits    = empty( $include ) ? 1 : count( $include );
			if ( $hits >= $best_hits ) {
				$best_hits = $hits;
				$best      = estrato_cleanup_classify( $title, $body, $taxonomy, $slug );
			}
		}
		$sub_cats = estrato_cleanup_classify( $title, $body, $taxonomy, $slug );
		if ( count( $sub_cats ) > 1 ) {
			return $sub_cats;
		}
	}
	if ( ! empty( $best ) ) {
		return $best;
	}
	$fallback = $editorias[0] ?? '';
	return $fallback ? estrato_cleanup_classify( $title, $body, $taxonomy, $fallback ) : array();
}

$legacy_ids = array();
foreach ( $finance_legacy as $slug ) {
	$id = estrato_cleanup_term_id( $slug );
	if ( $id ) {
		$legacy_ids[ $slug ] = $id;
	}
}

$stripped = 0;
$recat    = 0;
$trashed  = 0;

foreach (
	get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	) as $post_id
) {
	$post    = get_post( $post_id );
	$cat_ids = wp_get_post_categories( $post_id );
	$slugs   = array();
	foreach ( $cat_ids as $cid ) {
		$t = get_term( $cid, 'category' );
		if ( $t && ! is_wp_error( $t ) ) {
			$slugs[ (int) $t->term_id ] = $t->slug;
		}
	}

	$has_legacy  = false;
	$has_portal  = false;
	$legacy_found = array();
	foreach ( $slugs as $tid => $slug ) {
		if ( isset( $legacy_ids[ $slug ] ) || in_array( $slug, $finance_legacy, true ) ) {
			$has_legacy    = true;
			$legacy_found[] = (int) $tid;
		}
		if ( estrato_cleanup_is_portal_slug( $slug, $editorias ) ) {
			$has_portal = true;
		}
	}

	if ( ! $has_legacy ) {
		continue;
	}

	$new_cats = array_diff( $cat_ids, $legacy_found );
	$new_cats = array_values( array_map( 'intval', $new_cats ) );

	if ( empty( $new_cats ) || ! $has_portal ) {
		$assigned = estrato_cleanup_best_editoria( $post->post_title, $post->post_content, $taxonomy, $editorias );
		if ( empty( $assigned ) ) {
			if ( $dry_run ) {
				WP_CLI::log( "[dry-run] trash #$post_id sem editoria: {$post->post_title}" );
			} else {
				wp_trash_post( (int) $post_id );
			}
			++$trashed;
			continue;
		}
		$new_cats = $assigned;
		++$recat;
	} else {
		++$stripped;
	}

	if ( $dry_run ) {
		$names = array();
		foreach ( $new_cats as $cid ) {
			$t = get_term( $cid, 'category' );
			$names[] = $t && ! is_wp_error( $t ) ? $t->slug : (string) $cid;
		}
		WP_CLI::log( "[dry-run] #$post_id → " . implode( ',', $names ) );
		continue;
	}

	wp_set_post_categories( (int) $post_id, $new_cats, false );
}

$remaining = 0;
foreach ( $finance_legacy as $slug ) {
	$tid = estrato_cleanup_term_id( $slug );
	if ( ! $tid ) {
		continue;
	}
	$remaining += count(
		get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'category'       => $tid,
				'fields'         => 'ids',
			)
		)
	);
}

$mode = $dry_run ? 'DRY-RUN' : 'APPLIED';
WP_CLI::success(
	wp_json_encode(
		array(
			'portal'           => $portal,
			'mode'             => $mode,
			'stripped'         => $stripped,
			'recat'            => $recat,
			'trashed'          => $trashed,
			'legacy_remaining' => $remaining,
		)
	)
);
