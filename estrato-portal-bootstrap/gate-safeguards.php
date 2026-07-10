<?php
/**
 * Sprint 7 — Salvaguardas de gate (sem-categoria, categoria padrão no pipeline).
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @param int $post_id
 */
function estrato_gate_ensure_post_category( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post || 'post' !== $post->post_type || 'publish' !== $post->post_status ) {
		return;
	}

	$cats  = wp_get_post_categories( $post_id );
	$uncat = get_term_by( 'slug', 'sem-categoria', 'category' );
	$needs = empty( $cats );
	if ( ! $needs && $uncat && ! is_wp_error( $uncat ) && in_array( (int) $uncat->term_id, $cats, true ) ) {
		$needs = true;
	}
	if ( ! $needs ) {
		return;
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
		return;
	}

	wp_set_post_categories( $post_id, array( (int) $term->term_id ), false );
}
add_action( 'save_post_post', 'estrato_gate_ensure_post_category', 50 );

/**
 * Posts publicados antes de 2024-01-01 (para AR-SITEMAP-004).
 *
 * @return int
 */
function estrato_regression_pre2024_posts() {
	$ids = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'date_query'     => array(
				array(
					'before'    => '2024-01-01 00:00:00',
					'inclusive' => false,
					'column'    => 'post_date',
				),
			),
		)
	);
	return is_array( $ids ) ? count( $ids ) : 0;
}

/**
 * @param string $category_slug
 * @return string
 */
function estrato_gate_default_category_slug( $category_slug ) {
	if ( '' !== $category_slug ) {
		return $category_slug;
	}
	return 'economia';
}
