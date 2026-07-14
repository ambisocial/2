<?php
/**
 * Taxonomia — ESG (schema v2). Frente ex-Sustain.
 *
 * Clima, transição energética, impacto e economia responsável.
 *
 * @return array<string, mixed>
 */
return array(
	'schema_version' => 2,
	'portal_id'      => 'estrato-esg',
	'menu_order'     => array(
		'clima-ambiente',
		'transicao-energia',
		'impacto-negocios',
	),
	'column_order'   => array(),
	'index_order'    => array(
		'clima-ambiente',
		'transicao-energia',
		'impacto-negocios',
	),
	'legacy_merge'   => array(
		'economia-alternativa' => 'impacto-negocios',
	),
	'legacy_noindex' => array(
		'economia-alternativa',
	),
	'defaults'       => array(
		'branding' => array(
			'primary_color'   => '#0B3D2E',
			'accent_color'    => '#2EC4B6',
			'secondary_color' => '#145A46',
			'header_variant'  => 'editoria',
		),
		'keywords' => array(
			'include'  => array(),
			'exclude'  => array( 'ibovespa', 'celebridade', 'futebol' ),
			'match_in' => array( 'title', 'excerpt', 'content' ),
		),
	),
	'categories'     => array(
		'clima-ambiente'     => array(
			'name'        => 'Clima & Ambiente',
			'description' => 'Crise climática, conservação, Amazônia, água e biodiversidade.',
			'branding'    => array(
				'brand_name'      => 'Clima',
				'primary_color'   => '#0B3D2E',
				'accent_color'    => '#2EC4B6',
				'secondary_color' => '#145A46',
				'header_variant'  => 'editoria',
			),
			'feeds'       => array(
				array( 'title' => 'G1 Meio Ambiente', 'url' => 'https://g1.globo.com/rss/g1/meio-ambiente/', 'tier' => 1 ),
				array( 'title' => 'CicloVivo', 'url' => 'https://ciclovivo.com.br/feed/', 'tier' => 1 ),
				array( 'title' => 'Pensamento Verde', 'url' => 'https://www.pensamentoverde.com.br/feed/', 'tier' => 2 ),
			),
			'subcategories' => array(
				'crise-climatica' => array(
					'name'        => 'Crise climática',
					'description' => 'Aquecimento, extremos, COP e política climática.',
					'keywords'    => array(
						'include' => array( 'clima', 'climática', 'climatica', 'aquecimento', 'cop', 'carbono', 'emissões', 'emissoes' ),
						'exclude' => array(),
					),
					'feeds'       => array(
						array( 'title' => 'G1 Meio Ambiente', 'url' => 'https://g1.globo.com/rss/g1/meio-ambiente/', 'tier' => 1 ),
						array( 'title' => 'Capital Reset', 'url' => 'https://www.capitalreset.com/feed/', 'tier' => 1 ),
					),
				),
				'conservacao'     => array(
					'name'        => 'Conservação',
					'description' => 'Florestas, biomas, água e biodiversidade.',
					'keywords'    => array(
						'include' => array( 'amazônia', 'amazonia', 'floresta', 'pantanal', 'biodiversidade', 'conservação', 'conservacao', 'desmatamento' ),
						'exclude' => array(),
					),
					'feeds'       => array(
						array( 'title' => 'CicloVivo', 'url' => 'https://ciclovivo.com.br/feed/', 'tier' => 1 ),
						array( 'title' => 'Pensamento Verde', 'url' => 'https://www.pensamentoverde.com.br/feed/', 'tier' => 2 ),
					),
				),
			),
		),
		'transicao-energia'  => array(
			'name'        => 'Transição energética',
			'description' => 'Renováveis, mobilidade elétrica, hidrogênio e descarbonização industrial.',
			'branding'    => array(
				'brand_name'      => 'Energia',
				'primary_color'   => '#1D3557',
				'accent_color'    => '#F4A261',
				'secondary_color' => '#457B9D',
				'header_variant'  => 'editoria',
			),
			'feeds'       => array(
				array( 'title' => 'Capital Reset', 'url' => 'https://www.capitalreset.com/feed/', 'tier' => 1 ),
				array( 'title' => 'Página22', 'url' => 'https://www.pagina22.com.br/feed/', 'tier' => 1 ),
				array(
					'title'    => 'Exame (filtro ESG/energia)',
					'url'      => 'https://exame.com/feed/',
					'tier'     => 2,
					'keywords' => array(
						'include' => array( 'esg', 'energia', 'renovável', 'renovavel', 'elétrica', 'eletrica', 'hidrogênio', 'hidrogenio', 'solar', 'eólica', 'eolica' ),
						'exclude' => array( 'copa', 'futebol', 'celebridade' ),
					),
				),
			),
			'subcategories' => array(
				'renovaveis' => array(
					'name'        => 'Renováveis',
					'description' => 'Solar, eólica, biomassa e armazenamento.',
					'keywords'    => array(
						'include' => array( 'solar', 'eólica', 'eolica', 'renovável', 'renovavel', 'bateria', 'biomassa' ),
						'exclude' => array(),
					),
					'feeds'       => array(
						array( 'title' => 'Capital Reset', 'url' => 'https://www.capitalreset.com/feed/', 'tier' => 1 ),
						array( 'title' => 'CicloVivo', 'url' => 'https://ciclovivo.com.br/feed/', 'tier' => 2 ),
					),
				),
				'mobilidade' => array(
					'name'        => 'Mobilidade limpa',
					'description' => 'Elétricos, biometano, frota e infraestrutura.',
					'keywords'    => array(
						'include' => array( 'elétrico', 'eletrico', 'biometano', 'mobilidade', 'frota', 'ônibus elétrico', 'onibus eletrico' ),
						'exclude' => array(),
					),
					'feeds'       => array(
						array( 'title' => 'Capital Reset', 'url' => 'https://www.capitalreset.com/feed/', 'tier' => 1 ),
						array( 'title' => 'Página22', 'url' => 'https://www.pagina22.com.br/feed/', 'tier' => 2 ),
					),
				),
			),
		),
		'impacto-negocios'   => array(
			'name'        => 'Impacto & negócios',
			'description' => 'ESG corporativo, finanças verdes, diversidade e economia de impacto.',
			'branding'    => array(
				'brand_name'      => 'Impacto',
				'primary_color'   => '#3D348B',
				'accent_color'    => '#7678ED',
				'secondary_color' => '#2C2A6E',
				'header_variant'  => 'editoria',
			),
			'feeds'       => array(
				array( 'title' => 'Página22', 'url' => 'https://www.pagina22.com.br/feed/', 'tier' => 1 ),
				array( 'title' => 'DigiLabour', 'url' => 'https://digilabour.com.br/feed/', 'tier' => 1 ),
				array( 'title' => 'EITA', 'url' => 'https://eita.coop.br/feed/', 'tier' => 2 ),
			),
			'subcategories' => array(
				'financas-verdes' => array(
					'name'        => 'Finanças verdes',
					'description' => 'Crédito climático, green bonds e risco ESG.',
					'keywords'    => array(
						'include' => array( 'esg', 'green bond', 'finanças verdes', 'financas verdes', 'crédito climático', 'credito climatico', 'impacto' ),
						'exclude' => array(),
					),
					'feeds'       => array(
						array( 'title' => 'Capital Reset', 'url' => 'https://www.capitalreset.com/feed/', 'tier' => 1 ),
						array( 'title' => 'Página22', 'url' => 'https://www.pagina22.com.br/feed/', 'tier' => 1 ),
					),
				),
				'economia-impacto' => array(
					'name'        => 'Economia de impacto',
					'description' => 'Cooperativismo, negócios sociais e plataforma justa.',
					'keywords'    => array(
						'include' => array( 'cooperativa', 'impacto', 'negócio social', 'negocio social', 'economia solidária', 'economia solidaria' ),
						'exclude' => array(),
					),
					'feeds'       => array(
						array( 'title' => 'DigiLabour', 'url' => 'https://digilabour.com.br/feed/', 'tier' => 1 ),
						array( 'title' => 'EITA', 'url' => 'https://eita.coop.br/feed/', 'tier' => 1 ),
						array( 'title' => 'Quintessa', 'url' => 'https://blog.quintessa.org.br/feed/', 'tier' => 2 ),
					),
				),
			),
		),
	),
);
