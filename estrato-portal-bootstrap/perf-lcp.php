<?php
/**
 * Performance — LCP, fontes, preload e dequeue de bloat (S7.3).
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return int
 */
function estrato_perf_home_hero_post_id() {
	if ( ! function_exists( 'estrato_home_fetch_posts' ) || ! function_exists( 'estrato_home_pick_hero' ) ) {
		return 0;
	}
	$cache_key = 'estrato_perf_hero_' . gmdate( 'Y-m-d-H' );
	$cached    = get_transient( $cache_key );
	if ( false !== $cached ) {
		return (int) $cached;
	}
	$editorias = function_exists( 'estrato_aeo_portal_editorias' )
		? estrato_aeo_portal_editorias()
		: array( 'economia', 'mercados', 'negocios' );
	$pool = estrato_home_fetch_posts( array( 'posts_per_page' => 40 ) );
	$hero = estrato_home_pick_hero( $pool, $editorias );
	$id   = $hero ? (int) $hero->ID : 0;
	set_transient( $cache_key, $id, HOUR_IN_SECONDS );
	return $id;
}

/**
 * Remove hints e bloat do PressGrid que competem com LCP.
 */
function estrato_perf_disable_pressgrid_bloat() {
	remove_action( 'wp_head', 'pressgrid_preconnect_hints', 1 );
}
add_action( 'after_setup_theme', 'estrato_perf_disable_pressgrid_bloat', 99 );

/**
 * Featured do single: medium_large em vez de pressgrid-wide (1200px).
 *
 * @param string       $html
 * @param int          $post_id
 * @param int          $post_thumbnail_id
 * @param string|int[] $size
 * @param string|array $attr
 * @return string
 */
function estrato_perf_single_thumbnail_size( $html, $post_id, $post_thumbnail_id, $size, $attr ) {
	if ( ! is_singular( 'post' ) || (int) get_the_ID() !== (int) $post_id ) {
		return $html;
	}
	$heavy = array( 'pressgrid-wide', 'large', 'full', 'pressgrid-hero' );
	if ( ! in_array( $size, $heavy, true ) ) {
		return $html;
	}
	$attrs = is_array( $attr ) ? $attr : array();
	return wp_get_attachment_image( $post_thumbnail_id, 'medium_large', false, $attrs );
}
add_filter( 'post_thumbnail_html', 'estrato_perf_single_thumbnail_size', 1, 5 );

/**
 * CSS crítico do single (header + featured) cedo no head.
 */
function estrato_perf_critical_single_css() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}
	$css = '.pg-single-header,.pg-single-meta{display:none!important}'
		. '.pg-featured-image img{width:100%;height:auto;border-radius:4px}'
		. '.estrato-single-header{max-width:680px;margin:0 auto var(--estrato-space-4,24px);padding:0 1rem}'
		. '.estrato-single-dek{font-size:20px;line-height:1.45;color:var(--estrato-muted,#5E5E5E);margin:.75rem 0 1rem}'
		. '.estrato-display.estrato-h1{font-size:clamp(26px,4vw,48px);line-height:1.1}'
		. '.entry-content,.post-content,.pg-entry-content{max-width:680px;margin:0 auto}';
	if ( function_exists( 'estrato_perf_style_add' ) ) {
		estrato_perf_style_add( 'estrato-critical-single', $css, 'critical' );
	} else {
		echo '<style id="estrato-critical-single">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_head', 'estrato_perf_critical_single_css', 2 );

/**
 * PressGrid style.css tem @import Google Fonts — carrega sem bloquear.
 *
 * @param string $html
 * @param string $handle
 * @return string
 */
function estrato_perf_async_pressgrid_style( $html, $handle ) {
	if ( 'pressgrid-style' !== $handle ) {
		return $html;
	}
	if ( false !== strpos( $html, 'media=' ) ) {
		return preg_replace( '/media=[\'"]all[\'"]/', 'media="print" onload="this.media=\'all\'"', $html, 1 );
	}
	return str_replace( "rel='stylesheet'", "rel='stylesheet' media='print' onload=\"this.media='all'\"", $html );
}
add_filter( 'style_loader_tag', 'estrato_perf_async_pressgrid_style', 10, 2 );

