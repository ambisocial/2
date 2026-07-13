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
	$title      = $title ? $title : ucfirst( $slug );
	$blog       = get_bloginfo( 'name' );
	$is_finance = ! function_exists( 'estrato_nav_current_portal_id' )
		|| 'estrato-finance' === estrato_nav_current_portal_id();

	$thematic = estrato_aeo_hub_faq_themes();
	if ( isset( $thematic[ $slug ] ) ) {
		return $thematic[ $slug ];
	}

	$term   = null;
	$parent = get_page_by_path( 'tudo-sobre/' . $slug, OBJECT, 'page' );
	if ( $parent && preg_match( '/category="([^"]+)"/', $parent->post_content, $m ) ) {
		$term = get_term_by( 'slug', $m[1], 'category' );
	}

	$faqs = array(
		array(
			'q' => sprintf( 'O que é %s e por que importa?', wp_trim_words( $title, 6, '' ) ),
			'a' => $term && ! is_wp_error( $term ) && $term->description
				? wp_strip_all_tags( $term->description )
				: ( $is_finance
					? 'É um tema central da economia brasileira monitorado pela redação do Estrato, com impacto em juros, preços, empresas e investimentos.'
					: sprintf( 'É um tema central de %s no %s, com cobertura editorial contínua e fontes verificáveis.', wp_trim_words( $title, 6, '' ), $blog ) ),
		),
		array(
			'q' => sprintf( 'Com que frequência o %s atualiza esta hub?', $blog ),
			'a' => 'Publicamos novas matérias diariamente e revisamos o contexto quando há dados oficiais ou mudanças relevantes.',
		),
		array(
			'q' => sprintf( 'Como acompanhar %s no dia a dia?', wp_trim_words( $title, 5, '' ) ),
			'a' => $term && ! is_wp_error( $term )
				? 'Acompanhe a editoria ' . $term->name . ' em ' . get_category_link( $term ) . ' e assine a newsletter.'
				: 'Explore as editorias relacionadas no ' . $blog . ' e os guias em /tudo-sobre/.',
		),
	);

	if ( $is_finance ) {
		$faqs[] = array(
			'q' => 'Onde encontrar cotações e dados de mercado?',
			'a' => 'Consulte ' . home_url( '/cotacoes/' ) . ' e a editoria Mercados para Ibovespa, dólar e juros.',
		);
	}

	return array_slice( $faqs, 0, 5 );
}

/**
 * FAQs temáticas por slug de hub.
 *
 * @return array<string, array<int, array{q:string,a:string}>>
 */
