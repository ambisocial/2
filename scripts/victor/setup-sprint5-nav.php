<?php
/**
 * Sprint 5 — hubs, layout, cotações, categorias SEO, footer, forex.
 *
 * Uso: wp eval-file setup-sprint5-nav.php
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

/**
 * @param string $slug
 * @param string $title
 * @param string $content
 * @param int    $parent
 * @return int
 */
function estrato_s5_upsert_page( $slug, $title, $content, $parent = 0 ) {
	$existing = get_page_by_path( $parent ? trim( get_post_field( 'post_name', $parent ), '/' ) . '/' . $slug : $slug, OBJECT, 'page' );
	if ( ! $existing && $parent ) {
		$kids = get_children(
			array(
				'post_parent' => $parent,
				'post_type'   => 'page',
				'post_status' => 'any',
				'name'        => $slug,
			)
		);
		$existing = $kids ? reset( $kids ) : null;
	}

	$data = array(
		'post_title'   => $title,
		'post_name'    => $slug,
		'post_content' => $content,
		'post_status'  => 'publish',
		'post_type'    => 'page',
		'post_parent'  => $parent,
	);

	if ( $existing ) {
		$data['ID'] = $existing->ID;
		$id         = wp_update_post( $data, true );
	} else {
		$id = wp_insert_post( $data, true );
	}

	if ( is_wp_error( $id ) ) {
		WP_CLI::warning( "Página $slug: " . $id->get_error_message() );
		return 0;
	}

	WP_CLI::log( "Página " . ( $parent ? "tudo-sobre/$slug" : $slug ) . " → #$id" );
	return (int) $id;
}

// ─── Hubs /tudo-sobre/ ───────────────────────────────────────────
$hub_parent = estrato_s5_upsert_page(
	'tudo-sobre',
	'Tudo sobre',
	'<!-- wp:paragraph --><p>Guias e cobertura contínua sobre os temas que movem a economia brasileira: juros, bolsa, câmbio, inflação, tributação, criptoativos e agronegócio.</p><!-- /wp:paragraph -->'
);

$hubs = array(
	'selic'       => array(
		'title'    => 'Tudo sobre Selic',
		'category' => 'economia',
		'intro'    => 'A taxa Selic é a principal ferramenta de política monetária do Banco Central do Brasil. Ela influencia o custo do crédito, a rentabilidade da renda fixa e o apetite por risco na bolsa. Nesta página reunimos notícias, contexto e perguntas frequentes para acompanhar decisões do Copom, projeções do mercado e impactos para empresas e famílias.',
	),
	'ibovespa'    => array(
		'title'    => 'Tudo sobre Ibovespa',
		'category' => 'mercados',
		'intro'    => 'O Ibovespa é o principal índice da B3 e funciona como termômetro do mercado acionário brasileiro. Acompanhamos aqui movimentos intradiários, peso de blue chips, fluxo estrangeiro e fatores macro que explicam altas e quedas. Ideal para investidores que buscam contexto além do número do índice.',
	),
	'dolar'       => array(
		'title'    => 'Tudo sobre o dólar',
		'category' => 'mercados',
		'intro'    => 'O dólar comercial reflete fluxo cambial, juros nos EUA, commodities e risco país. Esta hub reúne análises sobre cotação, hedge cambial, impacto na inflação e decisões de importadores e exportadores. Monitoramos também o comportamento frente ao euro e outras moedas relevantes para o Brasil.',
	),
	'cripto'      => array(
		'title'    => 'Tudo sobre criptomoedas',
		'category' => 'criptomoedas',
		'intro'    => 'Bitcoin, Ethereum e o ecossistema de criptoativos ganharam espaço no portfólio de investidores e na agenda regulatória brasileira. Cobrimos preços, adoção institucional, marcos da CVM e Banco Central, além de riscos de volatilidade e custódia para quem acompanha o mercado digital.',
	),
	'inflacao'    => array(
		'title'    => 'Tudo sobre inflação',
		'category' => 'economia',
		'intro'    => 'A inflação medida pelo IPCA e índices correlatos afeta poder de compra, contratos e política de juros. Nesta seção explicamos divulgações do IBGE, núcleos de inflação, expectativas do Boletim Focus e como famílias e empresas podem se proteger em cenários de preços acelerados ou desinflação.',
	),
	'tributacao'  => array(
		'title'    => 'Tudo sobre tributação',
		'category' => 'negocios',
		'intro'    => 'Mudanças tributárias alteram margens corporativas, preços ao consumidor e planejamento financeiro. Acompanhamos reformas em debate, regras da Receita Federal, tributação de investimentos e impactos setoriais para ajudar leitores a entender custos de compliance e oportunidades de eficiência fiscal dentro da legalidade.',
	),
	'agronegocio' => array(
		'title'    => 'Tudo sobre agronegócio',
		'category' => 'agronegocio',
		'intro'    => 'O agronegócio brasileiro é motor de exportações e do PIB. Cobrimos safras, clima, logística, commodities e políticas públicas para o campo. Esta hub concentra notícias sobre soja, milho, carnes e café, com foco em como o setor influencia inflação, câmbio e balança comercial.',
	),
);

