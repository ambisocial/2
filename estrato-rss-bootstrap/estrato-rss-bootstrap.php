<?php
/**
 * Plugin Name: Estrato RSS Bootstrap
 * Description: Cria categorias e importa notícias automaticamente de feeds RSS (estilo portal financeiro).
 * Version: 1.0.0
 * Author: Cursor Agent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ESTRATO_RSS_VERSION', '1.0.0' );
define( 'ESTRATO_RSS_CRON_HOOK', 'estrato_rss_import_event' );
define( 'ESTRATO_RSS_OPTION_FEEDS', 'estrato_rss_feeds_config' );
define( 'ESTRATO_RSS_OPTION_IMPORTED', 'estrato_rss_imported_guids' );

register_activation_hook( __FILE__, 'estrato_rss_activate' );
register_deactivation_hook( __FILE__, 'estrato_rss_deactivate' );
add_action( ESTRATO_RSS_CRON_HOOK, 'estrato_rss_run_import' );
add_action( 'admin_notices', 'estrato_rss_admin_notice' );
add_action( 'admin_menu', 'estrato_rss_admin_menu' );

/**
 * Category and feed map (CNBC Brasil / portal financeiro style).
 *
 * @return array<string, array{name:string, description:string, feeds:array<int, array{title:string,url:string}>}>
 */
function estrato_rss_get_config() {
	return array(
		'economia'      => array(
			'name'        => 'Economia',
			'description' => 'Macroeconomia, inflação, PIB, política fiscal e monetária no Brasil e no mundo.',
			'feeds'       => array(
				array( 'title' => 'G1 Economia', 'url' => 'https://g1.globo.com/rss/g1/economia/' ),
				array( 'title' => 'InfoMoney', 'url' => 'https://www.infomoney.com.br/feed/' ),
				array( 'title' => 'Exame', 'url' => 'https://exame.com/feed/' ),
				array( 'title' => 'Money Times', 'url' => 'https://www.moneytimes.com.br/feed/' ),
				array( 'title' => 'Folha Em cima da Hora', 'url' => 'https://feeds.folha.uol.com.br/emcimadahora/rss091.xml' ),
			),
		),
		'mercados'      => array(
			'name'        => 'Mercados',
			'description' => 'Bolsa, juros, câmbio, commodities e movimentos de mercado.',
			'feeds'       => array(
				array( 'title' => 'Folha Mercado', 'url' => 'https://feeds.folha.uol.com.br/mercado/rss091.xml' ),
				array( 'title' => 'Investing.com Brasil', 'url' => 'https://br.investing.com/rss/news.rss' ),
				array( 'title' => 'MarketWatch', 'url' => 'https://feeds.marketwatch.com/marketwatch/topstories/' ),
				array( 'title' => 'CNBC Top News', 'url' => 'https://www.cnbc.com/id/100003114/device/rss/rss.html' ),
				array( 'title' => 'BBC Business', 'url' => 'https://feeds.bbci.co.uk/news/business/rss.xml' ),
			),
		),
		'negocios'      => array(
			'name'        => 'Negócios',
			'description' => 'Empresas, fusões, M&A, empreendedorismo e setor corporativo.',
			'feeds'       => array(
				array( 'title' => 'G1 PME & Negócios', 'url' => 'https://g1.globo.com/rss/g1/economia/pme/' ),
				array( 'title' => 'Exame Negócios', 'url' => 'https://exame.com/feed/' ),
				array( 'title' => 'Financial Times', 'url' => 'https://www.ft.com/rss/home' ),
			),
		),
		'brasil'        => array(
			'name'        => 'Brasil',
			'description' => 'Notícias nacionais, sociedade e agenda do país.',
			'feeds'       => array(
				array( 'title' => 'G1 Brasil', 'url' => 'https://g1.globo.com/rss/g1/brasil/' ),
				array( 'title' => 'BBC Brasil', 'url' => 'https://www.bbc.com/portuguese/index.xml' ),
			),
		),
		'politica'      => array(
			'name'        => 'Política',
			'description' => 'Governo, Congresso, eleições e decisões que impactam a economia.',
			'feeds'       => array(
				array( 'title' => 'G1 Política', 'url' => 'https://g1.globo.com/rss/g1/politica/' ),
			),
		),
		'tecnologia'    => array(
			'name'        => 'Tecnologia',
			'description' => 'Inovação, startups, big tech e transformação digital.',
			'feeds'       => array(
				array( 'title' => 'G1 Tecnologia', 'url' => 'https://g1.globo.com/rss/g1/tecnologia/' ),
				array( 'title' => 'TecMundo', 'url' => 'https://www.tecmundo.com.br/rss' ),
				array( 'title' => 'Canaltech', 'url' => 'https://canaltech.com.br/rss/' ),
			),
		),
		'mundo'         => array(
			'name'        => 'Mundo',
			'description' => 'Geopolítica, economia global e principais eventos internacionais.',
			'feeds'       => array(
				array( 'title' => 'G1 Mundo', 'url' => 'https://g1.globo.com/rss/g1/mundo/' ),
				array( 'title' => 'BBC Brasil', 'url' => 'https://www.bbc.com/portuguese/index.xml' ),
				array( 'title' => 'BBC Business', 'url' => 'https://feeds.bbci.co.uk/news/business/rss.xml' ),
			),
		),
		'criptomoedas'  => array(
			'name'        => 'Criptomoedas',
			'description' => 'Bitcoin, altcoins, regulação e mercado cripto.',
			'feeds'       => array(
				array( 'title' => 'Livecoins', 'url' => 'https://livecoins.com.br/feed/' ),
				array( 'title' => 'Portal do Bitcoin', 'url' => 'https://portaldobitcoin.uol.com.br/feed/' ),
				array( 'title' => 'CriptoFácil', 'url' => 'https://www.criptofacil.com/feed/' ),
			),
		),
		'agronegocio'   => array(
			'name'        => 'Agronegócio',
			'description' => 'Safra, commodities agrícolas, clima e exportações.',
			'feeds'       => array(
				array( 'title' => 'G1 Agronegócios', 'url' => 'https://g1.globo.com/rss/g1/economia/agronegocios/' ),
				array( 'title' => 'Agrolink', 'url' => 'https://www.agrolink.com.br/rss/noticias.xml' ),
			),
		),
	);
}

