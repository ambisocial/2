<?php
/**
 * Yoast defaults — OG 1200×630 + sameAs social (P1 auditoria).
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ESTRATO_OG_DEFAULT_OPTION', 'estrato_og_default_attachment_id' );

/**
 * @return int Attachment ID da imagem OG padrão.
 */
function estrato_yoast_default_og_attachment_id() {
	return (int) get_option( ESTRATO_OG_DEFAULT_OPTION, 0 );
}

/**
 * Fallback OG image quando post não tem thumbnail.
 *
 * @param string $image_url
 * @return string
 */
function estrato_yoast_fallback_og_image( $image_url ) {
	if ( $image_url ) {
		return $image_url;
	}
	$aid = estrato_yoast_default_og_attachment_id();
	if ( $aid ) {
		$url = wp_get_attachment_image_url( $aid, 'full' );
		if ( $url ) {
			return $url;
		}
	}
	return $image_url;
}
add_filter( 'wpseo_opengraph_image', 'estrato_yoast_fallback_og_image', 20 );
add_filter( 'wpseo_twitter_image', 'estrato_yoast_fallback_og_image', 20 );

/**
 * NOTA (V1 visual audit 2026-07-13):
 * O antigo `estrato_yoast_og_dimensions()` ligado a `wpseo_opengraph_image` retornava
 * `URL + <meta ...>` no valor do filtro, que Yoast escreve dentro de
 * `<meta property="og:image" content="…">`. Resultado: og:image virava um
 * blob "URL.pngmeta%20property=og:image:width…" em todos os posts, derrubando
 * compartilhamento social. Removido.
 *
 * Yoast já injeta og:image:width/height automaticamente quando o attachment
 * tem `wp_get_attachment_metadata`. Como fallback (imagem OG default), usamos
 * `wpseo_add_opengraph_images` para adicionar via API oficial com dimensões
 * corretas, evitando qualquer manipulação por concatenação.
 *
 * @param \WPSEO_OpenGraph_Image|null $image_container
 * @return void
 */
function estrato_yoast_register_default_og_image( $image_container ) {
	if ( ! $image_container || ! is_object( $image_container ) ) {
		return;
	}
	if ( ! is_front_page() && ! is_home() && ! is_archive() && ! is_search() && ! is_404() ) {
		if ( is_singular() && has_post_thumbnail() ) {
			return;
		}
	}
	$aid = estrato_yoast_default_og_attachment_id();
	if ( ! $aid ) {
		return;
	}
	$url = wp_get_attachment_image_url( $aid, 'full' );
	if ( ! $url ) {
		return;
	}
	if ( method_exists( $image_container, 'add_image' ) ) {
		$meta   = wp_get_attachment_metadata( $aid );
		$width  = ! empty( $meta['width'] ) ? (int) $meta['width'] : 1200;
		$height = ! empty( $meta['height'] ) ? (int) $meta['height'] : 630;
		$image_container->add_image(
			array(
				'url'    => $url,
				'width'  => $width,
				'height' => $height,
			)
		);
	}
}
add_action( 'wpseo_add_opengraph_images', 'estrato_yoast_register_default_og_image', 15 );

/**
 * sameAs padrão da rede quando Yoast social vazio.
 *
 * @return array<int, string>
 */
function estrato_yoast_default_same_as() {
	$existing = function_exists( 'estrato_seo_get_same_as' ) ? estrato_seo_get_same_as() : array();
	if ( $existing ) {
		return $existing;
	}
	return array_values(
		array_filter(
			array(
				'https://www.linkedin.com/company/estrato-media/',
				'https://x.com/estrato_cc',
				'https://www.instagram.com/estrato.cc/',
			)
		)
	);
}

/**
 * @param array<string, mixed> $data
 * @return array<string, mixed>
 */
function estrato_yoast_organization_same_as( $data ) {
	if ( ! is_array( $data ) ) {
		return $data;
	}
	$same = estrato_yoast_default_same_as();
	if ( $same && empty( $data['sameAs'] ) ) {
		$data['sameAs'] = $same;
	}
	return $data;
}
add_filter( 'wpseo_schema_organization', 'estrato_yoast_organization_same_as', 15 );
