<?php
/**
 * Taxonomia — Estrato Esporte (schema v2). P1 G1/Exame/IstoÉ.
 *
 * @return array<string, mixed>
 */
return array(
	'schema_version' => 2,
	'portal_id'      => 'estrato-esporte',
	'menu_order'     => array(
		'futebol', 'olimpiadas', 'mais-esportes',
	),
	'column_order'   => array(),
	'index_order'    => array(
		'futebol', 'olimpiadas', 'mais-esportes',
	),
	'legacy_merge'   => array(),
	'legacy_noindex' => array(),
	'defaults'       => array(
		'branding' => array(
			'primary_color'   => '#14532D',
			'accent_color'    => '#22C55E',
			'secondary_color' => '#166534',
			'header_variant'  => 'editoria',
		),
		'keywords' => array(
			'include'  => array(),
			'exclude'  => array(),
			'match_in' => array( 'title', 'excerpt', 'content' ),
		),
	),
	'categories'     => array(
		'futebol' => array(
			'name'        => 'Futebol',
			'description' => 'Brasileirão, seleções e copas.',
			'branding'    => array(
				'brand_name'      => 'Bola',
				'primary_color'   => '#14532D',
				'accent_color'    => '#22C55E',
				'secondary_color' => '#166534',
				'header_variant'  => 'editoria',
			),
			'keywords'    => array(
				'include' => array(),
				'exclude' => array(),
			),
			'feeds'       => array(
				array(
					'title' => 'GE Futebol',
					'url'   => 'https://ge.globo.com/dynamo/futebol/rss2.xml',
					'tier'  => 1,
				),
				array(
					'title' => 'Gazeta Esportiva',
					'url'   => 'https://www.gazetaesportiva.com/feed/',
					'tier'  => 2,
				),
			),
			'subcategories' => array(
				'brasileirao' => array(
					'name'        => 'Brasileirão',
					'description' => 'série A e clubes',
					'keywords'    => array(
						'include' => array( 'brasileirão', 'brasileirao', 'série a', 'serie a' ),
						'exclude' => array(),
					),
					'feeds'       => array(
				array(
					'title' => 'GE Futebol',
					'url'   => 'https://ge.globo.com/dynamo/futebol/rss2.xml',
					'tier'  => 1,
					'keywords' => array(
						'include' => array( 'brasileirão', 'brasileirao', 'série a', 'serie a' ),
						'exclude' => array(),
					),
				),
					),
				),
				'selecao' => array(
					'name'        => 'Seleção',
					'description' => 'canarinho e convocações',
					'keywords'    => array(
						'include' => array( 'seleção', 'selecao', 'convocação', 'convocacao' ),
						'exclude' => array(),
					),
					'feeds'       => array(
				array(
					'title' => 'GE Futebol',
					'url'   => 'https://ge.globo.com/dynamo/futebol/rss2.xml',
					'tier'  => 1,
					'keywords' => array(
						'include' => array( 'seleção', 'selecao', 'convocação', 'convocacao' ),
						'exclude' => array(),
					),
				),
					),
				),
			),
		),
		'olimpiadas' => array(
			'name'        => 'Olímpicos',
			'description' => 'Modalidades olímpicas e event cycles.',
			'branding'    => array(
				'brand_name'      => 'Arena',
				'primary_color'   => '#14532D',
				'accent_color'    => '#22C55E',
				'secondary_color' => '#166534',
				'header_variant'  => 'editoria',
			),
			'keywords'    => array(
				'include' => array(),
				'exclude' => array(),
			),
			'feeds'       => array(
				array(
					'title' => 'GE Geral',
					'url'   => 'https://ge.globo.com/dynamo/rss2.xml',
					'tier'  => 1,
				),
			),
			'subcategories' => array(
				'modalidades' => array(
					'name'        => 'Modalidades',
					'description' => 'atletismo, natação, vôlei',
					'keywords'    => array(
						'include' => array( 'olímpico', 'olimpico', 'vôlei', 'volei', 'natação', 'natacao' ),
						'exclude' => array(),
					),
					'feeds'       => array(
				array(
					'title' => 'GE Geral',
					'url'   => 'https://ge.globo.com/dynamo/rss2.xml',
					'tier'  => 1,
					'keywords' => array(
						'include' => array( 'olímpico', 'olimpico', 'vôlei', 'volei', 'natação', 'natacao' ),
						'exclude' => array(),
					),
				),
					),
				),
			),
		),
		'mais-esportes' => array(
			'name'        => 'Mais esportes',
			'description' => 'Basquete, tênis, combate e sportsbiz.',
			'branding'    => array(
				'brand_name'      => 'Mais',
				'primary_color'   => '#14532D',
				'accent_color'    => '#22C55E',
				'secondary_color' => '#166534',
				'header_variant'  => 'editoria',
			),
			'keywords'    => array(
				'include' => array(),
				'exclude' => array(),
			),
			'feeds'       => array(
				array(
					'title' => 'Exame Esporte (filtro)',
					'url'   => 'https://exame.com/feed/',
					'tier'  => 2,
					'keywords' => array(
						'include' => array( 'esporte', 'futebol', 'nba', 'tênis', 'tenis', 'mma' ),
						'exclude' => array(),
					),
				),
			),
			'subcategories' => array(
				'business' => array(
					'name'        => 'Sportsbiz',
					'description' => 'negócios do esporte',
					'keywords'    => array(
						'include' => array( 'patrocínio', 'patrocinio', 'clube', 'liga' ),
						'exclude' => array(),
					),
					'feeds'       => array(
				array(
					'title' => 'Exame Esporte (filtro)',
					'url'   => 'https://exame.com/feed/',
					'tier'  => 2,
					'keywords' => array(
						'include' => array( 'patrocínio', 'patrocinio', 'clube', 'liga' ),
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
