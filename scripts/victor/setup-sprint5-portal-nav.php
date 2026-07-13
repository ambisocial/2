<?php
/**
 * Sprint 5 genérico — footer widgets + intro SEO por editoria (satélites).
 *
 * Uso: ESTRATO_PORTAL=estrato-mind wp eval-file setup-sprint5-portal-nav.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$blog_name = get_bloginfo( 'name' );
$tagline   = get_bloginfo( 'description' );

// Footer widget 1 — sobre o portal.
$footer1 = <<<HTML
<div class="estrato-footer-about">
<strong>{$blog_name}</strong>
<p>{$tagline}</p>
<p><a href="/sobre/">Sobre</a> · <a href="/politica-editorial/">Editorial</a> · <a href="/contato/">Contato</a></p>
</div>
HTML;

$sidebars = array(
	'footer-1' => $footer1,
	'footer-2' => '<p><strong>Editorias</strong></p>' . ( function_exists( 'estrato_nav_footer_editorias' ) ? estrato_nav_footer_editorias() : '' ),
	'footer-3' => '<p>Parte do ecossistema <a href="https://estrato.cc/">Estrato</a>.</p>',
	'footer-4' => '<p><a href="/privacidade/">Privacidade</a></p>',
);

foreach ( $sidebars as $sidebar => $html ) {
	if ( ! is_active_sidebar( $sidebar ) ) {
		$widget_id = 'text_estrato_' . sanitize_key( $sidebar );
		$widgets   = get_option( 'widget_text', array() );
		$widgets[1] = array(
			'title'  => '',
			'text'   => $html,
			'filter' => false,
		);
		update_option( 'widget_text', $widgets );
		$sidebars_widgets = get_option( 'sidebars_widgets', array() );
		if ( ! isset( $sidebars_widgets[ $sidebar ] ) ) {
			$sidebars_widgets[ $sidebar ] = array();
		}
		$sidebars_widgets[ $sidebar ][] = 'text-1';
		update_option( 'sidebars_widgets', $sidebars_widgets );
	}
}

// SEO intro nas editorias (150 chars mínimo).
$editorias = get_terms(
	array(
		'taxonomy'   => 'category',
		'parent'     => 0,
		'hide_empty' => false,
	)
);
if ( ! is_wp_error( $editorias ) ) {
	foreach ( $editorias as $term ) {
		if ( strlen( (string) $term->description ) < 80 ) {
			$brand = get_term_meta( $term->term_id, '_estrato_brand_name', true ) ?: $term->name;
			$desc  = "{$brand}: acompanhe as últimas notícias e análises de {$term->name} no {$blog_name}. Curadoria editorial com fontes verificáveis.";
			wp_update_term( $term->term_id, 'category', array( 'description' => $desc ) );
			WP_CLI::log( "SEO desc: {$term->slug}" );
		}
	}
}

// Breaking bar — usa breaking_category do theme mod se existir.
$breaking = get_theme_mod( 'pressgrid_breaking_news_category', 0 );
if ( ! $breaking && ! empty( $editorias[0] ) ) {
	set_theme_mod( 'pressgrid_breaking_news_category', (int) $editorias[0]->term_id );
}

WP_CLI::success( 'Sprint 5 portal nav OK' );