foreach ( $hubs as $slug => $hub ) {
	$faq = '<h2>Perguntas frequentes</h2>'
		. '<h3>O que acompanhar nesta hub?</h3><p>Notícias recentes, dados oficiais e contexto de mercado relacionados ao tema.</p>'
		. '<h3>Com que frequência atualizamos?</h3><p>Diariamente, conforme a pauta econômica e movimentos de mercado.</p>'
		. '<h3>Onde ver cotações?</h3><p>Consulte também nossa página <a href="/cotacoes/">Cotações</a>.</p>';

	$content = '<!-- wp:paragraph --><p>' . esc_html( $hub['intro'] ) . '</p><!-- /wp:paragraph -->'
		. '<!-- wp:shortcode -->[estrato_hub_posts category="' . esc_attr( $hub['category'] ) . '" count="8"]<!-- /wp:shortcode -->'
		. '<!-- wp:html -->' . $faq . '<!-- /wp:html -->';

	estrato_s5_upsert_page( $slug, $hub['title'], $content, $hub_parent );
}

// ─── Cotações ───────────────────────────────────────────────────
estrato_s5_upsert_page(
	'cotacoes',
	'Cotações',
	'<!-- wp:paragraph --><p>Acompanhe câmbio USD e pares relevantes para o Brasil. Dados de câmbio via Frankfurter (ECB). Índices B3 podem ter delay de 15 minutos.</p><!-- /wp:paragraph -->'
		. '<!-- wp:shortcode -->[estrato_cotacoes]<!-- /wp:shortcode -->'
);

