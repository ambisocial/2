<?php
/**
 * Categorização determinística — política internacional vs mercados (Sprint 1 / B7).
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Regras de redirecionamento para editoria mundo (Internacional).
 *
 * @return array<int, array{pattern:string,slug:string,priority:int}>
 */
function estrato_pipeline_categorization_rules() {
	$rules = array(
		array(
			'pattern'  => '/\b(israel|gaza|hamas|netanyahu|zelensky|ucrânia|ucrania|ucraniano|premiê|premie|rússia|russia|putin|nato|otan)\b/iu',
			'slug'     => 'mundo',
			'priority' => 100,
		),
		array(
			'pattern'  => '/\b(eleição|eleicoes|eleições|congresso\s+aprova|senado\s+americano|house\s+of\s+representatives|white\s+house|presidente\s+dos\s+eua|trump|biden|lindsey\s+graham)\b/iu',
			'slug'     => 'mundo',
			'priority' => 90,
		),
		array(
			'pattern'  => '/\b(guerra|conflito|ataque|morte\s+de|assassinato|diplomacia|embaixada|sanções|sancoes|geopolítica|geopolitica)\b/iu',
			'slug'     => 'mundo',
			'priority' => 80,
		),
		array(
			'pattern'  => '/\b(ibovespa|b3\b|selic\b|cdi\b|câmbio|cambio|dólar|dolar|juros\b|bolsa\b|ações\b|acoes\b|renda\s+fixa)\b/iu',
			'slug'     => 'mercados',
			'priority' => 70,
		),
		array(
			'pattern'  => '/\b(pib\b|inflação|inflacao|ipca\b|fiscal|imposto|orçamento|orcamento|bc\b|banco\s+central)\b/iu',
			'slug'     => 'economia',
			'priority' => 60,
		),
		array(
			'pattern'  => '/\b(bitcoin|btc\b|ethereum|cripto|blockchain)\b/iu',
			'slug'     => 'criptomoedas',
			'priority' => 55,
		),
		array(
			'pattern'  => '/\b(agroneg[oó]cio|soja|safra|pecu[aá]ria|embarque|exportação|exportacao)\b/iu',
			'slug'     => 'agronegocio',
			'priority' => 50,
		),
	);
	return apply_filters( 'estrato_pipeline_categorization_rules', $rules );
}

/**
 * @param string $title
 * @param string $content
 * @return string Slug da editoria ou vazio.
 */
function estrato_pipeline_resolve_editoria_slug( $title, $content = '' ) {
	$haystack = $title . ' ' . wp_strip_all_tags( $content );
	$best     = array(
		'slug'     => '',
		'priority' => -1,
	);
	foreach ( estrato_pipeline_categorization_rules() as $rule ) {
		if ( empty( $rule['pattern'] ) || empty( $rule['slug'] ) ) {
			continue;
		}
		if ( preg_match( $rule['pattern'], $haystack ) ) {
			$prio = (int) ( $rule['priority'] ?? 0 );
			if ( $prio > $best['priority'] ) {
				$best = array(
					'slug'     => sanitize_key( $rule['slug'] ),
					'priority' => $prio,
				);
			}
		}
	}
	return $best['slug'];
}

/**
 * @param int    $post_id
 * @param string $title
 * @param string $content
 * @return bool
 */
function estrato_pipeline_apply_category( $post_id, $title, $content = '' ) {
	$slug = estrato_pipeline_resolve_editoria_slug( $title, $content );
	if ( '' === $slug ) {
		return false;
	}
	$term = get_term_by( 'slug', $slug, 'category' );
	if ( ! $term || is_wp_error( $term ) ) {
		return false;
	}
	$cats = wp_get_post_categories( $post_id );
	if ( in_array( (int) $term->term_id, $cats, true ) && 1 === count( $cats ) ) {
		return false;
	}
	$wrong_parents = array( 'economia', 'mercados', 'negocios' );
	if ( 'mundo' === $slug ) {
		foreach ( $cats as $cat_id ) {
			$cat = get_term( $cat_id, 'category' );
			if ( $cat && ! is_wp_error( $cat ) && in_array( $cat->slug, $wrong_parents, true ) ) {
				wp_set_post_categories( $post_id, array( (int) $term->term_id ), false );
				return true;
			}
		}
	}
	if ( empty( $cats ) || ( 1 === count( $cats ) && in_array( $cats[0], array( 1 ), true ) ) ) {
		wp_set_post_categories( $post_id, array( (int) $term->term_id ), false );
		return true;
	}
	return false;
}

/**
 * @param int $post_id
 */
function estrato_pipeline_categorize_on_save( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	$post = get_post( $post_id );
	if ( ! $post || 'post' !== $post->post_type || 'publish' !== $post->post_status ) {
		return;
	}
	estrato_pipeline_apply_category( $post_id, $post->post_title, $post->post_content );
}
add_action( 'save_post_post', 'estrato_pipeline_categorize_on_save', 45 );
