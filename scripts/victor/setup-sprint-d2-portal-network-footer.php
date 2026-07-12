<?php
/**
 * Sprint D2 — widget footer "Na rede Estrato".
 *
 * Satélites: footer-3 = rede completa.
 * Finance: footer-4 institucional + bloco rede (preserva footer-3 Guias).
 *
 * Uso: ESTRATO_PORTAL=estrato-mind wp eval-file setup-sprint-d2-portal-network-footer.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

if ( ! function_exists( 'estrato_nav_network_footer_html' ) ) {
	WP_CLI::error( 'estrato_nav_network_footer_html ausente (nav-visual.php)' );
}

$portal  = getenv( 'ESTRATO_PORTAL' ) ?: estrato_nav_current_portal_id();
$network = estrato_nav_network_footer_html();

/**
 * @param string $sidebar
 * @param string $html
 * @param string $title
 */
function estrato_d2_upsert_text_widget( $sidebar, $html, $title = '' ) {
	$widget_text = get_option( 'widget_text', array() );
	if ( empty( $widget_text['_multiwidget'] ) ) {
		$widget_text['_multiwidget'] = 1;
	}

	$sidebars = get_option( 'sidebars_widgets', array() );
	$key      = 1;
	while ( isset( $widget_text[ $key ] ) ) {
		++$key;
	}

	$widget_text[ $key ] = array(
		'title'  => $title,
		'text'   => $html,
		'filter' => false,
	);
	$sidebars[ $sidebar ] = array( 'text-' . $key );

	update_option( 'widget_text', $widget_text, false );
	update_option( 'sidebars_widgets', $sidebars, false );
	WP_CLI::log( "Widget {$sidebar} → text-{$key}" );
}

if ( 'estrato-finance' === $portal ) {
	$institutional = '<p><a href="/sobre/">Sobre</a> · <a href="/contato/">Contato</a> · '
		. '<a href="/politica-editorial/">Política editorial</a> · <a href="/politica-de-privacidade/">Privacidade</a></p>';
	estrato_d2_upsert_text_widget( 'footer-4', $institutional . "\n" . $network, 'Institucional' );
} else {
	estrato_d2_upsert_text_widget( 'footer-3', $network, '' );
}

WP_CLI::success(
	wp_json_encode(
		array(
			'portal' => $portal,
			'sidebar' => 'estrato-finance' === $portal ? 'footer-4' : 'footer-3',
		),
		JSON_UNESCAPED_UNICODE
	)
);
