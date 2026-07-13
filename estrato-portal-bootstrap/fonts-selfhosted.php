<?php
/**
 * Fontes self-hosted — Inter, Newsreader, IBM Plex Mono (S7 / auditoria LCP).
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return string
 */
function estrato_fonts_assets_dir() {
	return trailingslashit( dirname( __FILE__ ) ) . 'assets/fonts/';
}

/**
 * @return string
 */
function estrato_fonts_assets_url() {
	return plugin_dir_url( __DIR__ . '/estrato-portal-bootstrap.php' ) . 'assets/fonts/';
}

/**
 * @return bool
 */
function estrato_fonts_selfhosted_ready() {
	static $ready = null;
	if ( null !== $ready ) {
		return $ready;
	}
	$ready = is_readable( estrato_fonts_assets_dir() . 'inter-latin-400-normal.woff2' )
		&& is_readable( estrato_fonts_assets_dir() . 'newsreader-latin-600-normal.woff2' )
		&& is_readable( estrato_fonts_assets_dir() . 'ibm-plex-mono-latin-500-normal.woff2' );
	return $ready;
}

/**
 * @return array<int, array{file:string,family:string,weight:int,style:string}>
 */
function estrato_fonts_selfhosted_faces() {
	return array(
		array( 'file' => 'inter-latin-400-normal.woff2', 'family' => 'Inter', 'weight' => 400, 'style' => 'normal' ),
		array( 'file' => 'inter-latin-500-normal.woff2', 'family' => 'Inter', 'weight' => 500, 'style' => 'normal' ),
		array( 'file' => 'inter-latin-600-normal.woff2', 'family' => 'Inter', 'weight' => 600, 'style' => 'normal' ),
		array( 'file' => 'inter-latin-700-normal.woff2', 'family' => 'Inter', 'weight' => 700, 'style' => 'normal' ),
		array( 'file' => 'newsreader-latin-600-normal.woff2', 'family' => 'Newsreader', 'weight' => 600, 'style' => 'normal' ),
		array( 'file' => 'newsreader-latin-700-normal.woff2', 'family' => 'Newsreader', 'weight' => 700, 'style' => 'normal' ),
		array( 'file' => 'ibm-plex-mono-latin-500-normal.woff2', 'family' => 'IBM Plex Mono', 'weight' => 500, 'style' => 'normal' ),
	);
}

/**
 * @font-face inline (sem CDN externo).
 */
function estrato_fonts_print_selfhosted_css() {
	if ( ! estrato_fonts_selfhosted_ready() || is_admin() || is_feed() ) {
		return;
	}
	$base = estrato_fonts_assets_url();
	echo "<style id=\"estrato-fonts-selfhosted\">\n";
	foreach ( estrato_fonts_selfhosted_faces() as $face ) {
		if ( ! is_readable( estrato_fonts_assets_dir() . $face['file'] ) ) {
			continue;
		}
		printf(
			"@font-face{font-family:'%s';font-style:%s;font-weight:%d;font-display:swap;src:url('%s') format('woff2');}\n",
			esc_attr( $face['family'] ),
			esc_attr( $face['style'] ),
			(int) $face['weight'],
			esc_url( $base . $face['file'] )
		);
	}
	echo "</style>\n";
}
add_action( 'wp_head', 'estrato_fonts_print_selfhosted_css', 3 );

/**
 * Preload fontes críticas (body + display).
 */
function estrato_fonts_preload_selfhosted() {
	if ( ! estrato_fonts_selfhosted_ready() || is_admin() || is_feed() ) {
		return;
	}
	$base  = estrato_fonts_assets_url();
	$files = array(
		'inter-latin-400-normal.woff2',
		'newsreader-latin-600-normal.woff2',
	);
	foreach ( $files as $file ) {
		if ( ! is_readable( estrato_fonts_assets_dir() . $file ) ) {
			continue;
		}
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( $base . $file )
		);
	}
}
add_action( 'wp_head', 'estrato_fonts_preload_selfhosted', 2 );
