<?php
/**
 * Sprint G3 — estrato.cc como hub da rede (S26).
 *
 * Injeta bloco "Portais Estrato" na home do finance.
 *
 * Uso: ESTRATO_PORTAL=estrato-finance wp eval-file setup-sprint-g3-finance-hub.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$portal = getenv( 'ESTRATO_PORTAL' ) ?: estrato_nav_current_portal_id();
if ( 'estrato-finance' !== $portal ) {
	WP_CLI::success( wp_json_encode( array( 'portal' => $portal, 'skipped' => true ) ) );
	exit( 0 );
}

if ( ! function_exists( 'estrato_nav_network_hub_html' ) ) {
	WP_CLI::error( 'estrato_nav_network_hub_html ausente (nav-visual.php)' );
}

$hub_html = estrato_nav_network_hub_html();

$widget_text = get_option( 'widget_text', array() );
if ( empty( $widget_text['_multiwidget'] ) ) {
	$widget_text['_multiwidget'] = 1;
}

$key = 1;
while ( isset( $widget_text[ $key ] ) ) {
	++$key;
}

$widget_text[ $key ] = array(
	'title'  => 'Portais Estrato',
	'text'   => $hub_html,
	'filter' => false,
);

$sidebars = get_option( 'sidebars_widgets', array() );
$sidebars['content-bottom'] = array( 'text-' . $key );

update_option( 'widget_text', $widget_text, false );
update_option( 'sidebars_widgets', $sidebars, false );
update_option( 'estrato_network_hub_enabled', true, false );

WP_CLI::success(
	wp_json_encode(
		array(
			'portal'  => $portal,
			'widget'    => 'text-' . $key,
			'sidebar'   => 'content-bottom',
			'portals'   => 5,
		),
		JSON_UNESCAPED_UNICODE
	)
);
