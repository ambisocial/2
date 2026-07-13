<?php
/**
 * V6 (auditoria visual 2026-07-13) — Redirect legado de `/{slug}/` (raiz sem
 * `/category/`) para `/category/{slug}/`.
 *
 * A auditoria mostrou que menus internos apontam corretamente para
 * `/category/…/`, mas links externos e usuários digitando tendem à raiz
 * curta. Sem este redirect, retornava HTTP 404 (`/narrativas-som/` etc.).
 * O redirect só dispara se a URL solicitada não bater com nenhum post,
 * página, custom post type ou arquivo estático.
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * V6 — Detecta em `parse_request` se a URL curta é um slug de categoria e
 * dispara redirect 301 antes de o WP marcar 404. Redirect em
 * `template_redirect` também funciona, mas o handler 404 do PressGrid roda
 * antes por causa de outros hooks, quebrando o redirect.
 *
 * @param WP $wp
 */
function estrato_category_legacy_redirect_maybe( $wp ) {
	if ( is_admin() ) {
		return;
	}
	if ( ! isset( $wp->query_vars['name'] ) && ! isset( $wp->query_vars['pagename'] ) ) {
		return;
	}
	$slug = '';
	if ( ! empty( $wp->query_vars['name'] ) ) {
		$slug = (string) $wp->query_vars['name'];
	} elseif ( ! empty( $wp->query_vars['pagename'] ) ) {
		$slug = (string) $wp->query_vars['pagename'];
	}
	if ( '' === $slug || false !== strpos( $slug, '/' ) ) {
		return;
	}
	if ( isset( $wp->query_vars['category_name'] ) && $wp->query_vars['category_name'] === $slug ) {
		return;
	}
	if ( get_page_by_path( $slug, OBJECT, array( 'post', 'page' ) ) ) {
		return;
	}
	$term = get_term_by( 'slug', $slug, 'category' );
	if ( ! $term || is_wp_error( $term ) ) {
		return;
	}
	$url = get_category_link( $term->term_id );
	if ( ! $url ) {
		return;
	}
	wp_safe_redirect( $url, 301 );
	exit;
}
add_action( 'parse_request', 'estrato_category_legacy_redirect_maybe', 5 );

/**
 * V6 — Redirect antigos slugs de subcategoria (com prefixo redundante do pai)
 * para os novos slugs curtos, se o valor antigo estiver salvo em meta
 * `_estrato_old_slug`.
 */
function estrato_category_slug_migration_redirect() {
	if ( ! is_404() && ! is_category() ) {
		return;
	}
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
	if ( '' === $request_uri ) {
		return;
	}
	if ( false === strpos( $request_uri, '/category/' ) ) {
		return;
	}
	$path      = wp_parse_url( $request_uri, PHP_URL_PATH );
	$segments  = array_values( array_filter( explode( '/', (string) $path ) ) );
	$last_slug = end( $segments );
	if ( ! $last_slug || 'category' === $last_slug ) {
		return;
	}
	global $wpdb;
	$term_id = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT term_id FROM {$wpdb->termmeta} WHERE meta_key = %s AND meta_value = %s LIMIT 1",
			'_estrato_old_slug',
			$last_slug
		)
	);
	if ( ! $term_id ) {
		return;
	}
	$url = get_category_link( $term_id );
	if ( ! $url ) {
		return;
	}
	wp_safe_redirect( $url, 301 );
	exit;
}
add_action( 'template_redirect', 'estrato_category_slug_migration_redirect', 6 );