function estrato_aeo_hub_faq_themes() {
	return array(
		'selic'       => array(
			array( 'q' => 'O que é a taxa Selic?', 'a' => 'A Selic é a taxa básica de juros da economia brasileira, definida pelo Copom do Banco Central.' ),
			array( 'q' => 'Com que frequência o Copom reúne?', 'a' => 'O Copom se reúne a cada 45 dias em calendário publicado pelo BC.' ),
			array( 'q' => 'Como a Selic afeta o investidor?', 'a' => 'Selic alta eleva renda fixa; Selic em queda favorece ações e crédito, com impacto na inflação.' ),
		),
		'ibovespa'    => array(
			array( 'q' => 'O que é o Ibovespa?', 'a' => 'O Ibovespa é o principal índice da B3, formado pelas ações mais negociadas da bolsa brasileira.' ),
			array( 'q' => 'Quais fatores movem o Ibovespa?', 'a' => 'Juros, câmbio, commodities, fluxo estrangeiro e resultados corporativos explicam as variações.' ),
			array( 'q' => 'Como investir no Ibovespa?', 'a' => 'Via ETFs como BOVA11, fundos de índice ou carteiras que replicam o índice.' ),
		),
		'dolar'       => array(
			array( 'q' => 'Por que o dólar importa para o Brasil?', 'a' => 'O USD/BRL afeta inflação, exportadores, turismo e política monetária.' ),
			array( 'q' => 'O que faz o dólar subir ou cair?', 'a' => 'Fluxo de capital, juros nos EUA, commodities e risco fiscal local são drivers principais.' ),
			array( 'q' => 'Onde acompanhar cotações?', 'a' => 'Na página de Cotações em ' . home_url( '/cotacoes/' ) . '.' ),
		),
		'aprendizado' => array(
			array( 'q' => 'O que é aprendizado e cognição?', 'a' => 'Técnicas de estudo, memória e neurociência aplicada para aprender com método.' ),
			array( 'q' => 'Quais temas são recorrentes?', 'a' => 'Hábitos de leitura, foco profundo e evidências sobre retenção de informação.' ),
			array( 'q' => 'Para quem é esta hub?', 'a' => 'Estudantes e profissionais que buscam produtividade intelectual.' ),
		),
		'neuro'       => array(
			array( 'q' => 'O que a hub Neuro cobre?', 'a' => 'Neurociência, sono e performance cerebral com rigor e clareza.' ),
			array( 'q' => 'As matérias são baseadas em estudos?', 'a' => 'Priorizamos papers revisados por pares e instituições de pesquisa.' ),
			array( 'q' => 'Como isso se conecta ao dia a dia?', 'a' => 'Traduzimos achados científicos em implicações práticas para saúde e trabalho.' ),
		),
		'ia'          => array(
			array( 'q' => 'O que acompanhamos em IA?', 'a' => 'Modelos de linguagem, segurança, regulação e impacto no trabalho.' ),
			array( 'q' => 'Há foco em riscos?', 'a' => 'Sim — viés, privacidade e governança de modelos são temas recorrentes.' ),
			array( 'q' => 'Para quem é o conteúdo?', 'a' => 'Leitores que querem entender IA além do hype, com contexto brasileiro.' ),
		),
		'jogos'       => array(
			array( 'q' => 'Que tipo de jogos cobrimos?', 'a' => 'Videogames, RPG de mesa, indie e lançamentos AAA.' ),
			array( 'q' => 'Há reviews?', 'a' => 'Análises editoriais e curadoria de lançamentos relevantes.' ),
			array( 'q' => 'Como sugerir pauta?', 'a' => 'Envie sugestões pela página de Contato.' ),
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
 * Editorias do portal atual (index_order da taxonomia).
 *
 * @return array<int, string>
 */
function estrato_aeo_portal_editorias() {
	$portal  = function_exists( 'estrato_nav_current_portal_id' ) ? estrato_nav_current_portal_id() : 'estrato-finance';
	$loaders = array(
		'estrato-finance'   => 'estrato_rss_load_finance_taxonomy',
		'estrato-mind'      => 'estrato_rss_load_mind_taxonomy',
		'estrato-lifestyle' => 'estrato_rss_load_lifestyle_taxonomy',
		'estrato-science'   => 'estrato_rss_load_science_taxonomy',
		'estrato-sustain'   => 'estrato_rss_load_sustain_taxonomy',
		'estrato-culture'   => 'estrato_rss_load_culture_taxonomy',
	);
	$loader  = $loaders[ $portal ] ?? null;
	if ( ! $loader || ! function_exists( $loader ) ) {
		return function_exists( 'estrato_rss_get_finance_menu_order' )
			? estrato_rss_get_finance_menu_order()
			: array();
	}
	$taxonomy = $loader();
	return $taxonomy['index_order'] ?? array_keys( $taxonomy['categories'] ?? array() );
}

/**
 * Hubs /tudo-sobre/ publicados.
 *
 * @return array<int, array{slug:string,title:string,url:string}>
 */
function estrato_aeo_portal_hubs() {
	$parent = get_page_by_path( 'tudo-sobre', OBJECT, 'page' );
	if ( ! $parent ) {
		return array();
	}
	$hubs = array();
	foreach (
		get_children(
			array(
				'post_parent' => $parent->ID,
				'post_type'   => 'page',
				'post_status' => 'publish',
			)
		) as $page
	) {
		$hubs[] = array(
			'slug'  => $page->post_name,
			'title' => $page->post_title,
			'url'   => get_permalink( $page ),
		);
	}
	return $hubs;
}

/**
 * @return string
 */
function estrato_aeo_build_llms_txt() {
	$home    = home_url( '/' );
	$blog    = get_bloginfo( 'name' );
	$tagline = get_bloginfo( 'description' );
	$date    = gmdate( 'Y-m-d' );
	$lines   = array(
		'# ' . $blog . ' — llms.txt (GEO / AEO)',
		'# ' . $tagline,
		'# Última atualização: ' . $date,
		'',
		'> ' . $tagline,
		'',
		'## Sobre',
		'',
		'- Site: ' . $home,
		'- Política editorial: ' . home_url( '/politica-editorial/' ),
		'- Sobre: ' . home_url( '/sobre/' ),
		'- Contato: ' . home_url( '/contato/' ),
		'',
		'## Editorias',
		'',
	);

	foreach ( estrato_aeo_portal_editorias() as $slug ) {
		$term = get_term_by( 'slug', $slug, 'category' );
		if ( $term && ! is_wp_error( $term ) ) {
			$lines[] = '- ' . $term->name . ': ' . get_category_link( $term );
		}
	}

	$hubs = estrato_aeo_portal_hubs();
	if ( $hubs ) {
		$lines[] = '';
		$lines[] = '## Guias (hubs evergreen)';
		$lines[] = '';
		foreach ( $hubs as $hub ) {
			$lines[] = '- ' . $hub['title'] . ': ' . $hub['url'];
		}
	}

	if ( 'estrato-finance' === ( function_exists( 'estrato_nav_current_portal_id' ) ? estrato_nav_current_portal_id() : '' ) ) {
		$lines[] = '';
		$lines[] = '## Cotações';
		$lines[] = '';
		$lines[] = '- Cotações (Frankfurter/ECB): ' . home_url( '/cotacoes/' );
	}

	$lines[] = '';
	$lines[] = '## Feeds';
	$lines[] = '';
	$lines[] = '- RSS: ' . home_url( '/feed/' );
	$lines[] = '- Sitemap: ' . home_url( '/sitemap_index.xml' );
	$lines[] = '- News sitemap: ' . home_url( '/news-sitemap.xml' );
	$lines[] = '';
	$lines[] = '## Citação';
	$lines[] = '';
	$lines[] = 'Ao citar o ' . $blog . ', indique o título da matéria, a URL e a data de publicação.';

	return implode( "\n", $lines ) . "\n";
}

/**
 * @return string
 */
function estrato_aeo_build_llms_full_txt() {
	$home = home_url( '/' );
	$blog = get_bloginfo( 'name' );
	$lines = array(
		'# ' . $blog . ' — llms-full.txt (GEO expandido)',
		'',
		'## Identidade',
		'',
		'Nome: ' . $blog,
		'URL: ' . $home,
		'Idioma: pt-BR',
		'Operador: Estrato Mídia e Conteúdo Ltda.',
		'',
		'## Missão',
		'',
		get_bloginfo( 'description' ),
		'',
		'## Políticas',
		'',
		'- Política editorial: ' . home_url( '/politica-editorial/' ),
		'- Privacidade (LGPD): ' . home_url( '/privacidade/' ),
		'',
		'## Taxonomia editorial',
		'',
		'| Editoria | Slug | URL |',
		'|----------|------|-----|',
	);

	foreach ( estrato_aeo_portal_editorias() as $slug ) {
		$term = get_term_by( 'slug', $slug, 'category' );
		if ( $term && ! is_wp_error( $term ) ) {
			$lines[] = sprintf(
				'| %s | %s | %s |',
				$term->name,
				$slug,
				get_category_link( $term )
			);
		}
	}

	$hubs = estrato_aeo_portal_hubs();
	if ( $hubs ) {
		$lines[] = '';
		$lines[] = '## Hubs temáticos (FAQ + notícias)';
		$lines[] = '';
		foreach ( $hubs as $i => $hub ) {
			$lines[] = ( $i + 1 ) . '. ' . $hub['url'] . ' — ' . $hub['title'];
		}
	}

	$lines[] = '';
	$lines[] = '## Formato de conteúdo';
	$lines[] = '';
	$lines[] = 'Matérias incluem: lead factual, resumo AEO, contexto, perguntas frequentes (FAQ) e links internos. Mínimo editorial: 300 palavras em posts publicados.';
	$lines[] = '';
	$lines[] = '## Contato editorial';
	$lines[] = '';
	$lines[] = '- redacao@estrato.cc';
	$lines[] = '- contato@estrato.cc';

	return implode( "\n", $lines ) . "\n";
}

/**
 * Speakable + HowTo leve em posts (Sprint 22).
 */
function estrato_aeo_output_post_geo_schema() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}

	$post = get_queried_object();
	if ( ! $post || empty( $post->ID ) ) {
		return;
	}

	$schema = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'WebPage',
		'url'         => get_permalink( $post ),
		'name'        => get_the_title( $post ),
		'speakable'   => array(
			'@type'       => 'SpeakableSpecification',
			'cssSelector' => array(
				'.estrato-aeo-resumo',
				'.estrato-editorial-lead',
				'h1',
			),
		),
		'description' => wp_strip_all_tags( get_the_excerpt( $post ) ),
	);

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}
add_action( 'wp_head', 'estrato_aeo_output_post_geo_schema', 9 );

