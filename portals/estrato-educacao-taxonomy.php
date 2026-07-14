<?php
/**
 * Taxonomia — Estrato Educação (schema v2). P1 G1/Exame/IstoÉ.
 *
 * @return array<string, mixed>
 */
return array(
	'schema_version' => 2,
	'portal_id'      => 'estrato-educacao',
	'menu_order'     => array(
		'educacao-base', 'carreira', 'empreendedorismo',
	),
	'column_order'   => array(),
	'index_order'    => array(
		'educacao-base', 'carreira', 'empreendedorismo',
	),
	'legacy_merge'   => array(),
	'legacy_noindex' => array(),
	'defaults'       => array(
		'branding' => array(
			'primary_color'   => '#5B21B6',
			'accent_color'    => '#A78BFA',
			'secondary_color' => '#4C1D95',
			'header_variant'  => 'editoria',
		),
		'keywords' => array(
			'include'  => array(),
			'exclude'  => array(),
			'match_in' => array( 'title', 'excerpt', 'content' ),
		),
	),
	'categories'     => array(
		'educacao-base' => array(
			'name'        => 'Educação',
			'description' => 'Enem, vestibulares e política educacional.',
			'branding'    => array(
				'brand_name'      => 'Estudo',
				'primary_color'   => '#5B21B6',
				'accent_color'    => '#A78BFA',
				'secondary_color' => '#4C1D95',
				'header_variant'  => 'editoria',
			),
			'keywords'    => array(
				'include' => array(),
				'exclude' => array(),
			),
			'feeds'       => array(
				array(
					'title' => 'G1 Educação',
					'url'   => 'https://g1.globo.com/rss/g1/educacao/',
					'tier'  => 1,
				),
			),
			'subcategories' => array(
				'enem' => array(
					'name'        => 'Enem e vestibulares',
					'description' => 'provas e calendário',
					'keywords'    => array(
						'include' => array( 'enem', 'vestibular', 'prouni', 'fies' ),
						'exclude' => array(),
					),
					'feeds'       => array(
				array(
					'title' => 'G1 Educação',
					'url'   => 'https://g1.globo.com/rss/g1/educacao/',
					'tier'  => 1,
					'keywords' => array(
						'include' => array( 'enem', 'vestibular', 'prouni', 'fies' ),
						'exclude' => array(),
					),
				),
					),
				),
			),
		),
		'carreira' => array(
			'name'        => 'Carreira',
			'description' => 'Mercado de trabalho, soft skills e escala.',
			'branding'    => array(
				'brand_name'      => 'Carreira',
				'primary_color'   => '#5B21B6',
				'accent_color'    => '#A78BFA',
				'secondary_color' => '#4C1D95',
				'header_variant'  => 'editoria',
			),
			'keywords'    => array(
				'include' => array(),
				'exclude' => array(),
			),
			'feeds'       => array(
				array(
					'title' => 'G1 Trabalho e Carreira',
					'url'   => 'https://g1.globo.com/rss/g1/trabalho-e-carreira/',
					'tier'  => 1,
				),
			),
			'subcategories' => array(
				'mercado' => array(
					'name'        => 'Mercado de trabalho',
					'description' => 'CLT, MEI e escala',
					'keywords'    => array(
						'include' => array( 'emprego', 'carreira', 'clt', 'mei', '6x1' ),
						'exclude' => array(),
					),
					'feeds'       => array(
				array(
					'title' => 'G1 Trabalho e Carreira',
					'url'   => 'https://g1.globo.com/rss/g1/trabalho-e-carreira/',
					'tier'  => 1,
					'keywords' => array(
						'include' => array( 'emprego', 'carreira', 'clt', 'mei', '6x1' ),
						'exclude' => array(),
					),
				),
					),
				),
			),
		),
		'empreendedorismo' => array(
			'name'        => 'Empreendedorismo',
			'description' => 'PME, startups e gestão pessoal de negócios.',
			'branding'    => array(
				'brand_name'      => 'PME',
				'primary_color'   => '#5B21B6',
				'accent_color'    => '#A78BFA',
				'secondary_color' => '#4C1D95',
				'header_variant'  => 'editoria',
			),
			'keywords'    => array(
				'include' => array(),
				'exclude' => array(),
			),
			'feeds'       => array(
				array(
					'title' => 'G1 Empreendedorismo',
					'url'   => 'https://g1.globo.com/rss/g1/empreendedorismo/',
					'tier'  => 1,
				),
			),
			'subcategories' => array(
				'pme' => array(
					'name'        => 'PME',
					'description' => 'pequenos negócios',
					'keywords'    => array(
						'include' => array( 'empreendedor', 'startup', 'pme', 'negócio', 'negocio' ),
						'exclude' => array(),
					),
					'feeds'       => array(
				array(
					'title' => 'G1 Empreendedorismo',
					'url'   => 'https://g1.globo.com/rss/g1/empreendedorismo/',
					'tier'  => 1,
					'keywords' => array(
						'include' => array( 'empreendedor', 'startup', 'pme', 'negócio', 'negocio' ),
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
