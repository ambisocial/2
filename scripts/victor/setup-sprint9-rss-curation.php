<?php
/**
 * Sprint 9 — Curadoria RSS por categoria.
 *
 * Uso: wp eval-file setup-sprint9-rss-curation.php
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

// S9.5 — pipeline_primary: 2 items/feed, 15 max, hourly.
if ( function_exists( 'estrato_rss_apply_content_mode' ) ) {
	$settings = estrato_rss_apply_content_mode( 'pipeline_primary' );
	WP_CLI::log( 'RSS mode pipeline_primary: ' . wp_json_encode( $settings ) );
}

// S9.4 + S9.1 + S9.2/3 — sync taxonomia, dedupe, validar feeds.
if ( function_exists( 'estrato_rss_sync_portal_taxonomy' ) ) {
	$sync = estrato_rss_sync_portal_taxonomy();
	WP_CLI::log( 'Sync taxonomia: ' . wp_json_encode( $sync ) );
} elseif ( function_exists( 'estrato_rss_apply_curation' ) ) {
	$curation = estrato_rss_apply_curation( false );
	WP_CLI::log( 'Curadoria: ' . wp_json_encode( $curation ) );
}

// S9.6 — rebuild menu.
$categories = estrato_rss_create_categories();
if ( function_exists( 'estrato_rss_rebuild_menus' ) ) {
	estrato_rss_rebuild_menus( $categories );
	WP_CLI::log( 'Menu Estrato Principal reconstruído' );
}

// Relatório matriz fonte × categoria.
$config = estrato_rss_get_config();
WP_CLI::log( '--- Matriz fonte × categoria ---' );
$total_feeds = 0;
foreach ( $config as $slug => $data ) {
	$n = count( $data['feeds'] );
	$total_feeds += $n;
	$sources = array();
	foreach ( $data['feeds'] as $f ) {
		$sources[] = $f['title'];
	}
	WP_CLI::log( sprintf( '%s: %d feeds — %s', $slug, $n, implode( ', ', $sources ) ) );
}
WP_CLI::log( "Total feeds ativos: $total_feeds" );

$dupes = function_exists( 'estrato_rss_count_duplicate_feed_urls' )
	? estrato_rss_count_duplicate_feed_urls()
	: -1;
WP_CLI::log( "URLs duplicadas na config ativa: $dupes" );

$health = get_option( ESTRATO_RSS_FEED_HEALTH_OPTION, array() );
if ( is_array( $health ) && ! empty( $health ) ) {
	$ok = 0;
	$bad = 0;
	foreach ( $health as $url => $row ) {
		if ( ! empty( $row['ok'] ) ) {
			++$ok;
		} else {
			++$bad;
			WP_CLI::warning( 'Feed quebrado: ' . ( $row['title'] ?? $url ) . ' — ' . ( $row['error'] ?? '' ) );
		}
	}
	WP_CLI::log( "Saúde feeds: $ok ok / $bad quebrados" );
}

// S9.7 — IndexNow por categoria (mercados → negócios → …).
$taxonomy = function_exists( 'estrato_rss_load_finance_taxonomy' )
	? estrato_rss_load_finance_taxonomy()
	: array();
$index_order = $taxonomy['index_order'] ?? array(
	'mercados',
	'negocios',
	'economia',
	'financas-pessoais',
	'criptomoedas',
	'agronegocio',
	'mundo',
);

if ( function_exists( 'estrato_rss_index_categories_indexnow' ) ) {
	$indexed = estrato_rss_index_categories_indexnow( $index_order );
	WP_CLI::log( "IndexNow categorias: $indexed URLs" );
}

WP_CLI::success( 'Sprint 9 curadoria RSS configurada.' );
