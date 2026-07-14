<?php
/**
 * Taxonomia — Agro (schema v2). Frente ex-Sustain.
 *
 * Cobertura: produção, commodities, agroecologia e política agropecuária BR.
 *
 * @return array<string, mixed>
 */
return array(
	'schema_version' => 2,
	'portal_id'      => 'estrato-agro',
	'menu_order'     => array(
		'producao-safras',
		'mercado-agro',
		'agroecologia',
	),
	'column_order'   => array(),
	'index_order'    => array(
		'producao-safras',
		'mercado-agro',
		'agroecologia',
	),
	'legacy_merge'   => array(
		'agro-sustentavel' => 'agroecologia',
	),
	'legacy_noindex' => array(
		'agro-sustentavel',
	),
	'defaults'       => array(
		'branding' => array(
			'primary_color'   => '#386641',
			'accent_color'    => '#A7C957',
			'secondary_color' => '#6A994E',
			'header_variant'  => 'editoria',
		),
		'keywords' => array(
			'include'  => array(),
			'exclude'  => array( 'bitcoin', 'ibovespa', 'celebridade' ),
			'match_in' => array( 'title', 'excerpt', 'content' ),
		),
	),
	'categories'     => array(
		'producao-safras' => array(
			'name'        => 'Produção & Safras',
			'description' => 'Clima, ciclo de safras, pecuária, insumos e desafios da produção no campo brasileiro.',
			'branding'    => array(
				'brand_name'      => 'Safras',
				'primary_color'   => '#386641',
				'accent_color'    => '#A7C957',
				'secondary_color' => '#6A994E',
				'header_variant'  => 'editoria',
			),
			'feeds'       => array(
				array( 'title' => 'G1 Agronegócios', 'url' => 'https://g1.globo.com/rss/g1/economia/agronegocios/', 'tier' => 1 ),
				array( 'title' => 'Canal Rural', 'url' => 'https://www.canalrural.com.br/feed/', 'tier' => 1 ),
				array( 'title' => 'SNA — Sociedade Nacional de Agricultura', 'url' => 'https://www.sna.agr.br/feed/', 'tier' => 2 ),
			),
			'subcategories' => array(
				'clima-safras' => array(
					'name'        => 'Clima e safras',
					'description' => 'Previsão agrícola, El Niño/La Niña e impacto nas culturas.',
					'keywords'    => array(
						'include' => array( 'safra', 'clima', 'seca', 'chuva', 'soja', 'milho', 'trigo', 'café' ),
						'exclude' => array(),
					),
					'feeds'       => array(
						array( 'title' => 'Canal Rural', 'url' => 'https://www.canalrural.com.br/feed/', 'tier' => 1 ),
						array( 'title' => 'G1 Agronegócios', 'url' => 'https://g1.globo.com/rss/g1/economia/agronegocios/', 'tier' => 1 ),
					),
				),
				'pecuaria'     => array(
					'name'        => 'Pecuária',
					'description' => 'Corte, leite, frango, suínos e sanidade animal.',
					'keywords'    => array(
						'include' => array( 'pecuária', 'pecuaria', 'boi', 'leite', 'frango', 'suíno', 'suino', 'gado' ),
						'exclude' => array(),
					),
					'feeds'       => array(
						array( 'title' => 'Canal Rural', 'url' => 'https://www.canalrural.com.br/feed/', 'tier' => 1 ),
						array( 'title' => 'SNA', 'url' => 'https://www.sna.agr.br/feed/', 'tier' => 2 ),
					),
				),
			),
		),
		'mercado-agro'    => array(
			'name'        => 'Mercado Agro',
			'description' => 'Commodities, exportação, Plano Safra, crédito rural e preços.',
			'branding'    => array(
				'brand_name'      => 'Mercado',
				'primary_color'   => '#2D6A4F',
				'accent_color'    => '#95D5B2',
				'secondary_color' => '#1B4332',
				'header_variant'  => 'editoria',
			),
			'feeds'       => array(
				array( 'title' => 'Valor Agronegócios', 'url' => 'https://pox.globo.com/rss/valor/agronegocios', 'tier' => 1 ),
				array( 'title' => 'InfoMoney Agronegócio', 'url' => 'https://www.infomoney.com.br/tudo-sobre/agronegocio/feed/', 'tier' => 2 ),
			),
			'subcategories' => array(
				'commodities'  => array(
					'name'        => 'Commodities',
					'description' => 'Soja, milho, café, açúcar, algodão e cotações.',
					'keywords'    => array(
						'include' => array( 'commodity', 'commodities', 'soja', 'milho', 'açúcar', 'acucar', 'algodão', 'algodao', 'cotação', 'cotacao' ),
						'exclude' => array(),
					),
					'feeds'       => array(
						array( 'title' => 'Valor Agronegócios', 'url' => 'https://pox.globo.com/rss/valor/agronegocios', 'tier' => 1 ),
						array( 'title' => 'Canal Rural', 'url' => 'https://www.canalrural.com.br/feed/', 'tier' => 2 ),
					),
				),
				'exportacao'   => array(
					'name'        => 'Exportação e comércio',
					'description' => 'Embarques, portos, China, tarifas e balança comercial agrícola.',
					'keywords'    => array(
						'include' => array( 'exportação', 'exportacao', 'embarque', 'porto', 'china', 'tarifa', 'usda' ),
						'exclude' => array(),
					),
					'feeds'       => array(
						array( 'title' => 'Valor Agronegócios', 'url' => 'https://pox.globo.com/rss/valor/agronegocios', 'tier' => 1 ),
						array( 'title' => 'G1 Agronegócios', 'url' => 'https://g1.globo.com/rss/g1/economia/agronegocios/', 'tier' => 2 ),
					),
				),
			),
		),
		'agroecologia'    => array(
			'name'        => 'Agroecologia',
			'description' => 'Permacultura, SAFs, agroecologia e transição para sistemas regenerativos.',
			'branding'    => array(
				'brand_name'      => 'Terra',
				'primary_color'   => '#1B4332',
				'accent_color'    => '#74C69D',
				'secondary_color' => '#2D6A4F',
				'header_variant'  => 'editoria',
			),
			'feeds'       => array(
				array( 'title' => 'ANA — Articulação Nacional de Agroecologia', 'url' => 'https://agroecologia.org.br/feed/', 'tier' => 1 ),
				array( 'title' => 'CEPAGRO', 'url' => 'https://cepagro.org.br/?feed=rss2', 'tier' => 1 ),
				array( 'title' => 'Rede NEPerma Brasil', 'url' => 'https://redepermacultura.ufsc.br/feed/', 'tier' => 2 ),
			),
			'subcategories' => array(
				'safs-permacultura' => array(
					'name'        => 'SAFs e permacultura',
					'description' => 'Sistemas agroflorestais, manejo regenerativo e práticas de campo.',
					'keywords'    => array(
						'include' => array( 'agroecologia', 'permacultura', 'saf', 'agroflorestal', 'regenerativo', 'orgânico', 'organico' ),
						'exclude' => array(),
					),
					'feeds'       => array(
						array( 'title' => 'ANA', 'url' => 'https://agroecologia.org.br/feed/', 'tier' => 1 ),
						array( 'title' => 'CEPAGRO', 'url' => 'https://cepagro.org.br/?feed=rss2', 'tier' => 1 ),
						array( 'title' => 'IPOEMA', 'url' => 'https://ipoema.org.br/feed/', 'tier' => 3 ),
					),
				),
			),
		),
	),
);
