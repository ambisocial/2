<?php
/**
 * Taxonomia — Estilos de vida, hobbies e consumo apaixonado (schema v2).
 *
 * 3 editorias × 6 nichos (subcategorias) — bullets 5–10 do segmento lifestyle.
 *
 * @return array<string, mixed>
 */
return array(
	'schema_version' => 2,
	'portal_id'      => 'estrato-lifestyle',
	'menu_order'     => array(
		'sabores-paixao',
		'movimento-ar-livre',
		'hobbies-colecao',
	),
	'column_order'   => array(),
	'index_order'    => array(
		'sabores-paixao',
		'movimento-ar-livre',
		'hobbies-colecao',
	),
	'legacy_merge'   => array(),
	'legacy_noindex' => array(),
	'defaults'       => array(
		'branding' => array(
			'primary_color'   => '#2C1810',
			'accent_color'    => '#E8A838',
			'secondary_color' => '#4A3728',
			'header_variant'  => 'editoria',
		),
		'keywords' => array(
			'include'  => array(),
			'exclude'  => array(),
			'match_in' => array( 'title', 'excerpt', 'content' ),
		),
	),
	'categories'     => array(
		'sabores-paixao'     => array(
			'name'        => 'Sabores & Paixão',
			'description' => 'Café especial, cerveja artesanal e culinária vegana além do básico — tribos brasileiras de gastronomia apaixonada.',
			'branding'    => array(
				'brand_name'      => 'Sabores',
				'primary_color'   => '#3E2723',
				'accent_color'    => '#D4A574',
				'secondary_color' => '#5D4037',
				'header_variant'  => 'editoria',
			),
			'keywords'    => array(
				'include' => array(),
				'exclude' => array( 'ibovespa', 'selic', 'bitcoin' ),
			),
			'feeds'       => array(
				array( 'title' => 'Brejas', 'url' => 'https://www.brejas.com.br/blog/feed/', 'tier' => 1 ),
			),
			'subcategories' => array(
				'cafe-especial'       => array(
					'name'        => 'Café especial',
					'description' => 'Torrefação artesanal, barismo, métodos de preparo e cafeicultura brasileira.',
					'keywords'    => array(
						'include' => array(
							'café especial',
							'cafe especial',
							'barista',
							'torrefação artesanal',
							'torrefacao artesanal',
							'v60',
							'aeropress',
							'cafeicultura',
							'cup of excellence',
						),
						'exclude' => array(),
					),
					'feeds'       => array(
						array( 'title' => 'Grão Cafés Especiais', 'url' => 'https://graocafes.com.br/feed/', 'tier' => 1 ),
						array( 'title' => 'Revista Cafeicultura', 'url' => 'https://revistacafeicultura.com.br/feed/', 'tier' => 1 ),
						array( 'title' => 'Blog uCoffee', 'url' => 'https://blog.ucoffee.com.br/feed/', 'tier' => 2 ),
						array( 'title' => 'BSCA', 'url' => 'https://www.bsca.com.br/feed/', 'tier' => 3 ),
					),
				),
				'cerveja-homebrew'    => array(
					'name'        => 'Cerveja artesanal & homebrew',
					'description' => 'Cervejeiros caseiros, ACervA, festivais e cultura craft beer brasileira.',
					'keywords'    => array(
						'include' => array(
							'cerveja artesanal',
							'cervejeiro caseiro',
							'homebrewing',
							'acerva',
							'festival cervejeiro',
							'craft beer',
							'brassagem',
							'bjcp',
						),
						'exclude' => array(),
					),
					'feeds'       => array(
						array( 'title' => 'Brejas', 'url' => 'https://www.brejas.com.br/blog/feed/', 'tier' => 1 ),
						array( 'title' => 'Maria Cevada', 'url' => 'https://www.mariacevada.com.br/feed/', 'tier' => 1 ),
					),
				),
				'culinaria-vegana'    => array(
					'name'        => 'Culinária vegana & vegetariana',
					'description' => 'Receitas, ética, cruelty-free, nutrição e estilo de vida plant-based.',
					'keywords'    => array(
						'include' => array(
							'receita vegana',
							'veganismo',
							'vegetariano',
							'cruelty-free',
							'suplementação vegana',
							'suplementacao vegana',
							'nutrição vegetariana',
							'nutricao vegetariana',
							'veganuary',
							'plant-based',
						),
						'exclude' => array(),
					),
					'feeds'       => array(
						array( 'title' => 'Presunto Vegetariano', 'url' => 'https://presuntovegetariano.com.br/feed/', 'tier' => 1 ),
						array( 'title' => 'Jornada Vegana', 'url' => 'https://jornadavegana.com/feed/', 'tier' => 1 ),
						array( 'title' => 'Sociedade Vegetariana Brasileira', 'url' => 'https://svb.org.br/feed/', 'tier' => 2 ),
					),
				),
			),
		),
		'movimento-ar-livre' => array(
			'name'        => 'Movimento & Ar Livre',
			'description' => 'Bicicleta como estilo de vida: ciclismo urbano, cicloturismo, bikepacking e cultura fixie.',
			'branding'    => array(
				'brand_name'      => 'Pedal',
				'primary_color'   => '#1B4332',
				'accent_color'    => '#52B788',
				'secondary_color' => '#2D6A4F',
				'header_variant'  => 'editoria',
			),
			'keywords'    => array(
				'include' => array(),
				'exclude' => array(),
			),
			'feeds'       => array(
				array( 'title' => 'Vá de Bike', 'url' => 'https://vadebike.org/feed/', 'tier' => 1 ),
			),
			'subcategories' => array(
				'ciclismo-estilo' => array(
					'name'        => 'Ciclismo & estilo de vida',
					'description' => 'Cicloturismo, bikepacking, mobilidade urbana e cultura da bicicleta no Brasil.',
					'keywords'    => array(
						'include' => array(
							'cicloturismo',
							'bikepacking',
							'ciclismo urbano',
							'bike fixa',
							'fixed gear',
							'cicloviagem',
							'mobilidade ciclo',
							'magrela',
						),
						'exclude' => array(),
					),
					'feeds'       => array(
						array( 'title' => 'Vá de Bike', 'url' => 'https://vadebike.org/feed/', 'tier' => 1 ),
						array( 'title' => 'Cicloaventureiro', 'url' => 'https://www.cicloaventureiro.com.br/feed/', 'tier' => 1 ),
						array( 'title' => 'FIXA SAMPA', 'url' => 'https://fixasampa.wordpress.com/feed/', 'tier' => 2 ),
						array( 'title' => 'Eu e a Magrela', 'url' => 'https://eueamagrela.wordpress.com/feed/', 'tier' => 2 ),
						array( 'title' => 'Ciclomundo', 'url' => 'https://ciclomundoblog.wordpress.com/feed/', 'tier' => 2 ),
					),
				),
			),
		),
		'hobbies-colecao'    => array(
			'name'        => 'Hobbies & Colecionismo',
			'description' => 'Aquarismo, aquapaisagismo e relojoaria acessível — microbrands e comunidades de entusiastas.',
			'branding'    => array(
				'brand_name'      => 'Hobbies',
				'primary_color'   => '#0D1B2A',
				'accent_color'    => '#48CAE4',
				'secondary_color' => '#1B263B',
				'header_variant'  => 'editoria',
			),
			'keywords'    => array(
				'include' => array(),
				'exclude' => array(),
			),
			'feeds'       => array(
				array( 'title' => 'AquaA3 Aquarismo', 'url' => 'https://aquaa3.com.br/feed/', 'tier' => 1 ),
			),
			'subcategories' => array(
				'aquarismo-planted'   => array(
					'name'        => 'Aquarismo & aquapaisagismo',
					'description' => 'Aquário plantado, Iwagumi, hardscape e cultura aquarista brasileira.',
					'keywords'    => array(
						'include' => array(
							'aquário plantado',
							'aquario plantado',
							'aquapaisagismo',
							'nano aquário',
							'nano aquario',
							'iwagumi',
							'hardscape',
							'aquascape',
							'cbap',
							'iaplc',
						),
						'exclude' => array(),
					),
					'feeds'       => array(
						array( 'title' => 'AquaA3 Aquarismo', 'url' => 'https://aquaa3.com.br/feed/', 'tier' => 1 ),
						array( 'title' => 'Grupo Sarlo', 'url' => 'https://gruposarlo.com.br/feed/', 'tier' => 2 ),
						array( 'title' => 'CBAP', 'url' => 'http://www.cbap.com.br/feed/', 'tier' => 2 ),
					),
				),
				'relojoaria-micro'    => array(
					'name'        => 'Relojoaria & microbrands',
					'description' => 'Relógios mecânicos acessíveis, microbrands brasileiras e cultura horológica.',
					'keywords'    => array(
						'include' => array(
							'relojoaria',
							'relógio automático',
							'relogio automatico',
							'relógio mecânico',
							'relogio mecanico',
							'microbrands',
							'microbrand',
							'colecionador de relógios',
							'seiko',
							'orient',
							'statera',
							'terranova watches',
						),
						'exclude' => array(),
					),
					'feeds'       => array(
						array(
							'title'    => 'GMTmenos3 (YouTube)',
							'url'      => 'https://www.youtube.com/feeds/videos.xml?channel_id=UCCNB9TCO5_TbfsPpzJ04Dfw',
							'tier'     => 1,
							'verified' => true,
						),
						array(
							'title'    => 'Bernardo Britto | Entusiasta (YouTube)',
							'url'      => 'https://www.youtube.com/feeds/videos.xml?channel_id=UClTpR17xvQ4orhr-TRwN00A',
							'tier'     => 1,
							'verified' => true,
						),
						array( 'title' => 'Relógios Mecânicos', 'url' => 'https://relogiosmecanicos.com.br/feed/', 'tier' => 1 ),
						array(
							'title' => 'Fórum Relógios Mecânicos',
							'url'   => 'https://forum.relogiosmecanicos.com.br/index.php?action=.xml;type=rss2',
							'tier'  => 2,
						),
					),
				),
			),
		),
	),
	'columns'        => array(),
);
