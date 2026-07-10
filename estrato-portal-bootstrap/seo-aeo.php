<?php
/**
 * AEO / GEO / Indexação — Sprint 6 (llms.txt, FAQ hubs, robots IA).
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ESTRATO_INDEXNOW_KEY_OPTION', 'estrato_indexnow_key' );
define( 'ESTRATO_INDEXNOW_ENABLED_OPTION', 'estrato_bridge_indexnow_enabled' );
define( 'ESTRATO_NEWS_SITEMAP_CRON', 'estrato_news_sitemap_hourly' );

/**
 * @return array<int, string>
 */
function estrato_aeo_ai_bot_agents() {
	return array(
		'GPTBot',
		'ChatGPT-User',
		'CCBot',
		'anthropic-ai',
		'ClaudeBot',
		'PerplexityBot',
		'Bytespider',
		'cohere-ai',
	);
}

/**
 * Regras robots para crawlers de IA (BPMoney / Folha).
 *
 * @param string $output
 * @param bool   $public
 * @return string
 */
function estrato_aeo_filter_robots_txt( $output, $public ) {
	if ( ! $public ) {
		return $output;
	}

	if ( false !== stripos( $output, 'GPTBot' ) ) {
		return $output;
	}

	$extra = "\n# Estrato AEO/GEO — crawlers IA (Sprint 6)\n";
	foreach ( estrato_aeo_ai_bot_agents() as $agent ) {
		$extra .= "User-agent: {$agent}\nDisallow: /\n";
	}

	$domain = wp_parse_url( home_url(), PHP_URL_HOST );
	if ( $domain && false === stripos( $output, 'llms.txt' ) ) {
		$extra .= "\n# GEO\n";
		$extra .= "# llms: https://{$domain}/llms.txt\n";
	}

	return rtrim( $output ) . "\n" . $extra;
}
add_filter( 'robots_txt', 'estrato_aeo_filter_robots_txt', 1000001, 2 );

/**
 * FAQPage JSON-LD nas páginas hub /tudo-sobre/*.
 */
function estrato_aeo_output_hub_faq_schema() {
	if ( ! is_page() ) {
		return;
	}

	$post = get_queried_object();
	if ( ! $post || empty( $post->post_name ) ) {
		return;
	}

	$parent      = (int) $post->post_parent;
	$parent_slug = $parent ? get_post_field( 'post_name', $parent ) : '';
	if ( 'tudo-sobre' !== $parent_slug && 'tudo-sobre' !== $post->post_name ) {
		return;
	}

	$faqs = estrato_aeo_get_hub_faqs( $post->post_name, get_the_title( $post ) );
	if ( count( $faqs ) < 3 ) {
		return;
	}

	$entities = array();
	foreach ( $faqs as $faq ) {
		$entities[] = array(
			'@type'          => 'Question',
			'name'           => $faq['q'],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $faq['a'],
			),
		);
	}

	$schema = array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => $entities,
	);

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}
add_action( 'wp_head', 'estrato_aeo_output_hub_faq_schema', 8 );

/**
 * @param string $slug
 * @param string $title
 * @return array<int, array{q:string,a:string}>
 */
function estrato_aeo_get_hub_faqs( $slug, $title ) {
	$title = $title ? $title : ucfirst( $slug );

	return array(
		array(
			'q' => sprintf( 'O que é %s e por que importa?', wp_trim_words( $title, 6, '' ) ),
			'a' => 'É um tema central da economia brasileira monitorado pela redação do Estrato, com impacto em juros, preços, empresas e investimentos.',
		),
		array(
			'q' => 'Com que frequência o Estrato atualiza esta hub?',
			'a' => 'Publicamos novas matérias diariamente e revisamos o contexto quando há dados oficiais do Banco Central, IBGE, B3 ou mudanças regulatórias.',
		),
		array(
			'q' => 'Onde encontrar cotações e dados de mercado?',
			'a' => 'Consulte a página de Cotações em https://estrato.cc/cotacoes/ e a editoria Mercados para análises do Ibovespa, dólar e juros.',
		),
	);
}

/**
 * @return int Hubs com FAQPage no HTML.
 */
function estrato_regression_hub_faq_schema() {
	$count  = 0;
	$parent = get_page_by_path( 'tudo-sobre', OBJECT, 'page' );
	if ( ! $parent ) {
		return 0;
	}

	$kids = get_children(
		array(
			'post_parent' => $parent->ID,
			'post_type'   => 'page',
			'post_status' => 'publish',
		)
	);

	foreach ( $kids as $page ) {
		$url = get_permalink( $page );
		if ( ! $url ) {
			continue;
		}
		$response = wp_remote_get( $url, array( 'timeout' => 15 ) );
		$html     = is_wp_error( $response ) ? '' : wp_remote_retrieve_body( $response );
		if ( $html && false !== stripos( $html, 'FAQPage' ) ) {
			++$count;
		}
	}

	return $count;
}

