<?php
/**
 * Sprint A1 — Conteúdo mínimo por editoria (satélites).
 *
 * - Recategoriza posts legado finance → editorias do portal
 * - Garante ≥1 post publicado por editoria (parent category)
 * - Enrich thin + backfill thumbnails
 *
 * Uso: ESTRATO_PORTAL=estrato-mind wp eval-file setup-sprint-a1-portal-content.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$portal = getenv( 'ESTRATO_PORTAL' ) ?: '';
if ( '' === $portal && function_exists( 'estrato_portal_get_id' ) ) {
	$portal = estrato_portal_get_id();
}
if ( '' === $portal ) {
	$portal = 'estrato-finance';
}

$loaders = array(
	'estrato-finance'   => 'estrato_rss_load_finance_taxonomy',
	'estrato-mind'      => 'estrato_rss_load_mind_taxonomy',
	'estrato-lifestyle' => 'estrato_rss_load_lifestyle_taxonomy',
	'estrato-science'   => 'estrato_rss_load_science_taxonomy',
	'estrato-sustain'   => 'estrato_rss_load_sustain_taxonomy',
	'estrato-culture'   => 'estrato_rss_load_culture_taxonomy',
);

$loader = $loaders[ $portal ] ?? null;
if ( ! $loader || ! function_exists( $loader ) ) {
	WP_CLI::error( "Loader de taxonomia ausente para {$portal}" );
}

$taxonomy = $loader();
if ( empty( $taxonomy['categories'] ) ) {
	WP_CLI::error( "Taxonomia vazia para {$portal}" );
}

$editorias = $taxonomy['index_order'] ?? array_keys( $taxonomy['categories'] );
$legacy    = array(
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
function estrato_a1_term_id( $slug ) {
	$term = get_term_by( 'slug', $slug, 'category' );
	return ( $term && ! is_wp_error( $term ) ) ? (int) $term->term_id : 0;
}

/**
 * @param int   $post_id
 * @param int[] $term_ids
 */
function estrato_a1_set_categories( $post_id, $term_ids ) {
	$term_ids = array_values( array_unique( array_filter( array_map( 'intval', $term_ids ) ) ) );
	if ( empty( $term_ids ) ) {
		return;
	}
	wp_set_post_categories( $post_id, $term_ids, false );
}

/**
 * @param string               $title
 * @param string               $body
 * @param array<string, mixed> $taxonomy
 * @param string               $parent_slug
 * @return int[]
 */
function estrato_a1_classify( $title, $body, $taxonomy, $parent_slug ) {
	$parent_id = estrato_a1_term_id( $parent_slug );
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
function estrato_a1_best_editoria( $title, $body, $taxonomy, $editorias ) {
	$best     = array();
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
				$best      = estrato_a1_classify( $title, $body, $taxonomy, $slug );
			}
		}
		$sub_cats = estrato_a1_classify( $title, $body, $taxonomy, $slug );
		if ( count( $sub_cats ) > 1 ) {
			return $sub_cats;
		}
	}
	if ( ! empty( $best ) ) {
		return $best;
	}
	$fallback = $editorias[0] ?? '';
	return $fallback ? estrato_a1_classify( $title, $body, $taxonomy, $fallback ) : array();
}

$recat       = 0;
$parent_add  = 0;
$filled      = 0;
$enriched    = 0;
$thumbs      = 0;

$post_ids = get_posts(
	array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);

