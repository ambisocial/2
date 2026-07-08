<?php
/**
 * Plugin Name: Estrato RSS Bootstrap
 * Description: Cria categorias, remove posts de exemplo e importa notícias reais via RSS.
 * Version: 1.1.0
 * Author: Cursor Agent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ESTRATO_RSS_VERSION', '1.1.0' );
define( 'ESTRATO_RSS_CRON_HOOK', 'estrato_rss_import_event' );
define( 'ESTRATO_RSS_OPTION_FEEDS', 'estrato_rss_feeds_config' );
define( 'ESTRATO_RSS_OPTION_IMPORTED', 'estrato_rss_imported_guids' );
define( 'ESTRATO_RSS_OPTION_SETTINGS', 'estrato_rss_settings' );

register_activation_hook( __FILE__, 'estrato_rss_activate' );
register_deactivation_hook( __FILE__, 'estrato_rss_deactivate' );
add_action( ESTRATO_RSS_CRON_HOOK, 'estrato_rss_run_import' );
add_action( 'admin_notices', 'estrato_rss_admin_notice' );
add_action( 'admin_menu', 'estrato_rss_admin_menu' );
add_action( 'plugins_loaded', 'estrato_rss_maybe_upgrade' );

/**
 * Default plugin settings.
 *
 * @return array{items_per_feed:int,items_first_run:int,max_per_run:int,cron_schedule:string}
 */
function estrato_rss_default_settings() {
	return array(
		'items_per_feed'   => 5,
		'items_first_run'  => 12,
		'max_per_run'      => 120,
		'cron_schedule'    => 'every_thirty_minutes',
	);
}

/**
 * @return array{items_per_feed:int,items_first_run:int,max_per_run:int,cron_schedule:string}
 */
function estrato_rss_get_settings() {
	$settings = get_option( ESTRATO_RSS_OPTION_SETTINGS, array() );
	if ( ! is_array( $settings ) ) {
		$settings = array();
	}
	return array_merge( estrato_rss_default_settings(), $settings );
}

/**
 * @return array<string, string>
 */
function estrato_rss_cron_options() {
	return array(
		'every_fifteen_minutes' => 'A cada 15 minutos',
		'every_thirty_minutes'  => 'A cada 30 minutos',
		'hourly'                => 'A cada 1 hora',
		'twicedaily'            => 'A cada 12 horas',
		'daily'                 => '1 vez por dia',
	);
}

add_filter(
	'cron_schedules',
	function ( $schedules ) {
		$schedules['every_fifteen_minutes'] = array(
			'interval' => 15 * MINUTE_IN_SECONDS,
			'display'  => 'A cada 15 minutos',
		);
		$schedules['every_thirty_minutes']  = array(
			'interval' => 30 * MINUTE_IN_SECONDS,
			'display'  => 'A cada 30 minutos',
		);
		return $schedules;
	}
);

/**
 * Category and feed map.
 *
 * @return array<string, array{name:string, description:string, feeds:array<int, array{title:string,url:string}>}>
 */
