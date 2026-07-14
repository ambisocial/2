<?php
/**
 * Taxonomia — Viagem (schema v2). Frente ex-Sustain.
 *
 * Destinos, rotas, nômades e consumo de viagens.
 *
 * @return array<string, mixed>
 */
return array(
	'schema_version' => 2,
	'portal_id'      => 'estrato-viagem',
	'menu_order'     => array(
		'destinos',
		'rotas-dicas',
		'nomadismo',
	),
	'column_order'   => array(),
	'index_order'    => array(
		'destinos',
		'rotas-dicas',
		'nomadismo',
	),
	'legacy_merge'   => array(
		'vida-nomade' => 'nomadismo',
	),
	'legacy_noindex' => array(
		'vida-nomade',
	),
	'defaults'       => array(
		'branding' => array(
			'primary_color'   => '#023E8A',
			'accent_color'    => '#48CAE4',
			'secondary_color' => '#0077B6',
			'header_variant'  => 'editoria',
		),
		'keywords' => array(
			'include'  => array(),
			'exclude'  => array( 'ibovespa', 'bitcoin', 'selic' ),
			'match_in' => array( 'title', 'excerpt', 'content' ),
		),
	),
	'categories'     => array(
		'destinos'     => array(
			'name'        => 'Destinos',
			'description' => 'Cidades, regiões e roteiros no Brasil e no exterior.',
			'branding'    => array(
				'brand_name'      => 'Destinos',
				'primary_color'   => '#023E8A',
				'accent_color'    => '#48CAE4',
				'secondary_color' => '#0077B6',
				'header_variant'  => 'editoria',
			),
			'feeds'       => array(
				array( 'title' => 'G1 Turismo e Viagem', 'url' => 'https://g1.globo.com/rss/g1/turismo-e-viagem/', 'tier' => 1 ),
				array( 'title' => 'Melhores Destinos', 'url' => 'https://www.melhoresdestinos.com.br/feed', 'tier' => 1 ),
				array( 'title' => '360 Meridianos', 'url' => 'https://www.360meridianos.com/feed', 'tier' => 2 ),
			),
			'subcategories' => array(
				'brasil'   => array(
					'name'        => 'Brasil',
					'description' => 'Destinos, praias, cidades e rotas nacionais.',
					'keywords'    => array(
						'include' => array( 'brasil', 'praia', 'cidade', 'nordeste', 'amazônia', 'amazonia', 'rio', 'são paulo', 'sao paulo' ),
						'exclude' => array(),
					),
					'feeds'       => array(
						array( 'title' => 'G1 Turismo e Viagem', 'url' => 'https://g1.globo.com/rss/g1/turismo-e-viagem/', 'tier' => 1 ),
						array( 'title' => 'Melhores Destinos', 'url' => 'https://www.melhoresdestinos.com.br/feed', 'tier' => 1 ),
					),
				),
				'internacional' => array(
					'name'        => 'Internacional',
					'description' => 'Europa, Ásia, Américas e destinos long-haul.',
					'keywords'    => array(
						'include' => array( 'europa', 'ásia', 'asia', 'portugal', 'espanha', 'eua', 'japão', 'japao', 'tailândia', 'tailandia' ),
						'exclude' => array(),
					),
					'feeds'       => array(
						array( 'title' => '360 Meridianos', 'url' => 'https://www.360meridianos.com/feed', 'tier' => 1 ),
						array( 'title' => 'Viaje na Viagem', 'url' => 'https://www.viajenaviagem.com/feed/', 'tier' => 1 ),
					),
				),
			),
		),
		'rotas-dicas'  => array(
			'name'        => 'Rotas & dicas',
			'description' => 'Passagens, milhas, hospedagem e planejamento prático.',
			'branding'    => array(
				'brand_name'      => 'Rotas',
				'primary_color'   => '#0077B6',
				'accent_color'    => '#FFB703',
				'secondary_color' => '#023E8A',
				'header_variant'  => 'editoria',
			),
			'feeds'       => array(
				array( 'title' => 'Viaje na Viagem', 'url' => 'https://www.viajenaviagem.com/feed/', 'tier' => 1 ),
				array( 'title' => 'Blog 123 Milhas', 'url' => 'https://blog.123milhas.com/feed/', 'tier' => 2 ),
				array( 'title' => 'Melhores Destinos', 'url' => 'https://www.melhoresdestinos.com.br/feed', 'tier' => 1 ),
			),
			'subcategories' => array(
				'passagens-milhas' => array(
					'name'        => 'Passagens e milhas',
					'description' => 'Alertas de preço, milhas e companhias aéreas.',
					'keywords'    => array(
						'include' => array( 'passagem', 'milhas', 'aérea', 'aerea', 'voo', 'tarifa', 'promoção', 'promocao' ),
						'exclude' => array(),
					),
					'feeds'       => array(
						array( 'title' => 'Melhores Destinos', 'url' => 'https://www.melhoresdestinos.com.br/feed', 'tier' => 1 ),
						array( 'title' => 'Blog 123 Milhas', 'url' => 'https://blog.123milhas.com/feed/', 'tier' => 2 ),
					),
				),
				'hospedagem'       => array(
					'name'        => 'Hospedagem',
					'description' => 'Hotéis, Airbnb e dicas de estadia.',
					'keywords'    => array(
						'include' => array( 'hotel', 'hospedagem', 'airbnb', 'pousada', 'resort' ),
						'exclude' => array(),
					),
					'feeds'       => array(
						array( 'title' => 'Viaje na Viagem', 'url' => 'https://www.viajenaviagem.com/feed/', 'tier' => 1 ),
						array( 'title' => 'Melhores Destinos', 'url' => 'https://www.melhoresdestinos.com.br/feed', 'tier' => 2 ),
					),
				),
			),
		),
		'nomadismo'    => array(
			'name'        => 'Nomadismo',
			'description' => 'Vida nômade digital, slow travel e trabalho remoto em trânsito.',
			'branding'    => array(
				'brand_name'      => 'Nômade',
				'primary_color'   => '#4A306D',
				'accent_color'    => '#E9C46A',
				'secondary_color' => '#264653',
				'header_variant'  => 'editoria',
			),
			'feeds'       => array(
				array( 'title' => 'Passageiro — Matheus de Souza', 'url' => 'https://passageiro.news/feed', 'tier' => 1 ),
				array( 'title' => 'Juju na Trip', 'url' => 'https://jujunatrip.com/feed/', 'tier' => 1 ),
				array( 'title' => 'O Mundo é uma Escola', 'url' => 'https://omundoeumaescola.wordpress.com/feed/', 'tier' => 3 ),
			),
			'subcategories' => array(
				'nomade-digital' => array(
					'name'        => 'Nômade digital',
					'description' => 'Workation, visto, fiscalidade e lifestyle remoto.',
					'keywords'    => array(
						'include' => array( 'nômade', 'nomade', 'digital nomad', 'workation', 'remoto', 'visto' ),
						'exclude' => array(),
					),
					'feeds'       => array(
						array( 'title' => 'Passageiro', 'url' => 'https://passageiro.news/feed', 'tier' => 1 ),
						array( 'title' => 'Juju na Trip', 'url' => 'https://jujunatrip.com/feed/', 'tier' => 1 ),
						array( 'title' => 'Brasil Tax', 'url' => 'https://brasiltax.com/blog/feed/', 'tier' => 3 ),
					),
				),
			),
		),
	),
);
