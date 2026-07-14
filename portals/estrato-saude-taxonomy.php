<?php
/**
 * Taxonomia — Estrato Saúde (schema v2). P1 G1/Exame/IstoÉ.
 *
 * @return array<string, mixed>
 */
return array(
	'schema_version' => 2,
	'portal_id'      => 'estrato-saude',
	'menu_order'     => array(
		'medicina', 'prevencao', 'bem-estar',
	),
	'column_order'   => array(),
	'index_order'    => array(
		'medicina', 'prevencao', 'bem-estar',
	),
	'legacy_merge'   => array(),
	'legacy_noindex' => array(),
	'defaults'       => array(
		'branding' => array(
			'primary_color'   => '#0E7490',
			'accent_color'    => '#22D3EE',
			'secondary_color' => '#155E75',
			'header_variant'  => 'editoria',
		),
		'keywords' => array(
			'include'  => array(),
			'exclude'  => array(),
			'match_in' => array( 'title', 'excerpt', 'content' ),
		),
	),
	'categories'     => array(
		'medicina' => array(
			'name'        => 'Medicina',
			'description' => 'Pesquisas, tratamentos e sistema de saúde.',
			'branding'    => array(
				'brand_name'      => 'Clínica',
				'primary_color'   => '#0E7490',
				'accent_color'    => '#22D3EE',
				'secondary_color' => '#155E75',
				'header_variant'  => 'editoria',
			),
			'keywords'    => array(
				'include' => array(),
				'exclude' => array(),
			),
			'feeds'       => array(
				array(
					'title' => 'G1 Saúde',
					'url'   => 'https://g1.globo.com/rss/g1/saude/',
					'tier'  => 1,
				),
				array(
					'title' => 'G1 Ciência e Saúde',
					'url'   => 'https://g1.globo.com/rss/g1/ciencia-e-saude/',
					'tier'  => 1,
				),
			),
			'subcategories' => array(
				'pesquisas' => array(
					'name'        => 'Pesquisas',
					'description' => 'ensaios e descobertas',
					'keywords'    => array(
						'include' => array( 'pesquisa', 'estudo', 'vacina', 'tratamento' ),
						'exclude' => array(),
					),
					'feeds'       => array(
				array(
					'title' => 'G1 Saúde',
					'url'   => 'https://g1.globo.com/rss/g1/saude/',
					'tier'  => 1,
					'keywords' => array(
						'include' => array( 'pesquisa', 'estudo', 'vacina', 'tratamento' ),
						'exclude' => array(),
					),
				),
					),
				),
			),
		),
		'prevencao' => array(
			'name'        => 'Prevenção',
			'description' => 'Hábitos, vírus e saúde pública.',
			'branding'    => array(
				'brand_name'      => 'Prevenir',
				'primary_color'   => '#0E7490',
				'accent_color'    => '#22D3EE',
				'secondary_color' => '#155E75',
				'header_variant'  => 'editoria',
			),
			'keywords'    => array(
				'include' => array(),
				'exclude' => array(),
			),
			'feeds'       => array(
				array(
					'title' => 'Exame (filtro saúde)',
					'url'   => 'https://exame.com/feed/',
					'tier'  => 2,
					'keywords' => array(
						'include' => array( 'saúde', 'saude', 'vacina', 'hospital', 'suspe' ),
						'exclude' => array(),
					),
				),
			),
			'subcategories' => array(
				'saude-publica' => array(
					'name'        => 'Saúde pública',
					'description' => 'SUS e epidemias',
					'keywords'    => array(
						'include' => array( 'sus', 'epidemia', 'surto', 'hospital' ),
						'exclude' => array(),
					),
					'feeds'       => array(
				array(
					'title' => 'Exame (filtro saúde)',
					'url'   => 'https://exame.com/feed/',
					'tier'  => 2,
					'keywords' => array(
						'include' => array( 'sus', 'epidemia', 'surto', 'hospital' ),
						'exclude' => array(),
					),
				),
					),
				),
			),
		),
		'bem-estar' => array(
			'name'        => 'Bem-estar',
			'description' => 'Sono, nutrição e saúde mental cotidiana.',
			'branding'    => array(
				'brand_name'      => 'Bem',
				'primary_color'   => '#0E7490',
				'accent_color'    => '#22D3EE',
				'secondary_color' => '#155E75',
				'header_variant'  => 'editoria',
			),
			'keywords'    => array(
				'include' => array(),
				'exclude' => array(),
			),
			'feeds'       => array(
				array(
					'title' => 'ABS — Blog do Sono',
					'url'   => 'https://absono.com.br/feed/',
					'tier'  => 2,
				),
			),
			'subcategories' => array(
				'mental' => array(
					'name'        => 'Saúde mental',
					'description' => 'ansiedade e hábitos',
					'keywords'    => array(
						'include' => array( 'ansiedade', 'depressão', 'depressao', 'sono', 'mental' ),
						'exclude' => array(),
					),
					'feeds'       => array(
				array(
					'title' => 'ABS — Blog do Sono',
					'url'   => 'https://absono.com.br/feed/',
					'tier'  => 2,
					'keywords' => array(
						'include' => array( 'ansiedade', 'depressão', 'depressao', 'sono', 'mental' ),
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
