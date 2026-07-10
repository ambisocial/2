<?php
/**
 * Taxonomia canônica do portal financeiro estrato.cc (Sprint 8).
 * Fonte única sincronizada com estrato-finance.yaml → preset brasil-financeiro.
 *
 * @return array<string, mixed>
 */
return array(
	'menu_order' => array(
		'economia',
		'mercados',
		'negocios',
		'financas-pessoais',
		'criptomoedas',
		'agronegocio',
		'mundo',
	),
	'legacy_merge' => array(
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
	'categories' => array(
		'economia'          => array(
			'name'        => 'Economia',
			'description' => 'Macroeconomia brasileira: inflação, PIB, emprego, política fiscal e monetária. Acompanhamos Copom, IBGE, Focus e impactos no bolso das famílias e nas empresas.',
			'feeds'       => array(
				array( 'title' => 'G1 Economia', 'url' => 'https://g1.globo.com/rss/g1/economia/' ),
				array( 'title' => 'InfoMoney', 'url' => 'https://www.infomoney.com.br/feed/' ),
				array( 'title' => 'Exame', 'url' => 'https://exame.com/feed/' ),
				array( 'title' => 'Money Times', 'url' => 'https://www.moneytimes.com.br/feed/' ),
				array( 'title' => 'Folha Em cima da Hora', 'url' => 'https://feeds.folha.uol.com.br/emcimadahora/rss091.xml' ),
			),
			'subcategories' => array(
				'macro' => array(
					'name'        => 'Macroeconomia',
					'description' => 'PIB, inflação, fiscal e indicadores agregados da economia brasileira.',
				),
				'selic' => array(
					'name'        => 'Selic e juros',
					'description' => 'Copom, taxa Selic, curva de juros e expectativas do mercado.',
				),
			),
		),
		'mercados'          => array(
			'name'        => 'Mercados',
			'description' => 'B3, renda fixa, câmbio e commodities para o investidor brasileiro. Ibovespa, fluxo estrangeiro, volatilidade e contexto de cada sessão.',
			'feeds'       => array(
				array( 'title' => 'Folha Mercado', 'url' => 'https://feeds.folha.uol.com.br/mercado/rss091.xml' ),
				array( 'title' => 'Investing.com Brasil', 'url' => 'https://br.investing.com/rss/news.rss' ),
				array( 'title' => 'MarketWatch', 'url' => 'https://feeds.marketwatch.com/marketwatch/topstories/' ),
				array( 'title' => 'CNBC Top News', 'url' => 'https://www.cnbc.com/id/100003114/device/rss/rss.html' ),
				array( 'title' => 'Reuters Business', 'url' => 'https://feeds.reuters.com/reuters/businessNews' ),
			),
			'subcategories' => array(
				'ibovespa' => array(
					'name'        => 'Ibovespa',
					'description' => 'Índice B3, blue chips, fluxo e destaques da bolsa brasileira.',
				),
				'cambio'   => array(
					'name'        => 'Câmbio',
					'description' => 'Dólar, euro e pares relevantes para importadores, exportadores e investidores.',
				),
			),
		),
		'negocios'          => array(
			'name'        => 'Negócios',
			'description' => 'Empresas listadas, fusões, IPOs, governança e estratégia corporativa. Balanços, M&A e disputas regulatórias que alteram valuation.',
			'feeds'       => array(
				array( 'title' => 'G1 PME & Negócios', 'url' => 'https://g1.globo.com/rss/g1/economia/pme/' ),
				array( 'title' => 'Valor Investe Empresas', 'url' => 'https://valorinveste.globo.com/empresas/rss.xml' ),
				array( 'title' => 'Financial Times', 'url' => 'https://www.ft.com/rss/home' ),
			),
			'subcategories' => array(),
		),
		'financas-pessoais' => array(
			'name'        => 'Finanças Pessoais',
			'description' => 'Orçamento, crédito, investimentos e planejamento patrimonial em linguagem acessível. Educação financeira, golpes e produtos regulados.',
			'feeds'       => array(
				array( 'title' => 'Valor Investe', 'url' => 'https://valorinveste.globo.com/rss.xml' ),
				array( 'title' => 'Melhor Investimento', 'url' => 'https://www.melhorinvestimento.net/feed' ),
				array( 'title' => 'InfoMoney Finanças Pessoais', 'url' => 'https://www.infomoney.com.br/tudo-sobre/financas-pessoais/feed/' ),
			),
			'subcategories' => array(),
		),
		'criptomoedas'      => array(
			'name'        => 'Criptomoedas',
			'description' => 'Bitcoin, Ethereum, stablecoins e regulação de ativos digitais no Brasil. Volatilidade, custódia e marcos da CVM e Banco Central.',
			'feeds'       => array(
				array( 'title' => 'Livecoins', 'url' => 'https://livecoins.com.br/feed/' ),
				array( 'title' => 'Portal do Bitcoin', 'url' => 'https://portaldobitcoin.uol.com.br/feed/' ),
				array( 'title' => 'CriptoFácil', 'url' => 'https://www.criptofacil.com/feed/' ),
			),
			'subcategories' => array(),
		),
		'agronegocio'       => array(
			'name'        => 'Agronegócio',
			'description' => 'Safras, clima, exportações e commodities agrícolas. Soja, milho, carnes e café com impacto em inflação, câmbio e balança comercial.',
			'feeds'       => array(
				array( 'title' => 'G1 Agronegócios', 'url' => 'https://g1.globo.com/rss/g1/economia/agronegocios/' ),
				array( 'title' => 'Agrolink', 'url' => 'https://www.agrolink.com.br/rss/noticias.xml' ),
			),
			'subcategories' => array(),
		),
		'mundo'             => array(
			'name'        => 'Internacional',
			'description' => 'Economia global e geopolítica com impacto no Brasil: Fed, OPEP, China e emergentes. Juros americanos e choques de oferta refletidos no câmbio.',
			'feeds'       => array(
				array( 'title' => 'BBC Business', 'url' => 'https://feeds.bbci.co.uk/news/business/rss.xml' ),
				array( 'title' => 'Financial Times', 'url' => 'https://www.ft.com/rss/home' ),
				array( 'title' => 'Reuters Business', 'url' => 'https://feeds.reuters.com/reuters/businessNews' ),
			),
			'subcategories' => array(),
		),
	),
);
