<?php
/**
 * Taxonomia — Sustentabilidade, economia alternativa e política de nicho (schema v2).
 *
 * 3 editorias × 4 nichos (subcategorias) — bullets 17–20.
 *
 * @return array<string, mixed>
 */
return array(
	'schema_version' => 2,
	'portal_id'      => 'estrato-sustain',
	'menu_order'     => array(
		'agro-sustentavel',
		'economia-alternativa',
		'vida-nomade',
	),
	'column_order'   => array(),
	'index_order'    => array(
		'agro-sustentavel',
		'economia-alternativa',
		'vida-nomade',
	),
	'legacy_merge'   => array(),
	'legacy_noindex' => array(),
	'defaults'       => array(
		'branding' => array(
			'primary_color'   => '#1B4332',
			'accent_color'    => '#95D5B2',
			'secondary_color' => '#2D6A4F',
			'header_variant'  => 'editoria',
		),
		'keywords' => array(
			'include'  => array(),
			'exclude'  => array(),
			'match_in' => array( 'title', 'excerpt', 'content' ),
		),
	),
	'categories'     => array(
		'agro-sustentavel'      => array(
			'name'        => 'Agro & Sustentabilidade',
			'description' => 'Agroecologia, permacultura e sistemas agroflorestais — movimentos e redes brasileiras independentes.',
			'branding'    => array(
				'brand_name'      => 'Terra',
				'primary_color'   => '#386641',
				'accent_color'    => '#A7C957',
				'secondary_color' => '#6A994E',
				'header_variant'  => 'editoria',
			),
			'keywords'    => array(
				'include' => array(),
				'exclude' => array( 'ibovespa', 'selic' ),
			),
			'feeds'       => array(
				array(
					'title'    => 'ANA — Articulação Nacional de Agroecologia',
					'url'      => 'https://agroecologia.org.br/feed/',
					'tier'     => 1,
					'verified' => true,
				),
			),
			'subcategories' => array(
				'agroecologia-safs' => array(
					'name'        => 'Agroecologia, permacultura & SAFs',
					'description' => 'Agroecologia, sistemas agroflorestais, permacultura, agricultura sintrópica e agricultura familiar.',
					'keywords'    => array(
						'include' => array(
							'agroecologia',
							'sistema agroflorestal',
							'SAF',
							'permacultura',
							'agricultura sintrópica',
							'agricultura sintropica',
							'agricultura familiar',
							'agrofloresta',
							'compostagem',
						),
						'exclude' => array(),
					),
					'feeds'       => array(
						array(
							'title'    => 'ANA — Articulação Nacional de Agroecologia',
							'url'      => 'https://agroecologia.org.br/feed/',
							'tier'     => 1,
							'verified' => true,
						),
						array( 'title' => 'CEPAGRO', 'url' => 'https://cepagro.org.br/?feed=rss2', 'tier' => 1 ),
						array( 'title' => 'Rede NEPerma Brasil', 'url' => 'https://redepermacultura.ufsc.br/feed/', 'tier' => 1 ),
						array( 'title' => 'IPOEMA — Instituto de Permacultura', 'url' => 'https://ipoema.org.br/feed/', 'tier' => 3 ),
					),
				),
			),
		),
		'economia-alternativa'  => array(
			'name'        => 'Economia Alternativa',
			'description' => 'Cooperativismo de plataforma, negócios de impacto e DeFi/Web3 com viés analítico no Brasil.',
			'branding'    => array(
				'brand_name'      => 'Coop',
				'primary_color'   => '#4A1942',
				'accent_color'    => '#C77DFF',
				'secondary_color' => '#7B2CBF',
				'header_variant'  => 'editoria',
			),
			'keywords'    => array(
				'include' => array(),
				'exclude' => array(),
			),
			'feeds'       => array(
				array( 'title' => 'DigiLabour', 'url' => 'https://digilabour.com.br/feed/', 'tier' => 1 ),
			),
			'subcategories' => array(
				'cooperativismo-impacto' => array(
					'name'        => 'Cooperativismo de plataforma & negócios sociais',
					'description' => 'Economia solidária, cooperativismo de plataforma, plataformização do trabalho e negócios de impacto socioambiental.',
					'keywords'    => array(
						'include' => array(
							'cooperativismo de plataforma',
							'economia solidária',
							'negócios de impacto',
							'negocios de impacto',
							'impacto socioambiental',
							'plataformização do trabalho',
							'plataformizacao do trabalho',
							'autogestão cooperativa',
							'autogestao cooperativa',
							'Fairwork',
						),
						'exclude' => array(),
					),
					'feeds'       => array(
						array( 'title' => 'DigiLabour', 'url' => 'https://digilabour.com.br/feed/', 'tier' => 1 ),
						array( 'title' => 'EITA — Educação, Informação e Tecnologia para Autogestão', 'url' => 'https://eita.coop.br/feed/', 'tier' => 1 ),
						array( 'title' => 'Quintessa — negócios de impacto', 'url' => 'https://blog.quintessa.org.br/feed/', 'tier' => 2 ),
					),
				),
				'defi-web3-critico'      => array(
					'name'        => 'DeFi & Web3 crítico',
					'description' => 'Finanças descentralizadas, governança DAO e análise crítica do ecossistema cripto brasileiro — fontes escassas em pt-BR.',
					'keywords'    => array(
						'include' => array(
							'DeFi',
							'finanças descentralizadas',
							'financas descentralizadas',
							'governança DAO',
							'governanca DAO',
							'segurança smart contract',
							'seguranca smart contract',
							'golpe cripto',
							'pirâmide cripto',
							'análise on-chain',
							'analise on-chain',
							'Web3',
						),
						'exclude' => array(),
					),
					'feeds'       => array(
						array(
							'title'    => 'Modular Crypto (podcast)',
							'url'      => 'https://anchor.fm/s/e5095660/podcast/rss',
							'tier'     => 1,
							'verified' => true,
						),
						array(
							'title'    => 'Brazil Crypto Report',
							'url'      => 'https://newsletter.brazilcrypto.io/feed',
							'tier'     => 2,
							'verified' => true,
						),
						array( 'title' => 'CriptoFácil (Substack — Luciano Rocha)', 'url' => 'https://criptofacil.substack.com/feed', 'tier' => 3 ),
					),
				),
			),
		),
		'vida-nomade'           => array(
			'name'        => 'Vida Nômade',
			'description' => 'Nomadismo digital aprofundado, worldschooling, residência fiscal e saúde mental do nômade brasileiro.',
			'branding'    => array(
				'brand_name'      => 'Nômade',
				'primary_color'   => '#023047',
				'accent_color'    => '#FFB703',
				'secondary_color' => '#219EBC',
				'header_variant'  => 'editoria',
			),
			'keywords'    => array(
				'include' => array(),
				'exclude' => array(),
			),
			'feeds'       => array(
				array(
					'title'    => 'Passageiro — Matheus de Souza',
					'url'      => 'https://passageiro.news/feed',
					'tier'     => 1,
					'verified' => true,
				),
			),
			'subcategories' => array(
				'nomadismo-digital' => array(
					'name'        => 'Nomadismo digital aprofundado',
					'description' => 'Vistos de nômade, tributação internacional, worldschooling, saída definitiva e saúde mental do nômade.',
					'keywords'    => array(
						'include' => array(
							'nômade digital',
							'nomade digital',
							'visto nômade digital',
							'visto nomade digital',
							'residência fiscal',
							'residencia fiscal',
							'saída definitiva',
							'saida definitiva',
							'worldschooling',
							'saúde mental nômade',
							'saude mental nomade',
							'trabalho remoto',
							'homeschooling',
						),
						'exclude' => array(),
					),
					'feeds'       => array(
						array(
							'title'    => 'Passageiro — Matheus de Souza',
							'url'      => 'https://passageiro.news/feed',
							'tier'     => 1,
							'verified' => true,
						),
						array( 'title' => 'Juju na Trip', 'url' => 'https://jujunatrip.com/feed/', 'tier' => 1 ),
						array( 'title' => 'Brasil Tax (blog)', 'url' => 'https://brasiltax.com/blog/feed/', 'tier' => 3 ),
						array( 'title' => 'O Mundo é uma Escola', 'url' => 'https://omundoeumaescola.wordpress.com/feed/', 'tier' => 3 ),
					),
				),
			),
		),
	),
	'columns'        => array(),
);