function estrato_rss_get_config() {
	return array(
		'economia'     => array(
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
		'mercados'     => array(
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
		'negocios'     => array(
			'name'        => 'Negócios',
			'description' => 'Empresas, fusões, M&A, empreendedorismo e setor corporativo.',
			'feeds'       => array(
				array( 'title' => 'G1 PME & Negócios', 'url' => 'https://g1.globo.com/rss/g1/economia/pme/' ),
				array( 'title' => 'Exame Negócios', 'url' => 'https://exame.com/feed/' ),
				array( 'title' => 'Financial Times', 'url' => 'https://www.ft.com/rss/home' ),
			),
		),
		'brasil'       => array(
			'name'        => 'Brasil',
			'description' => 'Notícias nacionais, sociedade e agenda do país.',
			'feeds'       => array(
				array( 'title' => 'G1 Brasil', 'url' => 'https://g1.globo.com/rss/g1/brasil/' ),
				array( 'title' => 'BBC Brasil', 'url' => 'https://www.bbc.com/portuguese/index.xml' ),
			),
		),
		'politica'     => array(
			'name'        => 'Política',
			'description' => 'Governo, Congresso, eleições e decisões que impactam a economia.',
			'feeds'       => array(
				array( 'title' => 'G1 Política', 'url' => 'https://g1.globo.com/rss/g1/politica/' ),
			),
		),
		'tecnologia'   => array(
			'name'        => 'Tecnologia',
			'description' => 'Inovação, startups, big tech e transformação digital.',
			'feeds'       => array(
				array( 'title' => 'G1 Tecnologia', 'url' => 'https://g1.globo.com/rss/g1/tecnologia/' ),
				array( 'title' => 'TecMundo', 'url' => 'https://www.tecmundo.com.br/rss' ),
				array( 'title' => 'Canaltech', 'url' => 'https://canaltech.com.br/rss/' ),
			),
		),
		'mundo'        => array(
			'name'        => 'Mundo',
			'description' => 'Geopolítica, economia global e principais eventos internacionais.',
			'feeds'       => array(
				array( 'title' => 'G1 Mundo', 'url' => 'https://g1.globo.com/rss/g1/mundo/' ),
				array( 'title' => 'BBC Brasil', 'url' => 'https://www.bbc.com/portuguese/index.xml' ),
				array( 'title' => 'BBC Business', 'url' => 'https://feeds.bbci.co.uk/news/business/rss.xml' ),
			),
		),
		'criptomoedas' => array(
			'name'        => 'Criptomoedas',
			'description' => 'Bitcoin, altcoins, regulação e mercado cripto.',
			'feeds'       => array(
				array( 'title' => 'Livecoins', 'url' => 'https://livecoins.com.br/feed/' ),
				array( 'title' => 'Portal do Bitcoin', 'url' => 'https://portaldobitcoin.uol.com.br/feed/' ),
				array( 'title' => 'CriptoFácil', 'url' => 'https://www.criptofacil.com/feed/' ),
			),
		),
		'agronegocio'  => array(
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

/**
 * Remove posts de exemplo/demo que não vieram do RSS.
 *
 * @return int Number of deleted posts.
 */
function estrato_rss_remove_sample_posts() {
	$post_ids = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	$deleted = 0;
	foreach ( $post_ids as $post_id ) {
		if ( get_post_meta( $post_id, '_estrato_rss_guid', true ) ) {
			continue;
		}
		wp_delete_post( (int) $post_id, true );
		$deleted++;
	}

	set_transient( 'estrato_rss_last_cleanup', $deleted, DAY_IN_SECONDS );
	return $deleted;
}

/**
 * @param string $schedule
 */
function estrato_rss_reschedule_cron( $schedule ) {
	$timestamp = wp_next_scheduled( ESTRATO_RSS_CRON_HOOK );
	while ( $timestamp ) {
		wp_unschedule_event( $timestamp, ESTRATO_RSS_CRON_HOOK );
		$timestamp = wp_next_scheduled( ESTRATO_RSS_CRON_HOOK );
	}
	wp_schedule_event( time() + 90, $schedule, ESTRATO_RSS_CRON_HOOK );
}

function estrato_rss_activate() {
	$settings = estrato_rss_get_settings();
	if ( ! get_option( ESTRATO_RSS_OPTION_SETTINGS ) ) {
		update_option( ESTRATO_RSS_OPTION_SETTINGS, estrato_rss_default_settings(), false );
	}

	estrato_rss_create_categories();
	update_option( ESTRATO_RSS_OPTION_FEEDS, estrato_rss_get_config(), false );
	estrato_rss_reschedule_cron( $settings['cron_schedule'] );

	$removed = estrato_rss_remove_sample_posts();
	$stats   = estrato_rss_run_import( true );

	set_transient(
		'estrato_rss_activation_report',
		array(
			'removed' => $removed,
			'stats'   => $stats,
		),
		DAY_IN_SECONDS
	);
}

function estrato_rss_deactivate() {
	$timestamp = wp_next_scheduled( ESTRATO_RSS_CRON_HOOK );
	while ( $timestamp ) {
		wp_unschedule_event( $timestamp, ESTRATO_RSS_CRON_HOOK );
		$timestamp = wp_next_scheduled( ESTRATO_RSS_CRON_HOOK );
	}
}

function estrato_rss_maybe_upgrade() {
	$stored = get_option( 'estrato_rss_plugin_version', '0' );
	if ( version_compare( $stored, ESTRATO_RSS_VERSION, '>=' ) ) {
		return;
	}
	update_option( 'estrato_rss_plugin_version', ESTRATO_RSS_VERSION, false );
	estrato_rss_create_categories();
	$settings = estrato_rss_get_settings();
	estrato_rss_reschedule_cron( $settings['cron_schedule'] );
}

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
	$guids          = estrato_rss_get_imported_guids();
	$guids[ $guid ] = true;
	if ( count( $guids ) > 5000 ) {
		$guids = array_slice( $guids, -4000, null, true );
	}
	update_option( ESTRATO_RSS_OPTION_IMPORTED, $guids, false );
}

/**
 * @param bool $first_run
 * @return array{imported:int, skipped:int, errors:int, deleted:int}
 */
function estrato_rss_run_import( $first_run = false ) {
	if ( ! function_exists( 'fetch_feed' ) ) {
		require_once ABSPATH . WPINC . '/feed.php';
	}

	$settings   = estrato_rss_get_settings();
	$per_feed   = $first_run ? (int) $settings['items_first_run'] : (int) $settings['items_per_feed'];
	$per_feed   = max( 1, min( 20, $per_feed ) );
	$max_run    = max( 1, min( 300, (int) $settings['max_per_run'] ) );
	$categories = estrato_rss_create_categories();
	$config     = estrato_rss_get_config();
	$imported   = estrato_rss_get_imported_guids();
	$stats      = array(
		'imported' => 0,
		'skipped'  => 0,
		'errors'   => 0,
		'deleted'  => 0,
	);

	foreach ( $config as $slug => $data ) {
		if ( empty( $categories[ $slug ] ) || $stats['imported'] >= $max_run ) {
			continue;
		}
		$term_id = $categories[ $slug ];

		foreach ( $data['feeds'] as $feed_info ) {
			if ( $stats['imported'] >= $max_run ) {
				break;
			}

			$feed = fetch_feed( $feed_info['url'] );
			if ( is_wp_error( $feed ) ) {
				$stats['errors']++;
				continue;
			}

			$items = $feed->get_items( 0, $per_feed );
			foreach ( $items as $item ) {
				if ( $stats['imported'] >= $max_run ) {
					break;
				}

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

				$content = $item->get_content();
				$excerpt = $item->get_description();
				$body    = $content ? $content : $excerpt;
				$body    = wp_kses_post( $body );
				$link    = esc_url( $item->get_permalink() );
				$source  = esc_html( $feed_info['title'] );
				$footer  = sprintf(
					'<p><em>Fonte: <a href="%1$s" target="_blank" rel="nofollow noopener">%2$s</a></em></p>',
					$link,
					$source
				);

				$post_id = wp_insert_post(
					array(
						'post_title'    => $title,
						'post_content'  => $body . $footer,
						'post_status'   => 'publish',
						'post_author'   => 1,
						'post_category' => array( $term_id ),
						'post_date'     => $item->get_date( 'Y-m-d H:i:s' ) ? $item->get_date( 'Y-m-d H:i:s' ) : current_time( 'mysql' ),
					),
					true
				);

				if ( is_wp_error( $post_id ) ) {
					$stats['errors']++;
					continue;
				}

				update_post_meta( $post_id, '_estrato_rss_guid', $guid );
				update_post_meta( $post_id, '_estrato_rss_source', $feed_info['title'] );
				update_post_meta( $post_id, '_estrato_rss_source_url', $feed_info['url'] );
				estrato_rss_mark_guid_imported( $guid );
				$imported[ $guid ]      = true;
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

	$activation = get_transient( 'estrato_rss_activation_report' );
	if ( $activation ) {
		printf(
			'<div class="notice notice-success is-dismissible"><p><strong>Estrato RSS:</strong> removidos %1$d posts de exemplo. Importadas %2$d notícias reais (%3$d ignoradas, %4$d erros).</p></div>',
			(int) $activation['removed'],
			(int) $activation['stats']['imported'],
			(int) $activation['stats']['skipped'],
			(int) $activation['stats']['errors']
		);
		delete_transient( 'estrato_rss_activation_report' );
	}

	$stats = get_transient( 'estrato_rss_last_stats' );
	if ( $stats ) {
		printf(
			'<div class="notice notice-info is-dismissible"><p><strong>Estrato RSS:</strong> última importação — %1$d novas, %2$d ignoradas, %3$d erros.</p></div>',
			(int) $stats['imported'],
			(int) $stats['skipped'],
			(int) $stats['errors']
		);
	}
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
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$settings = estrato_rss_get_settings();

	if ( isset( $_POST['estrato_rss_save_settings'] ) && check_admin_referer( 'estrato_rss_settings' ) ) {
		$settings['items_per_feed']  = max( 1, min( 20, (int) $_POST['items_per_feed'] ) );
		$settings['items_first_run'] = max( 1, min( 20, (int) $_POST['items_first_run'] ) );
		$settings['max_per_run']     = max( 10, min( 300, (int) $_POST['max_per_run'] ) );
		$cron                        = sanitize_key( wp_unslash( $_POST['cron_schedule'] ) );
		$options                     = estrato_rss_cron_options();
		if ( isset( $options[ $cron ] ) ) {
			$settings['cron_schedule'] = $cron;
		}
		update_option( ESTRATO_RSS_OPTION_SETTINGS, $settings, false );
		estrato_rss_reschedule_cron( $settings['cron_schedule'] );
		echo '<div class="notice notice-success"><p>Configurações salvas.</p></div>';
	}

	if ( isset( $_POST['estrato_rss_cleanup_import'] ) && check_admin_referer( 'estrato_rss_cleanup_import' ) ) {
		$removed = estrato_rss_remove_sample_posts();
		$stats   = estrato_rss_run_import( true );
		echo '<div class="notice notice-success"><p>Removidos ' . esc_html( (string) $removed ) . ' posts de exemplo. Importação: ' . esc_html( wp_json_encode( $stats ) ) . '</p></div>';
	}

	if ( isset( $_POST['estrato_rss_run_now'] ) && check_admin_referer( 'estrato_rss_run_now' ) ) {
		$stats = estrato_rss_run_import( false );
		echo '<div class="notice notice-success"><p>Importação manual: ' . esc_html( wp_json_encode( $stats ) ) . '</p></div>';
	}

	$next_cron = wp_next_scheduled( ESTRATO_RSS_CRON_HOOK );
	$cron_opts = estrato_rss_cron_options();

	echo '<div class="wrap"><h1>Estrato RSS</h1>';
	echo '<p>Remove posts de exemplo e importa notícias reais dos feeds configurados.</p>';

	echo '<h2>Configurações</h2><form method="post">';
	wp_nonce_field( 'estrato_rss_settings' );
	echo '<table class="form-table"><tbody>';
	echo '<tr><th>Itens por feed (cron normal)</th><td><input type="number" min="1" max="20" name="items_per_feed" value="' . esc_attr( (string) $settings['items_per_feed'] ) . '"><p class="description">Quantas notícias buscar de cada feed a cada execução (1–20). Padrão: 5.</p></td></tr>';
	echo '<tr><th>Itens por feed (1ª importação)</th><td><input type="number" min="1" max="20" name="items_first_run" value="' . esc_attr( (string) $settings['items_first_run'] ) . '"><p class="description">Usado ao ativar o plugin ou em “Limpar exemplos e importar”. Padrão: 12.</p></td></tr>';
	echo '<tr><th>Máximo por execução</th><td><input type="number" min="10" max="300" name="max_per_run" value="' . esc_attr( (string) $settings['max_per_run'] ) . '"><p class="description">Teto total de posts criados por execução do cron. Padrão: 120.</p></td></tr>';
	echo '<tr><th>Cron (frequência)</th><td><select name="cron_schedule">';
	foreach ( $cron_opts as $key => $label ) {
		echo '<option value="' . esc_attr( $key ) . '"' . selected( $settings['cron_schedule'], $key, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select><p class="description">Próxima execução: ' . ( $next_cron ? esc_html( wp_date( 'd/m/Y H:i', $next_cron ) ) : 'não agendada' ) . '</p></td></tr>';
	echo '</tbody></table><p><button class="button button-primary" name="estrato_rss_save_settings" value="1">Salvar configurações</button></p></form>';

	echo '<h2>Ações</h2><form method="post" style="display:inline;margin-right:8px;">';
	wp_nonce_field( 'estrato_rss_cleanup_import' );
	echo '<button class="button button-secondary" name="estrato_rss_cleanup_import" value="1">Limpar posts de exemplo e importar RSS</button></form>';
	echo '<form method="post" style="display:inline;">';
	wp_nonce_field( 'estrato_rss_run_now' );
	echo '<button class="button" name="estrato_rss_run_now" value="1">Importar agora (sem limpar)</button></form>';

	echo '<h2>Categorias e feeds</h2><table class="widefat"><thead><tr><th>Categoria</th><th>Fonte</th><th>URL</th></tr></thead><tbody>';
	foreach ( estrato_rss_get_config() as $data ) {
		foreach ( $data['feeds'] as $feed ) {
			echo '<tr><td>' . esc_html( $data['name'] ) . '</td><td>' . esc_html( $feed['title'] ) . '</td><td><code>' . esc_html( $feed['url'] ) . '</code></td></tr>';
		}
	}
	echo '</tbody></table></div>';
}
