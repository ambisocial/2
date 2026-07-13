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
 * Garante dimensões OG no output.
 *
 * @param string $tag
 * @return string
 */
function estrato_yoast_og_dimensions( $tag ) {
	if ( ! is_singular() && ! is_front_page() && ! is_home() ) {
		return $tag;
	}
	if ( false !== strpos( $tag, 'og:image:width' ) ) {
		return $tag;
	}
	$aid = estrato_yoast_default_og_attachment_id();
	if ( ! $aid && has_post_thumbnail() ) {
		$aid = get_post_thumbnail_id();
	}
	if ( $aid ) {
		$meta = wp_get_attachment_metadata( $aid );
		if ( ! empty( $meta['width'] ) && ! empty( $meta['height'] ) ) {
			return $tag . sprintf(
				'<meta property="og:image:width" content="%d" />' . "\n" . '<meta property="og:image:height" content="%d" />' . "\n",
				(int) $meta['width'],
				(int) $meta['height']
			);
		}
	}
	return $tag . '<meta property="og:image:width" content="1200" />' . "\n" . '<meta property="og:image:height" content="630" />' . "\n";
}
add_filter( 'wpseo_opengraph_image', 'estrato_yoast_og_dimensions', 99 );

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