// ─── Intros SEO categorias (150 palavras) ───────────────────────
$cat_intros = array(
	'economia'          => 'A editoria de Economia do Estrato cobre macroeconomia brasileira e global: inflação, PIB, emprego, política fiscal e monetária. Acompanhamos decisões do Copom, metas de inflação, indicadores do IBGE e projeções do mercado para explicar como cada dado afeta juros, câmbio e o bolso das famílias. Nossa redação cruza fontes oficiais com análise contextual para investidores, empresas e gestores públicos.',
	'mercados'          => 'Em Mercados você encontra cobertura da B3, renda fixa, câmbio e commodities com foco no investidor brasileiro. Monitoramos Ibovespa, curva de juros, fluxo estrangeiro e volatilidade em tempo quase real, sempre com atribuição de fontes e histórico. O objetivo é ir além do ticker: explicar por que o mercado se move e quais setores lideram ganhos ou perdas em cada sessão.',
	'negocios'          => 'A editoria Negócios acompanha empresas listadas, fusões, IPOs, governança e estratégia corporativa. Cobrimos balanços, guidance, M&A e disputas regulatórias que alteram valuation e emprego. Para empreendedores e analistas, oferecemos contexto sobre cadeias de valor, concorrência e tendências de consumo que moldam resultados trimestrais.',
	'financas-pessoais' => 'Finanças Pessoais reúne guias práticos sobre orçamento, crédito, investimentos e planejamento patrimonial. Traduzimos conceitos de mercado em linguagem acessível, com atenção a perfil de risco, custos e metas de longo prazo. A editoria alerta sobre golpes financeiros, educação fiscal e produtos regulados pela CVM e Banco Central.',
	'criptomoedas'      => 'Criptomoedas no Estrato cobre Bitcoin, Ethereum, stablecoins e regulação de ativos digitais no Brasil. Explicamos volatilidade, custódia, tributação e integração com o sistema financeiro tradicional. A redação separa hype de adoção real, acompanhando decisões do Congresso, Banco Central e CVM sobre tokens e infraestrutura blockchain.',
	'agronegocio'       => 'Agronegócio concentra notícias sobre safras, clima, exportações e políticas para o campo. O Brasil é protagonista global em commodities e isso afeta inflação e superávit comercial. Cobrimos custos de insumos, logística, China como importador e inovação no campo, com dados de CONAB, USDA e associações setoriais quando disponíveis.',
	'mundo'             => 'A editoria Mundo explica economia internacional e geopolítica com impacto no Brasil: Fed, OPEP, China, Europa e emergentes. Conectamos juros americanos, guerra comercial e choques de oferta a dólar, inflação e exportações brasileiras. Ideal para quem investe globalmente ou gerencia cadeias expostas ao exterior.',
);

foreach ( $cat_intros as $slug => $desc ) {
	$term = get_term_by( 'slug', $slug, 'category' );
	if ( ! $term ) {
		continue;
	}
	wp_update_term(
		(int) $term->term_id,
		'category',
		array( 'description' => $desc )
	);
	WP_CLI::log( "Categoria $slug: descrição SEO atualizada" );
}

// ─── Layout Builder (home) ─────────────────────────────────────
$cat_ids = array(
	'economia'          => 2,
	'mercados'          => 3,
	'negocios'          => 4,
	'financas-pessoais' => 14,
	'criptomoedas'      => 9,
	'agronegocio'       => 10,
	'mundo'             => 8,
);

$newsletter_html = '<div class="estrato-newsletter"><h2>Newsletter Estrato</h2><p>Receba os destaques de economia e mercados no seu e-mail.</p>[contact-form-7 id="4" title="Contact form 1"]</div>';
$editorias_html  = '[estrato_home_editorias]';
$mais_lidas_html = '[estrato_mais_lidas count="7"]';

$sections = array(
	array(
		'id'          => 'hero',
		'label'       => 'Hero',
		'enabled'     => true,
		'layout'      => 'hero-grid',
		'category'    => $cat_ids['economia'],
		'post_count'  => 3,
		'custom_html' => '',
	),
	array(
		'id'          => 'latest_posts',
		'label'       => 'Latest Posts',
		'enabled'     => true,
		'layout'      => 'grid-3',
		'category'    => 0,
		'post_count'  => 6,
		'custom_html' => '',
	),
	array(
		'id'          => 'category_grid',
		'label'       => 'Mercados',
		'enabled'     => true,
		'layout'      => 'grid-4',
		'category'    => $cat_ids['mercados'],
		'post_count'  => 4,
		'custom_html' => '',
	),
	array(
		'id'          => 'trending',
		'label'       => 'Mais lidas',
		'enabled'     => true,
		'layout'      => 'custom_html',
		'category'    => 0,
		'post_count'  => 0,
		'custom_html' => $mais_lidas_html,
	),
	array(
		'id'          => 'editor_picks',
		'label'       => 'Editorias',
		'enabled'     => true,
		'layout'      => 'custom_html',
		'category'    => 0,
		'post_count'  => 0,
		'custom_html' => $editorias_html,
	),
	array(
		'id'          => 'newsletter',
		'label'       => 'Newsletter',
		'enabled'     => true,
		'layout'      => 'newsletter',
		'category'    => 0,
		'post_count'  => 0,
		'custom_html' => $newsletter_html,
	),
	array(
		'id'          => 'custom_html',
		'label'       => 'Negócios',
		'enabled'     => true,
		'layout'      => 'grid-4',
		'category'    => $cat_ids['negocios'],
		'post_count'  => 4,
		'custom_html' => '',
	),
	array(
		'id'          => 'ad_block',
		'label'       => 'Ad Block',
		'enabled'     => false,
		'layout'      => 'ad_block',
		'category'    => 0,
		'post_count'  => 0,
		'custom_html' => '',
	),
	array(
		'id'          => 'opinion',
		'label'       => 'Finanças Pessoais',
		'enabled'     => true,
		'layout'      => 'grid-2',
		'category'    => $cat_ids['financas-pessoais'],
		'post_count'  => 4,
		'custom_html' => '',
	),
);

