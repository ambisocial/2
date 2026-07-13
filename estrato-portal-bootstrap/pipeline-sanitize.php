<?php
/**
 * Sanitização de pipeline — hotlinks, paywall e gate de idioma (Sprint 1 / B3–B4–B8).
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ESTRATO_PAYWALL_PATTERNS', '/(?:exclusiva\s+para\s+assinantes|matéria\s+exclusiva|faça\s+seu\s+cadastro|acesse\s+o\s+link\s+para\s+ler|conteúdo\s+exclusivo\s+para\s+assinantes|subscribe\s+to\s+read|sign\s+in\s+to\s+continue|this\s+article\s+is\s+for\s+subscribers)/iu' );

define( 'ESTRATO_EXTERNAL_IMAGE_HOSTS', '/(?:glbimg\.com|globo\.com\/multimedia|i\.imgur\.com|pbs\.twimg\.com)/i' );

/**
 * Domínios permitidos para imagens inline (além do próprio site).
 *
 * @return array<int, string>
 */
function estrato_pipeline_allowed_image_hosts() {
	$hosts = array(
		(string) wp_parse_url( home_url(), PHP_URL_HOST ),
		'cdn.estrato.cc',
	);
	return apply_filters( 'estrato_pipeline_allowed_image_hosts', $hosts );
}

/**
 * @param string $url
 * @return bool
 */
function estrato_pipeline_is_allowed_image_url( $url ) {
	$url = trim( (string) $url );
	if ( '' === $url || ! preg_match( '#^https?://#i', $url ) ) {
		return false;
	}
	$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
	if ( '' === $host ) {
		return false;
	}
	foreach ( estrato_pipeline_allowed_image_hosts() as $allowed ) {
		$allowed = strtolower( (string) $allowed );
		if ( $host === $allowed || str_ends_with( $host, '.' . $allowed ) ) {
			return true;
		}
	}
	if ( preg_match( ESTRATO_EXTERNAL_IMAGE_HOSTS, $host ) ) {
		return false;
	}
	return false;
}

/**
 * Remove imagens externas e blocos de paywall do HTML.
 *
 * @param string $html
 * @return string
 */
function estrato_pipeline_sanitize_content_html( $html ) {
	$html = (string) $html;
	if ( '' === trim( $html ) ) {
		return $html;
	}

	$html = preg_replace_callback(
		'/<img\b[^>]*>/iu',
		function ( $m ) {
			if ( ! preg_match( '/\bsrc=["\']([^"\']+)["\']/iu', $m[0], $src ) ) {
				return '';
			}
			return estrato_pipeline_is_allowed_image_url( $src[1] ) ? $m[0] : '';
		},
		$html
	);

	$html = preg_replace_callback(
		'/<figure\b[^>]*>.*?<\/figure>/ius',
		function ( $m ) {
			if ( preg_match( '/\bsrc=["\']([^"\']+)["\']/iu', $m[0], $src ) ) {
				if ( ! estrato_pipeline_is_allowed_image_url( $src[1] ) ) {
					return '';
				}
			}
			if ( preg_match( ESTRATO_PAYWALL_PATTERNS, wp_strip_all_tags( $m[0] ) ) ) {
				return '';
			}
			return $m[0];
		},
		$html
	);

	$plain = wp_strip_all_tags( $html );
	$parts = preg_split( '/(?<=[.!?])\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY );
	if ( is_array( $parts ) ) {
		$clean = array();
		foreach ( $parts as $sentence ) {
			if ( preg_match( ESTRATO_PAYWALL_PATTERNS, $sentence ) ) {
				continue;
			}
			$clean[] = $sentence;
		}
		if ( $clean && count( $clean ) < count( $parts ) ) {
			$html = wpautop( implode( ' ', $clean ) );
		}
	}

	return $html;
}

/**
 * @param string $title
 * @return bool
 */
function estrato_pipeline_title_is_portuguese( $title ) {
	$title = trim( wp_strip_all_tags( (string) $title ) );
	if ( '' === $title ) {
		return false;
	}
	if ( preg_match( '/[áàâãéêíóôõúçÁÀÂÃÉÊÍÓÔÕÚÇ]/u', $title ) ) {
		return true;
	}
	$lower = function_exists( 'mb_strtolower' ) ? mb_strtolower( $title ) : strtolower( $title );
	$pt_markers = array(
		' de ', ' da ', ' do ', ' das ', ' dos ', ' para ', ' com ', ' não ', ' mais ',
		' após ', ' sobre ', ' brasil ', ' mercado ', ' economia ', ' juros ', ' dólar ',
	);
	foreach ( $pt_markers as $marker ) {
		if ( false !== strpos( $lower, $marker ) ) {
			return true;
		}
	}
	$en_markers = array(
		' the ', ' and ', ' says ', ' after ', ' report ', ' earnings ', ' stocks ',
		' market ', ' investors ', ' billion ', ' million ', ' chief ', ' president ',
	);
	$en_hits = 0;
	foreach ( $en_markers as $marker ) {
		if ( false !== strpos( $lower, $marker ) ) {
			++$en_hits;
		}
	}
	if ( $en_hits >= 2 && ! preg_match( '/[áàâãéêíóôõúç]/u', $title ) ) {
		return false;
	}
	$ascii_ratio = strlen( preg_replace( '/[\x20-\x7E]/', '', $title ) ) / max( 1, strlen( $title ) );
	return $ascii_ratio < 0.85 || $en_hits < 2;
}

/**
 * @param string $title
 * @return string
 */
function estrato_pipeline_translate_title_stub( $title ) {
	return '[Tradução pendente] ' . wp_strip_all_tags( $title );
}

/**
 * @param string $title
 * @return array{ok:bool,title:string,reason:string}
 */
function estrato_pipeline_gate_title_language( $title ) {
	if ( estrato_pipeline_title_is_portuguese( $title ) ) {
		return array(
			'ok'     => true,
			'title'  => $title,
			'reason' => '',
		);
	}
	$policy = apply_filters( 'estrato_pipeline_language_policy', 'reject' );
	if ( 'translate' === $policy ) {
		return array(
			'ok'     => true,
			'title'  => estrato_pipeline_translate_title_stub( $title ),
			'reason' => 'translated_stub',
		);
	}
	return array(
		'ok'     => false,
		'title'  => $title,
		'reason' => 'non_portuguese_title',
	);
}

/**
 * @param string $content
 * @return string
 */
function estrato_pipeline_filter_the_content( $content ) {
	if ( is_admin() || is_feed() ) {
		return $content;
	}
	if ( ! is_singular( 'post' ) ) {
		return $content;
	}
	return estrato_pipeline_sanitize_content_html( $content );
}
add_filter( 'the_content', 'estrato_pipeline_filter_the_content', 5 );

/**
 * Sanitiza no save.
 *
 * @param int $post_id
 */
function estrato_pipeline_sanitize_on_save( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	$post = get_post( $post_id );
	if ( ! $post || 'post' !== $post->post_type ) {
		return;
	}
	$clean = estrato_pipeline_sanitize_content_html( $post->post_content );
	if ( $clean !== $post->post_content ) {
		remove_action( 'save_post_post', 'estrato_pipeline_sanitize_on_save', 15 );
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $clean,
			)
		);
		add_action( 'save_post_post', 'estrato_pipeline_sanitize_on_save', 15 );
	}
}
add_action( 'save_post_post', 'estrato_pipeline_sanitize_on_save', 15 );
