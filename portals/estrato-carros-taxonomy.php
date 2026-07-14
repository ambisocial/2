<?php
/**
 * Taxonomia — Estrato Carros (schema v2). P1 G1/Exame/IstoÉ.
 *
 * @return array<string, mixed>
 */
return array(
	'schema_version' => 2,
	'portal_id'      => 'estrato-carros',
	'menu_order'     => array(
		'automoveis', 'eletricos', 'mobilidade',
	),
	'column_order'   => array(),
	'index_order'    => array(
		'automoveis', 'eletricos', 'mobilidade',
	),
	'legacy_merge'   => array(),
	'legacy_noindex' => array(),
	'defaults'       => array(
		'branding' => array(
			'primary_color'   => '#7C2D12',
			'accent_color'    => '#FB923C',
			'secondary_color' => '#9A3412',
			'header_variant'  => 'editoria',
		),
		'keywords' => array(
			'include'  => array(),
			'exclude'  => array(),
			'match_in' => array( 'title', 'excerpt', 'content' ),
		),
	),
	'categories'     => array(
		'automoveis' => array(
			'name'        => 'Automóveis',
			'description' => 'Lançamentos, testes e indústria auto.',
			'branding'    => array(
				'brand_name'      => 'Auto',
				'primary_color'   => '#7C2D12',
				'accent_color'    => '#FB923C',
				'secondary_color' => '#9A3412',
				'header_variant'  => 'editoria',
			),
			'keywords'    => array(
				'include' => array(),
				'exclude' => array(),
			),
			'feeds'       => array(
				array(
					'title' => 'G1 Carros',
					'url'   => 'https://g1.globo.com/rss/g1/carros/',
					'tier'  => 1,
				),
				array(
					'title' => 'Quatro Rodas',
					'url'   => 'https://quatrorodas.abril.com.br/feed/',
					'tier'  => 1,
				),
			),
			'subcategories' => array(
				'lancamentos' => array(
					'name'        => 'Lançamentos',
					'description' => 'novidades e recalls',
					'keywords'    => array(
						'include' => array( 'lançamento', 'lancamento', 'modelo', 'recall' ),
						'exclude' => array(),
					),
					'feeds'       => array(
				array(
					'title' => 'G1 Carros',
					'url'   => 'https://g1.globo.com/rss/g1/carros/',
					'tier'  => 1,
					'keywords' => array(
						'include' => array( 'lançamento', 'lancamento', 'modelo', 'recall' ),
						'exclude' => array(),
					),
				),
					),
				),
			),
		),
		'eletricos' => array(
			'name'        => 'Elétricos',
			'description' => 'EVs, híbridos e infraestrutura de carga.',
			'branding'    => array(
				'brand_name'      => 'EV',
				'primary_color'   => '#7C2D12',
				'accent_color'    => '#FB923C',
				'secondary_color' => '#9A3412',
				'header_variant'  => 'editoria',
			),
			'keywords'    => array(
				'include' => array(),
				'exclude' => array(),
			),
			'feeds'       => array(
				array(
					'title' => 'FlatOut',
					'url'   => 'https://www.flatout.com.br/feed/',
					'tier'  => 1,
				),
			),
			'subcategories' => array(
				'ev' => array(
					'name'        => 'EV e híbridos',
					'description' => 'bateria e autonomia',
					'keywords'    => array(
						'include' => array( 'elétrico', 'eletrico', 'híbrido', 'hibrido', 'bateria' ),
						'exclude' => array(),
					),
					'feeds'       => array(
				array(
					'title' => 'FlatOut',
					'url'   => 'https://www.flatout.com.br/feed/',
					'tier'  => 1,
					'keywords' => array(
						'include' => array( 'elétrico', 'eletrico', 'híbrido', 'hibrido', 'bateria' ),
						'exclude' => array(),
					),
				),
					),
				),
			),
		),
		'mobilidade' => array(
			'name'        => 'Mobilidade',
			'description' => 'Cidades, apps de transporte e políticas urbanas.',
			'branding'    => array(
				'brand_name'      => 'Mob',
				'primary_color'   => '#7C2D12',
				'accent_color'    => '#FB923C',
				'secondary_color' => '#9A3412',
				'header_variant'  => 'editoria',
			),
			'keywords'    => array(
				'include' => array(),
				'exclude' => array(),
			),
			'feeds'       => array(
				array(
					'title' => 'Exame (filtro mobilidade)',
					'url'   => 'https://exame.com/feed/',
					'tier'  => 2,
					'keywords' => array(
						'include' => array( 'carro', 'automóvel', 'automovel', 'mobilidade', 'trânsito', 'transito' ),
						'exclude' => array(),
					),
				),
			),
			'subcategories' => array(
				'urbana' => array(
					'name'        => 'Mobilidade urbana',
					'description' => 'transporte e apps',
					'keywords'    => array(
						'include' => array( 'uber', '99', 'ônibus', 'onibus', 'mobilidade' ),
						'exclude' => array(),
					),
					'feeds'       => array(
				array(
					'title' => 'Exame (filtro mobilidade)',
					'url'   => 'https://exame.com/feed/',
					'tier'  => 2,
					'keywords' => array(
						'include' => array( 'uber', '99', 'ônibus', 'onibus', 'mobilidade' ),
						'exclude' => array(),
					),
				),
					),
				),
			),
		),
	),
	'columns' => array(),
);
