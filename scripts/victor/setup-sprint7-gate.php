<?php
/**
 * Sprint 7 — Gate 100%: limpeza final e salvaguardas anti-regressão.
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$dry_run = '1' === getenv( 'ESTRATO_DRY_RUN' );

/**
 * Garante categoria financeira em posts publicados sem categoria.
 *
 * @param int $post_id
 * @return bool
 */
function estrato_gate_fix_uncategorized_post( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post || 'post' !== $post->post_type || 'publish' !== $post->post_status ) {
		return false;
	}

	$cats = wp_get_post_categories( $post_id );
	if ( ! empty( $cats ) ) {
		$uncat = get_term_by( 'slug', 'sem-categoria', 'category' );
		if ( ! $uncat || is_wp_error( $uncat ) || ! in_array( (int) $uncat->term_id, $cats, true ) || count( $cats ) > 1 ) {
			return false;
		}
	} elseif ( empty( $cats ) ) {
		// Sem nenhuma categoria.
	} else {
		return false;
	}

	$haystack = strtolower( $post->post_title . ' ' . wp_strip_all_tags( $post->post_content ) );
	$rules    = array(
		'criptomoedas'      => '/\b(bitcoin|btc|ethereum|cripto|blockchain)\b/iu',
		'agronegocio'       => '/\b(agroneg[oó]cio|soja|safra|pecu[aá]ria)\b/iu',
		'mercados'          => '/\b(ibovespa|bolsa|b3|d[oó]lar|selic|juros|cdi)\b/iu',
		'financas-pessoais' => '/\b(cart[aã]o|consignado|inss|aposentadoria)\b/iu',
		'negocios'          => '/\b(empresa|startup|fintech|petrobras|vale\b)\b/iu',
		'economia'          => '/\b(pib|infla[cç][aã]o|ipca|fiscal|imposto)\b/iu',
		'mundo'             => '/\b(eua|europa|china|internacional)\b/iu',
	);
	$target   = 'economia';
	foreach ( $rules as $slug => $pattern ) {
		if ( preg_match( $pattern, $haystack ) ) {
			$target = $slug;
			break;
		}
	}

	$term = get_term_by( 'slug', $target, 'category' );
	if ( ! $term || is_wp_error( $term ) ) {
		return false;
	}

	if ( $dry_run ) {
		echo "[dry-run] #{$post_id} sem-categoria → {$target}\n";
		return true;
	}

	wp_set_post_categories( $post_id, array( (int) $term->term_id ), false );
	return true;
}

$fixed_cat = 0;
foreach ( get_posts(
	array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
) as $post_id ) {
	if ( estrato_gate_fix_uncategorized_post( $post_id ) ) {
		++$fixed_cat;
	}
}

$pre2024 = 0;
foreach ( get_posts(
	array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'date_query'     => array(
			array(
				'before'    => '2024-01-01 00:00:00',
				'inclusive' => false,
			),
		),
	)
) as $post_id ) {
	++$pre2024;
	if ( $dry_run ) {
		echo "[dry-run] #{$post_id} pré-2024 → draft+noindex\n";
		continue;
	}
	update_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', '1' );
	wp_update_post(
		array(
			'ID'          => $post_id,
			'post_status' => 'draft',
		)
	);
}

if ( ! $dry_run && class_exists( 'WPSEO_Sitemaps_Cache' ) ) {
	WPSEO_Sitemaps_Cache::clear();
}

$uncat = get_term_by( 'slug', 'sem-categoria', 'category' );
$remaining = 0;
if ( $uncat && ! is_wp_error( $uncat ) ) {
	$remaining = count(
		get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'category'       => (int) $uncat->term_id,
				'fields'         => 'ids',
			)
		)
	);
}

$mode = $dry_run ? 'DRY-RUN' : 'APPLIED';
echo "{$mode}: recategorizados={$fixed_cat}, pré-2024 noindex={$pre2024}, sem-categoria restantes={$remaining}\n";