/**
 * Gera ou retorna chave IndexNow.
 *
 * @return string
 */
function estrato_aeo_get_indexnow_key() {
	$key = get_option( ESTRATO_INDEXNOW_KEY_OPTION, '' );
	if ( $key && is_string( $key ) ) {
		return $key;
	}

	$key = 'estrato-' . wp_generate_password( 24, false, false );
	update_option( ESTRATO_INDEXNOW_KEY_OPTION, $key, false );
	return $key;
}

/**
 * Grava arquivo de chave IndexNow na raiz do site.
 *
 * @return bool
 */
function estrato_aeo_write_indexnow_key_file() {
	$key  = estrato_aeo_get_indexnow_key();
	$path = ABSPATH . 'estrato-indexnow-key.txt';

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	return false !== file_put_contents( $path, $key . "\n", LOCK_EX );
}

/**
 * Copia assets GEO (llms.txt, ads.txt) para a raiz.
 *
 * @return array<string, bool>
 */
function estrato_aeo_deploy_static_files() {
	$base   = plugin_dir_path( __FILE__ ) . 'assets/';
	$files  = array( 'llms.txt', 'llms-full.txt', 'ads.txt' );
	$result = array();

	foreach ( $files as $file ) {
		$src = $base . $file;
		$dst = ABSPATH . $file;
		if ( ! is_readable( $src ) ) {
			$result[ $file ] = false;
			continue;
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$ok              = file_put_contents( $dst, file_get_contents( $src ), LOCK_EX );
		$result[ $file ] = false !== $ok;
	}

	$result['estrato-indexnow-key.txt'] = estrato_aeo_write_indexnow_key_file();
	update_option( ESTRATO_INDEXNOW_ENABLED_OPTION, true, false );

	return $result;
}

/**
 * Ping IndexNow após publicação.
 *
 * @param int $post_id
 */
function estrato_aeo_ping_indexnow( $post_id ) {
	if ( ! get_option( ESTRATO_INDEXNOW_ENABLED_OPTION, false ) ) {
		return;
	}

	$post = get_post( $post_id );
	if ( ! $post || 'publish' !== $post->post_status || 'post' !== $post->post_type ) {
		return;
	}

	$url  = get_permalink( $post_id );
	$key  = estrato_aeo_get_indexnow_key();
	$host = wp_parse_url( home_url(), PHP_URL_HOST );

	if ( ! $url || ! $host || ! $key ) {
		return;
	}

	wp_remote_post(
		'https://api.indexnow.org/indexnow',
		array(
			'timeout' => 10,
			'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			'body'    => wp_json_encode(
				array(
					'host'        => $host,
					'key'         => $key,
					'keyLocation' => home_url( '/estrato-indexnow-key.txt' ),
					'urlList'     => array( $url ),
				)
			),
		)
	);
}
add_action( 'publish_post', 'estrato_aeo_ping_indexnow', 20, 1 );

/**
 * Ping sitemaps (Google/Bing) — hourly cron.
 */
function estrato_aeo_ping_sitemaps_cron() {
	$sitemaps = array(
		home_url( '/sitemap_index.xml' ),
		home_url( '/news-sitemap.xml' ),
	);

	foreach ( $sitemaps as $sm ) {
		wp_remote_get(
			'https://www.google.com/ping?sitemap=' . rawurlencode( $sm ),
			array( 'timeout' => 10 )
		);
		wp_remote_get(
			'https://www.bing.com/ping?sitemap=' . rawurlencode( $sm ),
			array( 'timeout' => 10 )
		);
	}

	set_transient( 'estrato_last_sitemap_ping', time(), DAY_IN_SECONDS );
}
add_action( ESTRATO_NEWS_SITEMAP_CRON, 'estrato_aeo_ping_sitemaps_cron' );

/**
 * Agenda cron horário para sitemap ping.
 */
function estrato_aeo_schedule_cron() {
	if ( ! wp_next_scheduled( ESTRATO_NEWS_SITEMAP_CRON ) ) {
		wp_schedule_event( time() + 300, 'hourly', ESTRATO_NEWS_SITEMAP_CRON );
	}
}
add_action( 'init', 'estrato_aeo_schedule_cron' );

/**
 * Timezone America/Sao_Paulo para news-sitemap (ND Mais).
 */
function estrato_aeo_ensure_timezone() {
	if ( 'America/Sao_Paulo' !== get_option( 'timezone_string' ) ) {
		update_option( 'timezone_string', 'America/Sao_Paulo' );
	}
}