/**
 * Preconnect + preload LCP (imagem hero / featured).
 */
function estrato_perf_preload_lcp() {
	if ( is_admin() || is_feed() ) {
		return;
	}

	$lcp_size  = 'medium_large';
	$image_url = '';
	if ( function_exists( 'estrato_home_is_active' ) && estrato_home_is_active() ) {
		$hero_id = estrato_perf_home_hero_post_id();
		if ( $hero_id ) {
			$image_url = get_the_post_thumbnail_url( $hero_id, $lcp_size );
		}
	} elseif ( is_singular( 'post' ) && has_post_thumbnail() ) {
		$image_url = get_the_post_thumbnail_url( get_the_ID(), $lcp_size );
	}

	if ( $image_url ) {
		printf(
			'<link rel="preload" as="image" href="%s" fetchpriority="high">' . "\n",
			esc_url( $image_url )
		);
	}
}
add_action( 'wp_head', 'estrato_perf_preload_lcp', 1 );

/**
 * CSS crítico above-the-fold na home (evita esperar style block tardio).
 */
function estrato_perf_critical_home_css() {
	if ( ! function_exists( 'estrato_home_is_active' ) || ! estrato_home_is_active() ) {
		return;
	}
	$css = '.estrato-home-v2{max-width:1200px;margin:0 auto;padding:var(--estrato-space-4,24px) 1rem}'
		. '.estrato-home-hero{display:grid;gap:var(--estrato-space-4,24px);margin-bottom:var(--estrato-space-5,40px)}'
		. '@media(min-width:900px){.estrato-home-hero{grid-template-columns:1.4fr 1fr}}'
		. '.estrato-home-card__link{color:inherit;text-decoration:none;display:block}'
		. '.estrato-home-card__media img{width:100%;height:auto;border-radius:4px}'
		. '.estrato-home-card--hero .estrato-home-card__title{font-size:clamp(28px,4vw,40px);line-height:1.1;margin:.35rem 0}';
	if ( function_exists( 'estrato_perf_style_add' ) ) {
		estrato_perf_style_add( 'estrato-critical-home', $css, 'critical' );
	} else {
		echo '<style id="estrato-critical-home">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_head', 'estrato_perf_critical_home_css', 2 );

/**
 * Google Fonts sem bloquear render (media print → all).
 *
 * @param string $html
 * @param string $handle
 * @return string
 */
function estrato_perf_async_fonts( $html, $handle ) {
	if ( 'estrato-ds-fonts' !== $handle ) {
		return $html;
	}
	if ( false !== strpos( $html, 'media=' ) ) {
		return preg_replace( '/media=[\'"]all[\'"]/', 'media="print" onload="this.media=\'all\'"', $html, 1 );
	}
	return str_replace( "rel='stylesheet'", "rel='stylesheet' media='print' onload=\"this.media='all'\"", $html );
}
add_filter( 'style_loader_tag', 'estrato_perf_async_fonts', 10, 2 );

/**
 * Defer scripts não críticos.
 *
 * @param string $tag
 * @param string $handle
 * @return string
 */
function estrato_perf_defer_scripts( $tag, $handle ) {
	$no_defer = array( 'jquery-core', 'jquery-migrate', 'estrato-ds-fonts' );
	if ( in_array( $handle, $no_defer, true ) ) {
		return $tag;
	}
	if ( false !== strpos( $tag, ' defer' ) || false !== strpos( $tag, ' async' ) ) {
		return $tag;
	}
	return str_replace( ' src', ' defer src', $tag );
}
add_filter( 'script_loader_tag', 'estrato_perf_defer_scripts', 10, 2 );

/**
 * Remove bloat WP no front.
 */
function estrato_perf_disable_bloat() {
	if ( is_admin() ) {
		return;
	}
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	wp_deregister_script( 'wp-embed' );
}
add_action( 'init', 'estrato_perf_disable_bloat' );

/**
 * Dequeue CSS de blocos no front (PressGrid não usa) + extras inline do core/tema.
 */
function estrato_perf_dequeue_block_css() {
	if ( is_admin() ) {
		return;
	}
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'classic-theme-styles' );
	wp_dequeue_style( 'global-styles' );
	wp_deregister_style( 'global-styles' );
	// Inline CSS do core (WP 6.7 auto-sizes) — fora da meta ≤3 do consolidador Estrato.
	wp_dequeue_style( 'core-block-supports' );
	wp_dequeue_style( 'wp-img-auto-sizes-contain' );
}
add_action( 'wp_enqueue_scripts', 'estrato_perf_dequeue_block_css', 100 );
add_action( 'wp_enqueue_scripts', 'estrato_perf_dequeue_block_css', 999 );

