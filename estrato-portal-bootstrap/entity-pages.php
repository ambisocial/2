<?php
/**
 * Entity pages (/tudo-sobre/) — amplia hubs evergreen para todos os portais.
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Slug de hub a partir da editoria.
 *
 * @param string $category_slug
 * @return string
 */
function estrato_entity_hub_slug( $category_slug ) {
	$pos = strpos( $category_slug, '-' );
	return false === $pos ? sanitize_title( $category_slug ) : sanitize_title( substr( $category_slug, 0, $pos ) );
}

/**
 * Conteúdo base da entity page.
 *
 * @param string $hub_slug
 * @param string $category_slug
 * @param string $title
 * @return string
 */
function estrato_entity_hub_content( $hub_slug, $category_slug, $title ) {
	$blog = get_bloginfo( 'name' );
	$term = get_term_by( 'slug', $category_slug, 'category' );
	$desc = ( $term && ! is_wp_error( $term ) && $term->description )
		? wp_strip_all_tags( $term->description )
		: sprintf( 'Guia evergreen sobre %s no %s, com contexto, perguntas frequentes e cobertura contínua.', $title, $blog );

	$html  = '<section class="estrato-entity-hub">';
	$html .= '<h1 class="estrato-display">Tudo sobre ' . esc_html( $title ) . '</h1>';
	$html .= '<p class="estrato-entity-hub__lead">' . esc_html( $desc ) . '</p>';
	$html .= '<h2>Por que esta página existe</h2>';
	$html .= '<p>Centralizamos definições, atualizações e links internos para fortalecer a compreensão da entidade <strong>'
		. esc_html( $title ) . '</strong> — padrão AEO/GEO da rede Estrato.</p>';
	$html .= '<h2>Últimas matérias</h2>';
	$html .= '[estrato_hub_posts category="' . esc_attr( $category_slug ) . '" count="8"]';
	$html .= '<h2>Transparência</h2>';
	$html .= '<p>Cobertura informativa com revisão editorial. Veja <a href="' . esc_url( home_url( '/metodologia/' ) )
		. '">metodologia</a> e <a href="' . esc_url( home_url( '/equipe/' ) ) . '">equipe</a>.</p>';
	$html .= '</section>';
	return $html;
}

/**
 * Upsert page helper.
 *
 * @param string $slug
 * @param string $title
 * @param string $content
 * @param int    $parent
 * @return int
 */
function estrato_entity_upsert_page( $slug, $title, $content, $parent = 0 ) {
	$existing = null;
	if ( $parent ) {
		$kids = get_children(
			array(
				'post_parent' => $parent,
				'post_type'   => 'page',
				'post_status' => 'any',
				'name'        => $slug,
			)
		);
		$existing = $kids ? reset( $kids ) : null;
	} else {
		$existing = get_page_by_path( $slug, OBJECT, 'page' );
	}

	$data = array(
		'post_title'   => $title,
		'post_name'    => $slug,
		'post_content' => $content,
		'post_status'  => 'publish',
		'post_type'    => 'page',
		'post_parent'  => $parent,
	);

	if ( $existing ) {
		// Não sobrescreve conteúdo editorial rico já customizado.
		$marker = 'estrato-entity-hub';
		if ( false === strpos( (string) $existing->post_content, $marker )
			&& false === strpos( (string) $existing->post_content, 'estrato_hub_posts' ) ) {
			$data['ID'] = $existing->ID;
			$id         = wp_update_post( $data, true );
			return is_wp_error( $id ) ? 0 : (int) $id;
		}
		return (int) $existing->ID;
	}

	$id = wp_insert_post( $data, true );
	return is_wp_error( $id ) ? 0 : (int) $id;
}

/**
 * Garante /tudo-sobre/ + hubs por editoria (e hubs financeiros clássicos no finance).
 *
 * @return array<string,int>
 */
