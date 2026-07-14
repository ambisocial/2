<?php
/**
 * Taxonomia — Estrato Política (schema v2). P1 G1/Exame/IstoÉ.
 *
 * @return array<string, mixed>
 */
return array(
	'schema_version' => 2,
	'portal_id'      => 'estrato-politica',
	'menu_order'     => array(
		'poder', 'brasil', 'eleicoes',
	),
	'column_order'   => array(),
	'index_order'    => array(
		'poder', 'brasil', 'eleicoes',
	),
	'legacy_merge'   => array(),
	'legacy_noindex' => array(),
	'defaults'       => array(
		'branding' => array(
			'primary_color'   => '#1A365D',
			'accent_color'    => '#E53E3E',
			'secondary_color' => '#2A4365',
			'header_variant'  => 'editoria',
		),
		'keywords' => array(
			'include'  => array(),
			'exclude'  => array(),
			'match_in' => array( 'title', 'excerpt', 'content' ),
		),
	),
	'categories'     => array(
		'poder' => array(
			'name'        => 'Poder',
			'description' => 'Governo, Congresso, STF e bastidores de Brasília.',
			'branding'    => array(
				'brand_name'      => 'Poder',
				'primary_color'   => '#1A365D',
				'accent_color'    => '#E53E3E',
				'secondary_color' => '#2A4365',
				'header_variant'  => 'editoria',
			),
			'keywords'    => array(
				'include' => array(),
				'exclude' => array(),
			),
			'feeds'       => array(
				array(
					'title' => 'G1 Política',
					'url'   => 'https://g1.globo.com/rss/g1/politica/',
					'tier'  => 1,
				),
				array(
					'title' => 'Folha Poder',
					'url'   => 'https://feeds.folha.uol.com.br/poder/rss091.xml',
					'tier'  => 1,
				),
			),
			'subcategories' => array(
				'governo' => array(
					'name'        => 'Governo e Planalto',
					'description' => 'presidência, planalto, ministério',
					'keywords'    => array(
						'include' => array( 'governo', 'planalto', 'ministro', 'presidente' ),
						'exclude' => array(),
					),
					'feeds'       => array(
				array(
					'title' => 'G1 Política',
					'url'   => 'https://g1.globo.com/rss/g1/politica/',
					'tier'  => 1,
					'keywords' => array(
						'include' => array( 'governo', 'planalto', 'ministro', 'presidente' ),
						'exclude' => array(),
					),
				),
					),
				),
				'congresso' => array(
					'name'        => 'Congresso',
					'description' => 'câmara, senado e pacotes legislativos',
					'keywords'    => array(
						'include' => array( 'congresso', 'câmara', 'camara', 'senado', 'deputado', 'pec' ),
						'exclude' => array(),
					),
					'feeds'       => array(
				array(
					'title' => 'G1 Política',
					'url'   => 'https://g1.globo.com/rss/g1/politica/',
					'tier'  => 1,
					'keywords' => array(
						'include' => array( 'congresso', 'câmara', 'camara', 'senado', 'deputado', 'pec' ),
						'exclude' => array(),
					),
				),
					),
				),
			),
		),
		'brasil' => array(
			'name'        => 'Brasil',
			'description' => 'Agenda nacional, estados e políticas públicas.',
			'branding'    => array(
				'brand_name'      => 'Brasil',
				'primary_color'   => '#1A365D',
				'accent_color'    => '#E53E3E',
				'secondary_color' => '#2A4365',
				'header_variant'  => 'editoria',
			),
			'keywords'    => array(
				'include' => array(),
				'exclude' => array(),
			),
			'feeds'       => array(
				array(
					'title' => 'G1 Brasil',
					'url'   => 'https://g1.globo.com/rss/g1/brasil/',
					'tier'  => 1,
				),
			),
			'subcategories' => array(
				'politicas-publicas' => array(
					'name'        => 'Políticas públicas',
					'description' => 'saúde pública, segurança e infraestrutura',
					'keywords'    => array(
						'include' => array( 'política pública', 'politica publica', 'segurança', 'seguranca' ),
						'exclude' => array(),
					),
					'feeds'       => array(
				array(
					'title' => 'G1 Brasil',
					'url'   => 'https://g1.globo.com/rss/g1/brasil/',
					'tier'  => 1,
					'keywords' => array(
						'include' => array( 'política pública', 'politica publica', 'segurança', 'seguranca' ),
						'exclude' => array(),
					),
				),
					),
				),
			),
		),
		'eleicoes' => array(
			'name'        => 'Eleições',
			'description' => 'Campanhas, pesquisas e calendário eleitoral.',
			'branding'    => array(
				'brand_name'      => 'Urnas',
				'primary_color'   => '#1A365D',
				'accent_color'    => '#E53E3E',
				'secondary_color' => '#2A4365',
				'header_variant'  => 'editoria',
			),
			'keywords'    => array(
				'include' => array(),
				'exclude' => array(),
			),
			'feeds'       => array(
				array(
					'title' => 'Exame (filtro eleições)',
					'url'   => 'https://exame.com/feed/',
					'tier'  => 2,
					'keywords' => array(
						'include' => array( 'eleição', 'eleicao', 'eleições', 'eleicoes', 'candidato', 'tse', 'pesquisa' ),
						'exclude' => array(),
					),
				),
			),
			'subcategories' => array(
				'campanha' => array(
					'name'        => 'Campanha',
					'description' => 'candidatos e debates',
					'keywords'    => array(
						'include' => array( 'candidato', 'campanha', 'debate', 'urna' ),
						'exclude' => array(),
					),
					'feeds'       => array(
				array(
					'title' => 'Exame (filtro eleições)',
					'url'   => 'https://exame.com/feed/',
					'tier'  => 2,
					'keywords' => array(
						'include' => array( 'candidato', 'campanha', 'debate', 'urna' ),
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
