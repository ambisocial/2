<?php
/**
 * Taxonomia — Ciência, tecnologia e futuro (schema v2).
 *
 * 3 editorias × 6 nichos (subcategorias) — bullets 11–16 do segmento science.
 *
 * @return array<string, mixed>
 */
return array(
	'schema_version' => 2,
	'portal_id'      => 'estrato-science',
	'menu_order'     => array(
		'neuro-biologia',
		'bio-fabricacao',
		'ia-seguranca',
	),
	'column_order'   => array(),
	'index_order'    => array(
		'neuro-biologia',
		'bio-fabricacao',
		'ia-seguranca',
	),
	'legacy_merge'   => array(),
	'legacy_noindex' => array(),
	'defaults'       => array(
		'branding' => array(
			'primary_color'   => '#0B132B',
			'accent_color'    => '#5BC0BE',
			'secondary_color' => '#1C2541',
			'header_variant'  => 'editoria',
		),
		'keywords' => array(
			'include'  => array(),
			'exclude'  => array(),
			'match_in' => array( 'title', 'excerpt', 'content' ),
		),
	),
	'categories'     => array(
		'neuro-biologia'  => array(
			'name'        => 'Neuro & Biologia',
			'description' => 'Neurociência do sono, ritmo circadiano e microbioma intestinal — divulgação científica brasileira independente.',
			'branding'    => array(
				'brand_name'      => 'Neuro',
				'primary_color'   => '#1B263B',
				'accent_color'    => '#778DA9',
				'secondary_color' => '#415A77',
				'header_variant'  => 'editoria',
			),
			'keywords'    => array(
				'include' => array(),
				'exclude' => array( 'ibovespa', 'selic', 'bitcoin' ),
			),
			'feeds'       => array(
				array(
					'title'    => 'Instituto do Cérebro (UFRN)',
					'url'      => 'https://neuro.ufrn.br/blog/index.php/feed/',
					'tier'     => 1,
					'verified' => true,
				),
			),
			'subcategories' => array(
				'sono-neurociencia'   => array(
					'name'        => 'Neurociência do sono',
					'description' => 'Sono, sonhos, ritmo circadiano, higiene do sono e neurociência aplicada no Brasil.',
					'keywords'    => array(
						'include' => array(
							'sono',
							'ritmo circadiano',
							'higiene do sono',
							'insônia',
							'insonia',
							'neurociência do sono',
							'neurociencia do sono',
							'melatonina',
							'apneia do sono',
							'sonhos lúcidos',
							'sonhos lucidos',
						),
						'exclude' => array(),
					),
					'feeds'       => array(
						array(
							'title'    => 'Instituto do Cérebro (UFRN)',
							'url'      => 'https://neuro.ufrn.br/blog/index.php/feed/',
							'tier'     => 1,
							'verified' => true,
						),
						array(
							'title'    => 'Lab Sonhos (UFRN)',
							'url'      => 'https://neuro.ufrn.br/labsonhos/index.php/feed/',
							'tier'     => 1,
							'verified' => true,
						),
						array( 'title' => 'ABS — Blog do Sono', 'url' => 'https://absono.com.br/feed/', 'tier' => 1 ),
					),
				),
				'microbioma-digestivo' => array(
					'name'        => 'Microbioma & saúde digestiva',
					'description' => 'Microbiota intestinal, disbiose, eixo intestino-cérebro e gastroenterologia baseada em evidências.',
					'keywords'    => array(
						'include' => array(
							'microbiota intestinal',
							'microbioma',
							'disbiose',
							'eixo intestino-cérebro',
							'eixo intestino-cerebro',
							'probióticos',
							'probioticos',
							'saúde digestiva',
							'saude digestiva',
							'SIBO',
							'gastroenterologia',
						),
						'exclude' => array(),
					),
					'feeds'       => array(
						array( 'title' => 'Dra. Flávia Solano', 'url' => 'https://flaviasolano.com.br/feed/', 'tier' => 1 ),
						array( 'title' => 'Nutritotal', 'url' => 'https://nutritotal.com.br/feed/', 'tier' => 2 ),
					),
				),
			),
		),
		'bio-fabricacao'  => array(
			'name'        => 'Bio & Fabricação',
			'description' => 'Biohacking, longevidade, impressão 3D e cultura maker — ecossistema brasileiro de otimização e DIY.',
			'branding'    => array(
				'brand_name'      => 'Bio',
				'primary_color'   => '#004643',
				'accent_color'    => '#FAEAB1',
				'secondary_color' => '#001C1D',
				'header_variant'  => 'editoria',
			),
			'keywords'    => array(
				'include' => array(),
				'exclude' => array(),
			),
			'feeds'       => array(
				array( 'title' => 'Lifespan', 'url' => 'https://lifespan.com.br/feed/', 'tier' => 1 ),
			),
			'subcategories' => array(
				'biohacking-longevidade' => array(
					'name'        => 'Biohacking & longevidade',
					'description' => 'Longevidade, medicina de precisão, wearables, jejum intermitente e otimização humana no Brasil.',
					'keywords'    => array(
						'include' => array(
							'biohacking',
							'longevidade',
							'jejum intermitente',
							'suplementação',
							'suplementacao',
							'wearables',
							'HRV',
							'medicina de precisão',
							'medicina de precisao',
							'NAD+',
							'hormese',
						),
						'exclude' => array(),
					),
					'feeds'       => array(
						array(
							'title'    => 'Renato Braga Podcast',
							'url'      => 'https://anchor.fm/s/d175e64/podcast/rss',
							'tier'     => 1,
							'verified' => true,
						),
						array( 'title' => 'Lifespan', 'url' => 'https://lifespan.com.br/feed/', 'tier' => 1 ),
						array( 'title' => 'Blog Biohacking Brasil', 'url' => 'https://blog.biohackingbrasil.com.br/feed/', 'tier' => 2 ),
					),
				),
				'maker-impressao-3d'     => array(
					'name'        => 'Impressão 3D & cultura maker',
					'description' => 'Impressão 3D, Arduino, robótica, RepRap e comunidades maker brasileiras.',
					'keywords'    => array(
						'include' => array(
							'impressão 3D',
							'impressao 3D',
							'cultura maker',
							'filamento',
							'RepRap',
							'impressora 3D',
							'impressora 3d',
							'Arduino',
							'DIY',
							'open source hardware',
						),
						'exclude' => array(),
					),
					'feeds'       => array(
						array( 'title' => 'Fazedores', 'url' => 'https://blog.fazedores.com/feed/', 'tier' => 1 ),
					),
				),
			),
		),
		'ia-seguranca'    => array(
			'name'        => 'IA & Segurança',
			'description' => 'Inteligência artificial aplicada a profissões criativas e cibersegurança ofensiva — newsletters, podcasts e writeups BR.',
			'branding'    => array(
				'brand_name'      => 'Futuro',
				'primary_color'   => '#240046',
				'accent_color'    => '#9D4EDD',
				'secondary_color' => '#3C096C',
				'header_variant'  => 'editoria',
			),
			'keywords'    => array(
				'include' => array(),
				'exclude' => array(),
			),
			'feeds'       => array(
				array(
					'title'    => 'Cartas de um Humano+',
					'url'      => 'https://caioia.substack.com/feed',
					'tier'     => 1,
					'verified' => true,
				),
			),
			'subcategories' => array(
				'ia-criativa'        => array(
					'name'        => 'IA criativa',
					'description' => 'IA generativa aplicada a design, escrita, criatividade e processo criativo — newsletters independentes em PT-BR.',
					'keywords'    => array(
						'include' => array(
							'IA generativa',
							'inteligência artificial generativa',
							'inteligencia artificial generativa',
							'criatividade',
							'design e IA',
							'escrita com IA',
							'processo criativo',
							'ChatGPT',
							'Midjourney',
							'prompt engineering',
						),
						'exclude' => array(),
					),
					'feeds'       => array(
						array(
							'title'    => 'Cartas de um Humano+',
							'url'      => 'https://caioia.substack.com/feed',
							'tier'     => 1,
							'verified' => true,
						),
						array( 'title' => 'Garimpo Criativo', 'url' => 'https://garimpocriativo.substack.com/feed', 'tier' => 1 ),
						array( 'title' => 'makers gonna make', 'url' => 'https://makersgonnamake.substack.com/feed', 'tier' => 1 ),
						array( 'title' => 'Escrita Mestra', 'url' => 'https://escritamestranews.substack.com/feed', 'tier' => 1 ),
					),
				),
				'ciberseguranca-ctf' => array(
					'name'        => 'Cibersegurança ofensiva & CTF',
					'description' => 'Pentest, red team, engenharia reversa, CTF e segurança ofensiva — fontes independentes brasileiras.',
					'keywords'    => array(
						'include' => array(
							'pentest',
							'red team',
							'CTF',
							'capture the flag',
							'engenharia reversa',
							'segurança ofensiva',
							'seguranca ofensiva',
							'OSCP',
							'writeup',
							'malware analysis',
						),
						'exclude' => array(),
					),
					'feeds'       => array(
						array(
							'title'    => 'hexkaster',
							'url'      => 'https://hexkaster.com/feed.xml',
							'tier'     => 1,
							'verified' => true,
						),
						array(
							'title'    => 'Papo Binário',
							'url'      => 'https://anchor.fm/s/42c60d58/podcast/rss',
							'tier'     => 1,
							'verified' => true,
						),
					),
				),
			),
		),
	),
	'columns'        => array(),
);