/**
 * @return array<string, int>
 */
function estrato_rss_create_categories() {
	$created = array();
	foreach ( estrato_rss_get_config() as $slug => $data ) {
		$existing = get_term_by( 'slug', $slug, 'category' );
		if ( $existing ) {
			$created[ $slug ] = (int) $existing->term_id;
			continue;
		}
		$result = wp_insert_term(
			$data['name'],
			'category',
			array(
				'slug'        => $slug,
				'description' => $data['description'],
			)
		);
		if ( ! is_wp_error( $result ) ) {
			$created[ $slug ] = (int) $result['term_id'];
		}
	}
	return $created;
}

function estrato_rss_activate() {
	estrato_rss_create_categories();
	update_option( ESTRATO_RSS_OPTION_FEEDS, estrato_rss_get_config(), false );
	if ( ! wp_next_scheduled( ESTRATO_RSS_CRON_HOOK ) ) {
		wp_schedule_event( time() + 120, 'every_thirty_minutes', ESTRATO_RSS_CRON_HOOK );
	}
	estrato_rss_run_import();
}

function estrato_rss_deactivate() {
	$timestamp = wp_next_scheduled( ESTRATO_RSS_CRON_HOOK );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, ESTRATO_RSS_CRON_HOOK );
	}
}

add_filter(
	'cron_schedules',
	function ( $schedules ) {
		$schedules['every_thirty_minutes'] = array(
			'interval' => 30 * MINUTE_IN_SECONDS,
			'display'  => 'A cada 30 minutos',
		);
		return $schedules;
	}
);

/**
 * @return array<string, bool>
 */
function estrato_rss_get_imported_guids() {
	$guids = get_option( ESTRATO_RSS_OPTION_IMPORTED, array() );
	return is_array( $guids ) ? $guids : array();
}

/**
 * @param string $guid
 */
function estrato_rss_mark_guid_imported( $guid ) {
	$guids              = estrato_rss_get_imported_guids();
	$guids[ $guid ]     = true;
	if ( count( $guids ) > 5000 ) {
		$guids = array_slice( $guids, -4000, null, true );
	}
	update_option( ESTRATO_RSS_OPTION_IMPORTED, $guids, false );
}

/**
 * @return array{imported:int, skipped:int, errors:int}
 */
