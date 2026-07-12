<?php
/**
 * Saúde RSS + mega menu por portal (sync, cron, import, imagens, dedup).
 *
 * Uso: wp eval-file setup-portal-rss-health.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$report = array(
	'preset'       => '',
	'sync'         => array(),
	'cron'         => array(),
	'feeds'        => array(),
	'import'       => array(),
	'menu'         => array(),
	'images'       => array(),
	'duplicates'   => array(),
);

$preset = function_exists( 'estrato_rss_get_active_preset' ) ? estrato_rss_get_active_preset() : 'brasil-financeiro';
$report['preset'] = $preset;

// 1) Sync taxonomia v2 (categorias, subcategorias, matriz RSS, keywords).
if ( function_exists( 'estrato_rss_sync_portal_taxonomy' ) ) {
	$sync = estrato_rss_sync_portal_taxonomy( $preset );
	$report['sync'] = $sync;
	if ( ! empty( $sync['ok'] ) ) {
		WP_CLI::log( sprintf(
			'Sync OK — cats=%d subs=%d cols=%d nodes=%d',
			(int) ( $sync['categories'] ?? 0 ),
			(int) ( $sync['subcategories'] ?? 0 ),
			(int) ( $sync['columns'] ?? 0 ),
			(int) ( $sync['import_nodes'] ?? 0 )
		) );
	} else {
		WP_CLI::warning( 'Sync falhou: ' . ( $sync['reason'] ?? 'unknown' ) );
	}
}

// 2) Curadoria / dedupe feeds entre categorias.
if ( function_exists( 'estrato_rss_apply_curation' ) ) {
	$curation = estrato_rss_apply_curation( false );
	$report['feeds']['curation'] = $curation;
	WP_CLI::log( 'Curadoria: ' . wp_json_encode( $curation ) );
}

$dup_feeds = function_exists( 'estrato_rss_count_duplicate_feed_urls' )
	? estrato_rss_count_duplicate_feed_urls()
	: -1;
$report['feeds']['duplicate_urls'] = $dup_feeds;
WP_CLI::log( "URLs RSS duplicadas na config: $dup_feeds" );

// 3) Cron estrato_rss_import_event.
if ( function_exists( 'estrato_rss_get_settings' ) && function_exists( 'estrato_rss_reschedule_cron' ) ) {
	$settings = estrato_rss_get_settings();
	estrato_rss_reschedule_cron( $settings['cron_schedule'] );
	$next = wp_next_scheduled( ESTRATO_RSS_CRON_HOOK );
	$report['cron'] = array(
		'schedule' => $settings['cron_schedule'],
		'next'     => $next ? gmdate( 'c', $next ) : null,
	);
	if ( $next ) {
		WP_CLI::log( 'Cron RSS agendado: ' . $settings['cron_schedule'] . ' próximo=' . gmdate( 'c', $next ) );
	} else {
		WP_CLI::warning( 'Cron RSS não agendado — reagendando...' );
		estrato_rss_reschedule_cron( $settings['cron_schedule'] );
	}
}

// 4) Validação saúde feeds.
$health = get_option( ESTRATO_RSS_FEED_HEALTH_OPTION, array() );
if ( is_array( $health ) && $health ) {
	$ok = 0;
	$bad = 0;
	foreach ( $health as $url => $row ) {
		if ( ! empty( $row['ok'] ) ) {
			++$ok;
		} else {
			++$bad;
			WP_CLI::warning( 'Feed quebrado: ' . ( $row['title'] ?? $url ) );
		}
	}
	$report['feeds']['health_ok'] = $ok;
	$report['feeds']['health_bad'] = $bad;
	WP_CLI::log( "Saúde feeds: $ok ok / $bad quebrados" );
}

// 5) Import controlado (primeira passagem leve).
if ( function_exists( 'estrato_rss_run_import' ) ) {
	$import = estrato_rss_run_import( false );
	$report['import'] = $import;
	WP_CLI::log( 'Import RSS: ' . wp_json_encode( $import ) );
}

// 6) Mega menu com subcategorias.
if ( function_exists( 'estrato_nav_rebuild_principal_menu' ) ) {
	$menu = estrato_nav_rebuild_principal_menu();
	$report['menu'] = $menu;
	WP_CLI::log( sprintf(
		'Mega menu — pais=%d filhos=%d colunas=%d',
		(int) ( $menu['parents'] ?? 0 ),
		(int) ( $menu['children'] ?? 0 ),
		(int) ( $menu['columns'] ?? 0 )
	) );
} elseif ( function_exists( 'estrato_rss_rebuild_menus' ) ) {
	$categories = function_exists( 'estrato_rss_create_categories' ) ? estrato_rss_create_categories() : array();
	estrato_rss_rebuild_menus( $categories );
	WP_CLI::log( 'Menu plano reconstruído (mega menu indisponível)' );
}

if ( function_exists( 'estrato_rss_rebuild_column_menu' ) ) {
	estrato_rss_rebuild_column_menu();
}

// 7) Imagens originais (backfill URL → attachment).
$img_set = 0;
$img_fail = 0;
if ( function_exists( 'estrato_bridge_backfill_featured_images' ) ) {
	for ( $round = 0; $round < 5; $round++ ) {
		$stats = estrato_bridge_backfill_featured_images( 40 );
		$img_set   += (int) ( $stats['set'] ?? 0 );
		$img_fail  += (int) ( $stats['failed'] ?? 0 );
		if ( (int) ( $stats['processed'] ?? 0 ) < 40 ) {
			break;
		}
	}
	WP_CLI::log( "Imagens originais backfill: set=$img_set failed=$img_fail" );
}
$report['images'] = array( 'set' => $img_set, 'failed' => $img_fail );

// 8) Posts RSS duplicados por GUID.
$guid_dupes = 0;
global $wpdb;
$rows = $wpdb->get_results(
	"SELECT meta_value AS guid, COUNT(*) AS c FROM {$wpdb->postmeta}
	 WHERE meta_key = '_estrato_rss_guid' AND meta_value != ''
	 GROUP BY meta_value HAVING c > 1 LIMIT 50",
	ARRAY_A
);
if ( $rows ) {
	foreach ( $rows as $row ) {
		$guid_dupes += (int) $row['c'] - 1;
		WP_CLI::warning( 'GUID duplicado: ' . $row['guid'] . ' (' . $row['c'] . 'x)' );
	}
}
$report['duplicates']['guid'] = $guid_dupes;

// 9) Títulos idênticos publicados (últimos 500).
$title_dupes = 0;
$titles = $wpdb->get_results(
	"SELECT post_title, COUNT(*) AS c FROM {$wpdb->posts}
	 WHERE post_type='post' AND post_status='publish'
	 GROUP BY post_title HAVING c > 1 ORDER BY c DESC LIMIT 20",
	ARRAY_A
);
if ( $titles ) {
	foreach ( $titles as $row ) {
		$title_dupes += (int) $row['c'] - 1;
	}
}
$report['duplicates']['titles'] = $title_dupes;
if ( $title_dupes > 0 ) {
	WP_CLI::warning( "Títulos duplicados detectados: $title_dupes posts extras" );
}

update_option( 'estrato_rss_health_last_report', $report, false );

if ( $dup_feeds > 0 || $guid_dupes > 0 ) {
	WP_CLI::warning( 'Saúde RSS concluída com alertas — ver relatório.' );
} else {
	WP_CLI::success( 'Saúde RSS + mega menu OK.' );
}