/**
 * Evita o <style id="wp-img-auto-sizes-contain-inline-css">.
 *
 * @return bool
 */
function estrato_perf_disable_auto_sizes() {
	return false;
}
add_filter( 'wp_img_tag_add_auto_sizes', 'estrato_perf_disable_auto_sizes' );

/**
 * Remove inline CSS do PressGrid (customizer) — tokens/overrides já estão no design-system.
 *
 * @return void
 */
function estrato_perf_strip_pressgrid_inline() {
	if ( is_admin() ) {
		return;
	}
	global $wp_styles;
	if ( ! $wp_styles instanceof WP_Styles ) {
		return;
	}
	foreach ( array( 'pressgrid-style', 'pressgrid', 'stylesheet' ) as $handle ) {
		if ( ! empty( $wp_styles->registered[ $handle ] ) ) {
			$wp_styles->registered[ $handle ]->extra['after'] = array();
		}
	}
}
add_action( 'wp_print_styles', 'estrato_perf_strip_pressgrid_inline', 999 );

/**
 * Logo com dimensões explícitas (evita CLS).
 *
 * @param string $html
 * @return string
 */
function estrato_perf_logo_dimensions( $html ) {
	if ( false === strpos( $html, 'custom-logo' ) ) {
		return $html;
	}
	if ( false !== strpos( $html, 'width=' ) ) {
		return $html;
	}
	return str_replace( '<img ', '<img width="160" height="40" ', $html );
}
add_filter( 'get_custom_logo', 'estrato_perf_logo_dimensions' );

/**
 * Featured image no single — prioridade LCP.
 *
 * @param string       $html
 * @param int          $post_id
 * @param int          $post_thumbnail_id
 * @param string|int[] $size
 * @param string|array $attr
 * @return string
 */
function estrato_perf_post_thumbnail_attrs( $html, $post_id, $post_thumbnail_id, $size, $attr ) {
	unset( $post_thumbnail_id, $size, $attr );
	if ( ! is_singular( 'post' ) || (int) get_the_ID() !== (int) $post_id ) {
		return $html;
	}
	if ( false !== strpos( $html, 'fetchpriority' ) ) {
		return $html;
	}
	return str_replace( '<img ', '<img fetchpriority="high" loading="eager" decoding="async" ', $html );
}
add_filter( 'post_thumbnail_html', 'estrato_perf_post_thumbnail_attrs', 10, 5 );

/**
 * @return array<string, mixed>
 */
function estrato_perf_lcp_status() {
	return array(
		'preload'        => true,
		'async_fonts'    => true,
		'defer_js'       => true,
		'ticker_cron'    => true,
		'fonts_cdn'      => function_exists( 'estrato_fonts_selfhosted_ready' ) && estrato_fonts_selfhosted_ready() ? 'selfhosted' : 'bunny',
		'lcp_image_size' => 'medium_large',
		'single_resize'  => true,
		'pressgrid_hints'=> false,
		'version'        => defined( 'ESTRATO_PORTAL_VERSION' ) ? ESTRATO_PORTAL_VERSION : '',
	);
}