foreach ( $post_ids as $post_id ) {
	$post     = get_post( $post_id );
	$title    = $post->post_title;
	$body     = $post->post_content;
	$cat_ids  = wp_get_post_categories( $post_id );
	$slugs    = array();
	foreach ( $cat_ids as $cid ) {
		$t = get_term( $cid, 'category' );
		if ( $t && ! is_wp_error( $t ) ) {
			$slugs[] = $t->slug;
		}
	}

	$in_portal = false;
	foreach ( $slugs as $s ) {
		if ( in_array( $s, $editorias, true ) ) {
			$in_portal = true;
			break;
		}
		foreach ( $editorias as $ed ) {
			if ( 0 === strpos( $s, $ed . '-' ) ) {
				$in_portal = true;
				break 2;
			}
		}
	}

	if ( ! $in_portal ) {
		$new = estrato_a1_best_editoria( $title, $body, $taxonomy, $editorias );
		if ( ! empty( $new ) ) {
			estrato_a1_set_categories( $post_id, $new );
			++$recat;
		}
		continue;
	}

	$parent_ids = array();
	foreach ( $editorias as $ed ) {
		$pid = estrato_a1_term_id( $ed );
		if ( ! $pid ) {
			continue;
		}
		foreach ( $slugs as $s ) {
			if ( $s === $ed || 0 === strpos( $s, $ed . '-' ) ) {
				$parent_ids[] = $pid;
			}
		}
	}
	if ( ! empty( $parent_ids ) && ! array_intersect( $parent_ids, $cat_ids ) ) {
		estrato_a1_set_categories( $post_id, array_merge( $cat_ids, $parent_ids ) );
		++$parent_add;
	}
}

foreach ( $editorias as $slug ) {
	$parent_id = estrato_a1_term_id( $slug );
	if ( ! $parent_id ) {
		continue;
	}
	$count = count(
		get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'category'       => $parent_id,
				'fields'         => 'ids',
			)
		)
	);
	if ( $count > 0 ) {
		continue;
	}
	foreach ( $post_ids as $post_id ) {
		$post  = get_post( $post_id );
		$new   = estrato_a1_classify( $post->post_title, $post->post_content, $taxonomy, $slug );
		if ( empty( $new ) ) {
			$new = array( $parent_id );
		}
		$merged = array_unique( array_merge( wp_get_post_categories( $post_id ), $new ) );
		estrato_a1_set_categories( $post_id, $merged );
		++$filled;
		break;
	}
}

if ( function_exists( 'estrato_content_enrich_post' ) && ! defined( 'ESTRATO_ENRICHING' ) ) {
	foreach ( $post_ids as $post_id ) {
		if ( function_exists( 'estrato_content_post_word_count' )
			&& estrato_content_post_word_count( $post_id ) < ( defined( 'ESTRATO_MIN_PUBLISH_WORDS' ) ? ESTRATO_MIN_PUBLISH_WORDS : 200 ) ) {
			$r = estrato_content_enrich_post( (int) $post_id );
			if ( ! empty( $r['updated'] ) ) {
				++$enriched;
			}
		}
	}
}

if ( function_exists( 'estrato_bridge_backfill_featured_images' ) ) {
	$rounds = 0;
	while ( $rounds < 10 ) {
		$stats = estrato_bridge_backfill_featured_images( 50 );
		$thumbs += (int) ( $stats['set'] ?? 0 );
		++$rounds;
		if ( (int) ( $stats['processed'] ?? 0 ) < 1 ) {
			break;
		}
	}
}
if ( function_exists( 'estrato_bridge_set_fallback_thumbnail' ) ) {
	foreach ( $post_ids as $post_id ) {
		if ( ! has_post_thumbnail( $post_id ) && estrato_bridge_set_fallback_thumbnail( $post_id ) ) {
			++$thumbs;
		}
	}
}

$report = array();
foreach ( $editorias as $slug ) {
	$pid = estrato_a1_term_id( $slug );
	$report[ $slug ] = $pid ? count(
		get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'category'       => $pid,
				'fields'         => 'ids',
			)
		)
	) : 0;
}

$thin = function_exists( 'estrato_regression_thin_posts' ) ? estrato_regression_thin_posts() : -1;
$no_t = function_exists( 'estrato_regression_posts_without_thumbnail' ) ? estrato_regression_posts_without_thumbnail() : -1;

WP_CLI::success(
	wp_json_encode(
		array(
			'portal'      => $portal,
			'recat'       => $recat,
			'parent_add'  => $parent_add,
			'filled'      => $filled,
			'enriched'    => $enriched,
			'thumbs'      => $thumbs,
			'editorias'   => $report,
			'thin'        => $thin,
			'no_thumb'    => $no_t,
		)
	)
);