function estrato_rss_run_import() {
	if ( ! function_exists( 'fetch_feed' ) ) {
		require_once ABSPATH . WPINC . '/feed.php';
	}

	$categories = estrato_rss_create_categories();
	$config     = estrato_rss_get_config();
	$imported   = estrato_rss_get_imported_guids();
	$stats      = array(
		'imported' => 0,
		'skipped'  => 0,
		'errors'   => 0,
	);

	foreach ( $config as $slug => $data ) {
		if ( empty( $categories[ $slug ] ) ) {
			continue;
		}
		$term_id = $categories[ $slug ];

		foreach ( $data['feeds'] as $feed_info ) {
			$feed = fetch_feed( $feed_info['url'] );
			if ( is_wp_error( $feed ) ) {
				$stats['errors']++;
				continue;
			}

			$items = $feed->get_items( 0, 5 );
			foreach ( $items as $item ) {
				$guid = $item->get_id() ? $item->get_id() : $item->get_permalink();
				if ( ! $guid || isset( $imported[ $guid ] ) ) {
					$stats['skipped']++;
					continue;
				}

				$title = wp_strip_all_tags( $item->get_title() );
				if ( '' === $title ) {
					$stats['skipped']++;
					continue;
				}

				$content  = $item->get_content();
				$excerpt  = $item->get_description();
				$body     = $content ? $content : $excerpt;
				$body     = wp_kses_post( $body );
				$link     = esc_url( $item->get_permalink() );
				$source   = esc_html( $feed_info['title'] );
				$footer   = sprintf(
					'<p><em>Fonte: <a href="%1$s" target="_blank" rel="nofollow noopener">%2$s</a></em></p>',
					$link,
					$source
				);
				$post_arr = array(
					'post_title'   => $title,
					'post_content' => $body . $footer,
					'post_status'  => 'publish',
					'post_author'  => 1,
					'post_category'=> array( $term_id ),
					'post_date'    => $item->get_date( 'Y-m-d H:i:s' ) ? $item->get_date( 'Y-m-d H:i:s' ) : current_time( 'mysql' ),
				);

				$post_id = wp_insert_post( $post_arr, true );
				if ( is_wp_error( $post_id ) ) {
					$stats['errors']++;
					continue;
				}

				update_post_meta( $post_id, '_estrato_rss_guid', $guid );
				update_post_meta( $post_id, '_estrato_rss_source', $feed_info['title'] );
				update_post_meta( $post_id, '_estrato_rss_source_url', $feed_info['url'] );
				estrato_rss_mark_guid_imported( $guid );
				$imported[ $guid ] = true;
				$stats['imported']++;
			}
		}
	}

	set_transient( 'estrato_rss_last_stats', $stats, DAY_IN_SECONDS );
	return $stats;
}

function estrato_rss_admin_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$stats = get_transient( 'estrato_rss_last_stats' );
	if ( ! $stats ) {
		return;
	}
	printf(
		'<div class="notice notice-success is-dismissible"><p><strong>Estrato RSS:</strong> última importação — %1$d novas, %2$d ignoradas, %3$d erros.</p></div>',
		(int) $stats['imported'],
		(int) $stats['skipped'],
		(int) $stats['errors']
	);
}

function estrato_rss_admin_menu() {
	add_management_page(
		'Estrato RSS',
		'Estrato RSS',
		'manage_options',
		'estrato-rss',
		'estrato_rss_admin_page'
	);
}

function estrato_rss_admin_page() {
	if ( isset( $_POST['estrato_rss_run_now'] ) && check_admin_referer( 'estrato_rss_run_now' ) ) {
		$stats = estrato_rss_run_import();
		echo '<div class="notice notice-success"><p>Importação manual concluída: ' . esc_html( wp_json_encode( $stats ) ) . '</p></div>';
	}

	echo '<div class="wrap"><h1>Estrato RSS</h1>';
	echo '<p>Importação automática a cada 30 minutos. Fontes configuradas por categoria.</p>';
	echo '<form method="post">';
	wp_nonce_field( 'estrato_rss_run_now' );
	echo '<p><button class="button button-primary" name="estrato_rss_run_now" value="1">Importar agora</button></p>';
	echo '</form><h2>Categorias e feeds</h2><table class="widefat"><thead><tr><th>Categoria</th><th>Fonte</th><th>URL</th></tr></thead><tbody>';
	foreach ( estrato_rss_get_config() as $slug => $data ) {
		foreach ( $data['feeds'] as $feed ) {
			echo '<tr><td>' . esc_html( $data['name'] ) . '</td><td>' . esc_html( $feed['title'] ) . '</td><td><code>' . esc_html( $feed['url'] ) . '</code></td></tr>';
		}
	}
	echo '</tbody></table></div>';
}