/**
 * IndexNow em lote para URLs recentes.
 *
 * @param int $limit
 * @return int
 */
function estrato_aeo_indexnow_ping_urls( $urls ) {
	if ( ! get_option( ESTRATO_INDEXNOW_ENABLED_OPTION, false ) || ! $urls ) {
		return 0;
	}

	$key  = estrato_aeo_get_indexnow_key();
	$host = wp_parse_url( home_url(), PHP_URL_HOST );
	if ( ! $key || ! $host ) {
		return 0;
	}

	$urls = array_values( array_unique( array_filter( $urls ) ) );
	if ( ! $urls ) {
		return 0;
	}

	wp_remote_post(
		'https://api.indexnow.org/indexnow',
		array(
			'timeout' => 15,
			'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			'body'    => wp_json_encode(
				array(
					'host'        => $host,
					'key'         => $key,
					'keyLocation' => home_url( '/estrato-indexnow-key.txt' ),
					'urlList'     => array_slice( $urls, 0, 100 ),
				)
			),
		)
	);

	return count( $urls );
}

/**
 * @param int $limit
 * @return int
 */
function estrato_aeo_indexnow_recent_posts( $limit = 30 ) {
	$urls = array();
	foreach (
		get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		) as $post
	) {
		$urls[] = get_permalink( $post );
	}
	return estrato_aeo_indexnow_ping_urls( $urls );
}

/**
 * Copia assets GEO (llms.txt, ads.txt) para a raiz.
 *
 * @return array<string, bool>
 */
function estrato_aeo_deploy_static_files() {
	$base   = plugin_dir_path( __FILE__ ) . 'assets/';
	$result = array();

	$llms = estrato_aeo_build_llms_txt();
	$full = estrato_aeo_build_llms_full_txt();
	$result['llms.txt']      = false !== file_put_contents( ABSPATH . 'llms.txt', $llms, LOCK_EX );
	$result['llms-full.txt'] = false !== file_put_contents( ABSPATH . 'llms-full.txt', $full, LOCK_EX );

	$ads_src = $base . 'ads.txt';
	if ( is_readable( $ads_src ) ) {
		$result['ads.txt'] = false !== file_put_contents( ABSPATH . 'ads.txt', file_get_contents( $ads_src ), LOCK_EX );
	} else {
		$result['ads.txt'] = false;
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