function estrato_entity_ensure_all_hubs() {
	$parent_id = estrato_entity_upsert_page(
		'tudo-sobre',
		'Tudo sobre',
		'<p>Guias evergreen e páginas de entidade da redação. Cada hub reúne definição, FAQ e cobertura recente.</p>'
	);
	if ( ! $parent_id ) {
		return array();
	}

	$created   = array( 'tudo-sobre' => $parent_id );
	$editorias = function_exists( 'estrato_aeo_portal_editorias' ) ? estrato_aeo_portal_editorias() : array();
	if ( ! $editorias ) {
		$terms = get_terms(
			array(
				'taxonomy'   => 'category',
				'parent'     => 0,
				'hide_empty' => false,
				'number'     => 12,
			)
		);
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$editorias[] = $term->slug;
			}
		}
	}
	$seen = array();

	foreach ( $editorias as $cat_slug ) {
		$hub = estrato_entity_hub_slug( $cat_slug );
		if ( isset( $seen[ $hub ] ) ) {
			continue;
		}
		$seen[ $hub ] = true;
		$term         = get_term_by( 'slug', $cat_slug, 'category' );
		$title        = ( $term && ! is_wp_error( $term ) )
			? html_entity_decode( (string) $term->name, ENT_QUOTES, 'UTF-8' )
			: ucwords( str_replace( '-', ' ', $hub ) );
		$id = estrato_entity_upsert_page(
			$hub,
			'Tudo sobre ' . $title,
			estrato_entity_hub_content( $hub, $cat_slug, $title ),
			$parent_id
		);
		if ( $id ) {
			$created[ $hub ] = $id;
		}
	}

	$portal = function_exists( 'estrato_nav_current_portal_id' ) ? estrato_nav_current_portal_id() : '';
	if ( 'estrato-finance' === $portal ) {
		$finance_hubs = array(
			'selic'       => array( 'Selic', 'economia' ),
			'ibovespa'    => array( 'Ibovespa', 'mercados' ),
			'dolar'       => array( 'Dólar', 'mercados' ),
			'cripto'      => array( 'Criptomoedas', 'criptomoedas' ),
			'inflacao'    => array( 'Inflação', 'economia' ),
			'tributacao'  => array( 'Tributação', 'economia' ),
			'agronegocio' => array( 'Agronegócio', 'agronegocio' ),
		);
		foreach ( $finance_hubs as $hub => $pair ) {
			if ( isset( $created[ $hub ] ) ) {
				continue;
			}
			$id = estrato_entity_upsert_page(
				$hub,
				'Tudo sobre ' . $pair[0],
				estrato_entity_hub_content( $hub, $pair[1], $pair[0] ),
				$parent_id
			);
			if ( $id ) {
				$created[ $hub ] = $id;
			}
		}
	}

	update_option( 'estrato_entity_hubs_map', $created, false );
	return $created;
}

/**
 * Schema CollectionPage extra nas entity pages.
 */
function estrato_entity_schema_head() {
	if ( ! is_page() ) {
		return;
	}
	$post = get_queried_object();
	if ( ! $post ) {
		return;
	}
	$parent_slug = $post->post_parent ? get_post_field( 'post_name', $post->post_parent ) : '';
	if ( 'tudo-sobre' !== $parent_slug && 'tudo-sobre' !== $post->post_name ) {
		return;
	}

	$schema = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'CollectionPage',
		'name'        => get_the_title( $post ),
		'url'         => get_permalink( $post ),
		'inLanguage'  => 'pt-BR',
		'isPartOf'    => array(
			'@type' => 'WebSite',
			'name'  => get_bloginfo( 'name' ),
			'url'   => home_url( '/' ),
		),
		'about'       => array(
			'@type' => 'Thing',
			'name'  => get_the_title( $post ),
		),
		'description' => wp_trim_words( wp_strip_all_tags( $post->post_content ), 40, '…' ),
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}
add_action( 'wp_head', 'estrato_entity_schema_head', 6 );

/**
 * @return int
 */
function estrato_regression_entity_hub_count() {
	$map = get_option( 'estrato_entity_hubs_map', array() );
	return is_array( $map ) ? max( 0, count( $map ) - 1 ) : 0;
}
