<?php
/**
 * Taxonomia canônica estrato.cc — schema v2.
 *
 * Camadas:
 *   1. categories   — editorias (menu principal, PressGrid, RSS base)
 *   2. subcategories — tópicos dentro da editoria (filho WP category)
 *   3. columns      — colunas de nicho com nome de marca e cores próprias (estilo G1)
 *
 * Preencha subcategorias/colunas em portals/estrato-finance-taxonomy.template.yaml
 * e envie para importação; este arquivo permanece a fonte canônica em produção.
 *
 * @return array<string, mixed>
 */
return array(
	'schema_version' => 2,
	'menu_order'     => array(
		'economia',
		'mercados',
		'negocios',
		'financas-pessoais',
		'criptomoedas',
		'agronegocio',
		'mundo',
	),
	'column_order'   => array(
		// Adicione slugs de colunas na ordem do menu/strip (ex.: 'mercados-radar-b3').
	),
	'index_order'    => array(
		'mercados',
		'negocios',
		'economia',
		'financas-pessoais',
		'criptomoedas',
		'agronegocio',
		'mundo',
	),
	'legacy_merge'   => array(
		'politica'   => 'economia',
		'tecnologia' => 'negocios',
		'brasil'     => 'mundo',
	),
	'legacy_noindex' => array(
		'politica',
		'tecnologia',
		'brasil',
		'sem-categoria',
	),
	/**
	 * Defaults de branding/keywords herdados por editorias, subcategorias e colunas.
	 */
	'defaults'       => array(
		'branding'  => array(
			'primary_color'   => '#000000',
			'accent_color'    => '#9AFF33',
			'secondary_color' => '#1a1a1a',
			'header_variant'  => 'editoria',
		),
		'keywords'  => array(
			'include'  => array(),
			'exclude'  => array(),
			'match_in' => array( 'title', 'excerpt', 'content' ),
		),
	),
	'categories'     => array(
		'economia'          => array(
			'name'        => 'Economia',
			'description' => 'Macroeconomia brasileira: inflação, PIB, emprego, política fiscal e monetária. Acompanhamos Copom, IBGE, Focus e impactos no bolso das famílias e nas empresas.',
			'branding'    => array(
				'brand_name'      => 'Economia',
				'primary_color'   => '#0D1B2A',
				'accent_color'    => '#E85D04',
				'secondary_color' => '#1B263B',
				'header_variant'  => 'editoria',
			),
			'keywords'    => array(
				'include' => array(),
				'exclude' => array(),
			),
			'feeds'       => array(
				array( 'title' => 'InfoMoney', 'url' => 'https://www.infomoney.com.br/feed/', 'tier' => 1 ),
				array( 'title' => 'Valor Econômico', 'url' => 'https://pox.globo.com/rss/valor', 'tier' => 1 ),
				array( 'title' => 'Folha Em cima da Hora', 'url' => 'https://feeds.folha.uol.com.br/emcimadahora/rss091.xml', 'tier' => 1 ),
				array( 'title' => 'G1 Economia', 'url' => 'https://g1.globo.com/rss/g1/economia/', 'tier' => 2 ),
				array( 'title' => 'Money Times', 'url' => 'https://www.moneytimes.com.br/feed/', 'tier' => 2 ),
			),
			'subcategories' => array(
				'macro' => array(
					'name'        => 'Macroeconomia',
					'description' => 'PIB, inflação, fiscal e indicadores agregados da economia brasileira.',
					'author'      => array(
						'first_name'   => 'Carolina',
						'last_name'    => 'Fischer',
						'display_name' => 'Carolina Fischer',
						'job_title'    => 'Repórter de macroeconomia · Economia',
						'gender'       => 'woman',
						'bio'          => 'Formada em Economia pela USP, cobre PIB, fiscal e indicadores agregados há mais de uma década. No Estrato, traduz dados do IBGE e do Focus para o investidor e para famílias.',
					),
					'keywords'    => array(
						'include' => array( 'pib', 'inflação', 'inflacao', 'ipca', 'fiscal', 'déficit', 'deficit' ),
						'exclude' => array(),
					),
				),
				'selic' => array(
					'name'        => 'Selic e juros',
					'description' => 'Copom, taxa Selic, curva de juros e expectativas do mercado.',
					'author'      => array(
						'first_name'   => 'Rafael',
						'last_name'    => 'Moraes',
						'display_name' => 'Rafael Moraes',
						'job_title'    => 'Repórter de política monetária · Economia',
						'gender'       => 'man',
						'bio'          => 'Especialista em política monetária e mercado de juros. Acompanha cada decisão do Copom e a reação da curva DI. Antes do Estrato, passou por mesas de renda fixa em São Paulo.',
					),
					'keywords'    => array(
						'include' => array( 'selic', 'copom', 'juros', 'taxa básica', 'focus' ),
						'exclude' => array(),
					),
				),
			),
		),
		'mercados'          => array(
			'name'        => 'Mercados',
			'description' => 'B3, renda fixa, câmbio e commodities para o investidor brasileiro. Ibovespa, fluxo estrangeiro, volatilidade e contexto de cada sessão.',
			'branding'    => array(
				'brand_name'      => 'Mercados',
				'primary_color'   => '#003049',
				'accent_color'    => '#FCBF49',
				'secondary_color' => '#1D3557',
				'header_variant'  => 'editoria',
			),
			'keywords'    => array(),
			'feeds'       => array(
				array( 'title' => 'Folha Mercado', 'url' => 'https://feeds.folha.uol.com.br/mercado/rss091.xml', 'tier' => 1 ),
				array( 'title' => 'InfoMoney Mercados', 'url' => 'https://www.infomoney.com.br/mercados/feed/', 'tier' => 1 ),
				array( 'title' => 'Investing.com Brasil', 'url' => 'https://br.investing.com/rss/news.rss', 'tier' => 2 ),
				array( 'title' => 'Portal Mercado Aberto', 'url' => 'https://www.portalmercadoaberto.com.br/rss', 'tier' => 2 ),
				array( 'title' => 'MarketWatch', 'url' => 'https://feeds.marketwatch.com/marketwatch/topstories/', 'tier' => 2 ),
			),
			'subcategories' => array(
				'ibovespa' => array(
					'name'        => 'Ibovespa',
					'description' => 'Índice B3, blue chips, fluxo e destaques da bolsa brasileira.',
					'author'      => array(
						'first_name'   => 'Beatriz',
						'last_name'    => 'Nogueira',
						'display_name' => 'Beatriz Nogueira',
						'job_title'    => 'Repórter de mercado acionário · Mercados',
						'gender'       => 'woman',
						'bio'          => 'Cobre pregão, blue chips e fluxo estrangeiro na B3. No Estrato, contextualiza cada sessão do Ibovespa para quem investe em ações no Brasil.',
					),
					'keywords'    => array(
						'include' => array( 'ibovespa', 'b3', 'ações', 'acoes', 'bolsa' ),
						'exclude' => array(),
					),
				),
				'cambio'   => array(
					'name'        => 'Câmbio',
					'description' => 'Dólar, euro e pares relevantes para importadores, exportadores e investidores.',
					'author'      => array(
						'first_name'   => 'Thiago',
						'last_name'    => 'Carvalho',
						'display_name' => 'Thiago Carvalho',
						'job_title'    => 'Repórter de câmbio · Mercados',
						'gender'       => 'man',
						'bio'          => 'Analista de câmbio e fluxo externo. Monitora PTAX, dólar à vista e fatores que movem o real — do Fed às commodities.',
					),
					'keywords'    => array(
						'include' => array( 'dólar', 'dolar', 'câmbio', 'cambio', 'euro', 'ptax' ),
						'exclude' => array(),
					),
				),
			),
		),
		'negocios'          => array(
			'name'        => 'Negócios',
			'description' => 'Empresas listadas, fusões, IPOs, governança e estratégia corporativa. Balanços, M&A e disputas regulatórias que alteram valuation.',
			'branding'    => array(
				'brand_name'      => 'Negócios',
				'primary_color'   => '#1A1A2E',
				'accent_color'    => '#E94560',
				'secondary_color' => '#16213E',
				'header_variant'  => 'editoria',
			),
			'keywords'    => array(),
			'feeds'       => array(
				array( 'title' => 'Exame', 'url' => 'https://exame.com/feed/', 'tier' => 1 ),
				array( 'title' => 'G1 PME & Negócios', 'url' => 'https://g1.globo.com/rss/g1/economia/pme/', 'tier' => 2 ),
				array( 'title' => 'Financial Times', 'url' => 'https://www.ft.com/rss/home', 'tier' => 2 ),
			),
			'subcategories' => array(),
		),
		'financas-pessoais' => array(
			'name'        => 'Finanças Pessoais',
			'description' => 'Orçamento, crédito, investimentos e planejamento patrimonial em linguagem acessível. Educação financeira, golpes e produtos regulados.',
			'branding'    => array(
				'brand_name'      => 'Finanças Pessoais',
				'primary_color'   => '#2D6A4F',
				'accent_color'    => '#95D5B2',
				'secondary_color' => '#1B4332',
				'header_variant'  => 'editoria',
			),
			'keywords'    => array(),
			'feeds'       => array(
				array( 'title' => 'InfoMoney Finanças Pessoais', 'url' => 'https://www.infomoney.com.br/tudo-sobre/financas-pessoais/feed/', 'tier' => 1 ),
				array( 'title' => 'Melhor Investimento', 'url' => 'https://www.melhorinvestimento.net/feed', 'tier' => 2 ),
			),
			'subcategories' => array(),
		),
		'criptomoedas'      => array(
			'name'        => 'Criptomoedas',
			'description' => 'Bitcoin, Ethereum, stablecoins e regulação de ativos digitais no Brasil. Volatilidade, custódia e marcos da CVM e Banco Central.',
			'branding'    => array(
				'brand_name'      => 'Cripto',
				'primary_color'   => '#0F0F23',
				'accent_color'    => '#F7931A',
				'secondary_color' => '#1A1A3E',
				'header_variant'  => 'editoria',
			),
			'keywords'    => array(),
			'feeds'       => array(
				array( 'title' => 'Livecoins', 'url' => 'https://livecoins.com.br/feed/', 'tier' => 1 ),
				array( 'title' => 'Portal do Bitcoin', 'url' => 'https://portaldobitcoin.uol.com.br/feed/', 'tier' => 1 ),
				array( 'title' => 'CriptoFácil', 'url' => 'https://www.criptofacil.com/feed/', 'tier' => 2 ),
			),
			'subcategories' => array(),
		),
		'agronegocio'       => array(
			'name'        => 'Agronegócio',
			'description' => 'Safras, clima, exportações e commodities agrícolas. Soja, milho, carnes e café com impacto em inflação, câmbio e balança comercial.',
			'branding'    => array(
				'brand_name'      => 'Agro',
				'primary_color'   => '#3E5635',
				'accent_color'    => '#D4A373',
				'secondary_color' => '#2C4A2E',
				'header_variant'  => 'editoria',
			),
			'keywords'    => array(),
			'feeds'       => array(
				array( 'title' => 'G1 Agronegócios', 'url' => 'https://g1.globo.com/rss/g1/economia/agronegocios/', 'tier' => 1 ),
				array( 'title' => 'InfoMoney Agronegócio', 'url' => 'https://www.infomoney.com.br/tudo-sobre/agronegocio/feed/', 'tier' => 2 ),
			),
			'subcategories' => array(),
		),
		'mundo'             => array(
			'name'        => 'Internacional',
			'description' => 'Economia global e geopolítica com impacto no Brasil: Fed, OPEP, China e emergentes. Juros americanos e choques de oferta refletidos no câmbio.',
			'branding'    => array(
				'brand_name'      => 'Mundo',
				'primary_color'   => '#14213D',
				'accent_color'    => '#FCA311',
				'secondary_color' => '#1F2A44',
				'header_variant'  => 'editoria',
			),
			'keywords'    => array(),
			'feeds'       => array(
				array( 'title' => 'BBC Business', 'url' => 'https://feeds.bbci.co.uk/news/business/rss.xml', 'tier' => 1 ),
				array( 'title' => 'Financial Times World', 'url' => 'https://www.ft.com/world?format=rss', 'tier' => 1 ),
			),
			'subcategories' => array(),
		),
	),
	/**
	 * Colunas de nicho — nome de marca próprio, cores e feeds dedicados.
	 * slug WP gerado: {category}-{slug} (ex.: mercados-radar-b3).
	 *
	 * Deixe vazio até enviar a lista final; use o template YAML para rascunho.
	 */
	'columns'        => array(
		// 'radar-b3' => array(
		// 	'brand_name'       => 'Radar B3',
		// 	'name'             => 'Radar B3',
		// 	'tagline'          => 'Plantão da bolsa em tempo real',
		// 	'category'         => 'mercados',
		// 	'subcategory'      => 'ibovespa',
		// 	'description'      => 'Sessão, destaques e fluxo do Ibovespa.',
		// 	'show_in_nav'      => true,
		// 	'show_in_home_strip' => true,
		// 	'branding'         => array(
		// 		'primary_color'   => '#003049',
		// 		'accent_color'    => '#FCBF49',
		// 		'secondary_color' => '#1D3557',
		// 		'header_variant'  => 'column',
		// 	),
		// 	'keywords'         => array(
		// 		'include' => array( 'ibovespa', 'b3', 'ações' ),
		// 		'exclude' => array( 'cripto', 'bitcoin' ),
		// 	),
		// 	'feeds'            => array(
		// 		array(
		// 			'title'    => 'InfoMoney Mercados',
		// 			'url'      => 'https://www.infomoney.com.br/mercados/feed/',
		// 			'tier'     => 1,
		// 			'keywords' => array(
		// 				'include' => array( 'ibovespa', 'b3' ),
		// 				'exclude' => array(),
		// 			),
		// 		),
		// 	),
		// ),
	),
);
