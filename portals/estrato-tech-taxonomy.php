<?php
/**
 * Taxonomia — Estrato Tech (schema v2). P1 G1/Exame/IstoÉ.
 *
 * @return array<string, mixed>
 */
return array(
	'schema_version' => 2,
	'portal_id'      => 'estrato-tech',
	'menu_order'     => array(
		'tecnologia', 'inovacao', 'gadgets',
	),
	'column_order'   => array(),
	'index_order'    => array(
		'tecnologia', 'inovacao', 'gadgets',
	),
	'legacy_merge'   => array(),
	'legacy_noindex' => array(),
	'defaults'       => array(
		'branding' => array(
			'primary_color'   => '#0F172A',
			'accent_color'    => '#38BDF8',
			'secondary_color' => '#1E293B',
			'header_variant'  => 'editoria',
		),
		'keywords' => array(
			'include'  => array(),
			'exclude'  => array(),
			'match_in' => array( 'title', 'excerpt', 'content' ),
		),
	),
	'categories'     => array(
		'tecnologia' => array(
			'name'        => 'Tecnologia',
			'description' => 'Big techs, internet e política digital.',
			'branding'    => array(
				'brand_name'      => 'Tech',
				'primary_color'   => '#0F172A',
				'accent_color'    => '#38BDF8',
				'secondary_color' => '#1E293B',
				'header_variant'  => 'editoria',
			),
			'keywords'    => array(
				'include' => array(),
				'exclude' => array(),
			),
			'feeds'       => array(
				array(
					'title' => 'G1 Tecnologia',
					'url'   => 'https://g1.globo.com/rss/g1/tecnologia/',
					'tier'  => 1,
				),
				array(
					'title' => 'Tecnoblog',
					'url'   => 'https://tecnoblog.net/feed/',
					'tier'  => 1,
				),
			),
			'subcategories' => array(
				'internet' => array(
					'name'        => 'Internet e apps',
					'description' => 'plataformas e redes',
					'keywords'    => array(
						'include' => array( 'app', 'google', 'meta', 'apple', 'internet' ),
						'exclude' => array(),
					),
					'feeds'       => array(
				array(
					'title' => 'G1 Tecnologia',
					'url'   => 'https://g1.globo.com/rss/g1/tecnologia/',
					'tier'  => 1,
					'keywords' => array(
						'include' => array( 'app', 'google', 'meta', 'apple', 'internet' ),
						'exclude' => array(),
					),
				),
					),
				),
			),
		),
		'inovacao' => array(
			'name'        => 'Inovação',
			'description' => 'Startups, IA aplicada e novos produtos.',
			'branding'    => array(
				'brand_name'      => 'Inova',
				'primary_color'   => '#0F172A',
				'accent_color'    => '#38BDF8',
				'secondary_color' => '#1E293B',
				'header_variant'  => 'editoria',
			),
			'keywords'    => array(
				'include' => array(),
				'exclude' => array(),
			),
			'feeds'       => array(
				array(
					'title' => 'Canaltech',
					'url'   => 'https://canaltech.com.br/rss/',
					'tier'  => 1,
				),
			),
			'subcategories' => array(
				'ia-produtos' => array(
					'name'        => 'IA e produtos',
					'description' => 'ferramentas e lançamentos',
					'keywords'    => array(
						'include' => array( 'inteligência artificial', 'inteligencia artificial', 'chatgpt', 'ia' ),
						'exclude' => array(),
					),
					'feeds'       => array(
				array(
					'title' => 'Canaltech',
					'url'   => 'https://canaltech.com.br/rss/',
					'tier'  => 1,
					'keywords' => array(
						'include' => array( 'inteligência artificial', 'inteligencia artificial', 'chatgpt', 'ia' ),
						'exclude' => array(),
					),
				),
					),
				),
			),
		),
		'gadgets' => array(
			'name'        => 'Gadgets',
			'description' => 'Hardware, mobile e reviews.',
			'branding'    => array(
				'brand_name'      => 'Gear',
				'primary_color'   => '#0F172A',
				'accent_color'    => '#38BDF8',
				'secondary_color' => '#1E293B',
				'header_variant'  => 'editoria',
			),
			'keywords'    => array(
				'include' => array(),
				'exclude' => array(),
			),
			'feeds'       => array(
				array(
					'title' => 'Exame (filtro tech)',
					'url'   => 'https://exame.com/feed/',
					'tier'  => 2,
					'keywords' => array(
						'include' => array( 'tecnologia', 'smartphone', 'gadget', 'chip', 'IA' ),
						'exclude' => array(),
					),
				),
			),
			'subcategories' => array(
				'mobile' => array(
					'name'        => 'Mobile',
					'description' => 'celulares e wearables',
					'keywords'    => array(
						'include' => array( 'smartphone', 'celular', 'android', 'iphone' ),
						'exclude' => array(),
					),
					'feeds'       => array(
				array(
					'title' => 'Exame (filtro tech)',
					'url'   => 'https://exame.com/feed/',
					'tier'  => 2,
					'keywords' => array(
						'include' => array( 'smartphone', 'celular', 'android', 'iphone' ),
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
