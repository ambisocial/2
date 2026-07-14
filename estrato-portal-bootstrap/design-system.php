<?php
/**
 * Design system — tokens de cor, tipografia e espaçamento (Sprint 2).
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return array<string, string>
 */
function estrato_ds_editoria_colors() {
	return array(
		'economia'          => '#0D5C63',
		'mercados'          => '#0B6E4F',
		'negocios'          => '#1B2A4A',
		'financas-pessoais' => '#5B3E96',
		'criptomoedas'      => '#C77800',
		'agronegocio'       => '#55701C',
		'mundo'             => '#7A1F2B',
	);
}

/**
 * @param int $post_id
 * @return string
 */
function estrato_ds_post_editoria_slug( $post_id = 0 ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$cats    = get_the_category( $post_id );
	if ( empty( $cats ) ) {
		return 'economia';
	}
	$colors = estrato_ds_editoria_colors();
	foreach ( $cats as $cat ) {
		$root = $cat;
		while ( $root->parent ) {
			$parent = get_term( $root->parent, 'category' );
			if ( ! $parent || is_wp_error( $parent ) ) {
				break;
			}
			$root = $parent;
		}
		if ( isset( $colors[ $root->slug ] ) ) {
			return $root->slug;
		}
	}
	return $cats[0]->slug;
}

/**
 * @param string $slug
 * @return string
 */
function estrato_ds_editoria_color( $slug ) {
	$colors = estrato_ds_editoria_colors();
	return $colors[ $slug ] ?? '#191919';
}

/**
 * CSS global do design system.
 */
function estrato_ds_print_styles() {
	if ( is_admin() || is_feed() ) {
		return;
	}
	$colors = estrato_ds_editoria_colors();
	$vars   = '';
	foreach ( $colors as $slug => $hex ) {
		$vars .= '--cat-' . esc_attr( str_replace( '-', '_', $slug ) ) . ':' . esc_attr( $hex ) . ';';
	}
	$css = ':root{'
		. '--estrato-bg:#FBFAF7;--estrato-ink:#191919;--estrato-muted:#5E5E5E;--estrato-line:#E4E1DA;'
		. '--estrato-up:#0B6E4F;--estrato-down:#C0392B;'
		. '--estrato-space-1:4px;--estrato-space-2:8px;--estrato-space-3:16px;--estrato-space-4:24px;--estrato-space-5:40px;--estrato-space-6:64px;'
		. '--estrato-radius:4px;'
		. '--estrato-bp-sm:640px;--estrato-bp-md:768px;--estrato-bp-lg:960px;--estrato-bp-xl:1100px;'
		. '--estrato-font-display:"Newsreader",Georgia,"Times New Roman",serif;'
		. '--estrato-font-body:"Inter",-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;'
		. '--estrato-font-mono:"IBM Plex Mono",ui-monospace,monospace;'
		. $vars
		. '}'
		. 'html{-webkit-text-size-adjust:100%;text-size-adjust:100%}'
		. 'body{background:var(--estrato-bg);color:var(--estrato-ink);font-family:var(--estrato-font-body);font-size:16px;line-height:1.6;overflow-x:clip}'
		. '@media(min-width:960px){body{font-size:18px}}'
		. 'img,video,svg,iframe{max-width:100%;height:auto}'
		. 'table{display:block;max-width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch}'
		. '.entry-content img,.post-content img,.pg-entry-content img{max-width:100%;height:auto}'
		. '.estrato-cat-bar{height:4px;width:100%}'
		. '.estrato-kicker{color:var(--estrato-cat-color,#191919);font-family:var(--estrato-font-mono);font-size:12px;font-weight:600;letter-spacing:.08em;text-transform:uppercase}'
		. '.estrato-price-up{color:var(--estrato-up)}'
		. '.estrato-price-down{color:var(--estrato-down)}'
		. '.estrato-display{font-family:var(--estrato-font-display);font-weight:600;letter-spacing:-.02em}'
		. '.estrato-h1{font-size:clamp(26px,4vw,48px);line-height:1.1}'
		. '.estrato-h2{font-size:clamp(22px,3.2vw,34px);line-height:1.15}'
		. '.estrato-h3{font-size:clamp(18px,2.6vw,26px);line-height:1.2}'
		. '.estrato-h4{font-size:clamp(16px,2.2vw,20px);line-height:1.25}'
		. '.estrato-body-lg{font-size:1.125rem;line-height:1.6}'
		. '.estrato-body-sm{font-size:.875rem}'
		. '.estrato-caption{font-size:.75rem;color:var(--estrato-muted)}'
		/* Sidebar PressGrid: sem scroll interno — só o scroll da página. */
		. '.pg-sidebar,.pg-sidebar[role="complementary"]{max-height:none!important;height:auto!important;overflow:visible!important;overflow-y:visible!important;scrollbar-width:auto}'
		/* Mobile: sidebar abaixo/oculta; sem publicidade vazia ocupando viewport. */
		. '@media(max-width:1099px){.pg-sidebar,.pg-sidebar[role="complementary"],.widget_text:has(.ads),.widget.widget_media_image{order:99}}'
		. '@media(prefers-reduced-motion:reduce){*,*::before,*::after{animation-duration:.01ms!important;animation-iteration-count:1!important;transition-duration:.01ms!important;scroll-behavior:auto!important}}'
		. 'a:focus-visible,button:focus-visible,summary:focus-visible,input:focus-visible{outline:2px solid #9AFF33;outline-offset:2px}';
	if ( function_exists( 'estrato_perf_style_add' ) ) {
		estrato_perf_style_add( 'estrato-design-system', $css, 'critical' );
	} else {
		echo '<style id="estrato-design-system">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_head', 'estrato_ds_print_styles', 2 );

/**
 * Google Fonts.
 */
function estrato_ds_enqueue_fonts() {
	if ( is_admin() || is_feed() ) {
		return;
	}
	if ( function_exists( 'estrato_fonts_selfhosted_ready' ) && estrato_fonts_selfhosted_ready() ) {
		return;
	}
	wp_enqueue_style(
		'estrato-ds-fonts',
		'https://fonts.bunny.net/css?family=inter:400,500,600,700|newsreader:600,700|ibm-plex-mono:500&display=swap',
		array(),
		null
	);
}
add_action( 'wp_enqueue_scripts', 'estrato_ds_enqueue_fonts', 5 );

/**
 * Barra de editoria em archives.
 */
function estrato_ds_category_bar() {
	if ( ! is_category() ) {
		return;
	}
	$term = get_queried_object();
	if ( ! $term instanceof WP_Term ) {
		return;
	}
	$slug  = estrato_ds_post_editoria_slug();
	$color = estrato_ds_editoria_color( $slug );
	printf(
		'<div class="estrato-cat-bar" style="--estrato-cat-color:%s;background:%s" aria-hidden="true"></div>',
		esc_attr( $color ),
		esc_attr( $color )
	);
}
add_action( 'wp_body_open', 'estrato_ds_category_bar', 4 );