update_option( 'pressgrid_layout_sections', $sections, false );
WP_CLI::log( 'Layout Builder atualizado' );

// ─── Forex / ticker ─────────────────────────────────────────────
set_theme_mod( 'pressgrid_forex_force', true );
set_theme_mod( 'pressgrid_forex_base', 'USD' );
set_theme_mod( 'pressgrid_forex_targets', 'BRL,EUR,GBP,CHF,JPY' );
set_theme_mod( 'pressgrid_forex_category_slug', 'mercados' );
set_theme_mod( 'pressgrid_breaking_news_category', $cat_ids['mercados'] );
set_theme_mod( 'pressgrid_primary_color', '#000000' );

// ─── Footer 4 colunas ───────────────────────────────────────────
$footer_widgets = array(
	'footer-1' => array(
		'title' => 'Estrato',
		'text'  => 'Portal de economia, mercados e finanças pessoais. Cobertura diária com foco em dados e contexto para investidores brasileiros.',
	),
	'footer-2' => array(
		'title' => 'Editorias',
		'text'  => '<a href="/category/economia/">Economia</a> · <a href="/category/mercados/">Mercados</a> · <a href="/category/negocios/">Negócios</a> · <a href="/category/financas-pessoais/">Finanças Pessoais</a>',
	),
	'footer-3' => array(
		'title' => 'Guias',
		'text'  => '<a href="/tudo-sobre/selic/">Selic</a> · <a href="/tudo-sobre/ibovespa/">Ibovespa</a> · <a href="/tudo-sobre/dolar/">Dólar</a> · <a href="/cotacoes/">Cotações</a>',
	),
	'footer-4' => array(
		'title' => 'Institucional',
		'text'  => '<a href="/sobre/">Sobre</a> · <a href="/contato/">Contato</a> · <a href="/politica-editorial/">Política editorial</a> · <a href="/politica-de-privacidade/">Privacidade</a>',
	),
);

$widget_text = get_option( 'widget_text', array() );
if ( empty( $widget_text['_multiwidget'] ) ) {
	$widget_text['_multiwidget'] = 1;
}
$sidebars = get_option( 'sidebars_widgets', array() );

foreach ( $footer_widgets as $sidebar => $widget ) {
	$key = 1;
	while ( isset( $widget_text[ $key ] ) ) {
		++$key;
	}
	$widget_text[ $key ] = array(
		'title'  => $widget['title'],
		'text'   => $widget['text'],
		'filter' => false,
	);
	$sidebars[ $sidebar ] = array( 'text-' . $key );
}

update_option( 'widget_text', $widget_text, false );
update_option( 'sidebars_widgets', $sidebars, false );
WP_CLI::log( 'Footer 4 colunas configurado' );

// Menu primário — garantir location
$menu = wp_get_nav_menu_object( 'Estrato Principal' );
if ( $menu ) {
	$locations = get_theme_mod( 'nav_menu_locations', array() );
	$locations['primary']   = (int) $menu->term_id;
	$locations['secondary'] = (int) $menu->term_id;
	set_theme_mod( 'nav_menu_locations', $locations );
}

flush_rewrite_rules( false );
WP_CLI::success( 'Sprint 5 navegação & visual configurado.' );
