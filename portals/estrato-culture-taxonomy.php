<?php
/**
 * Taxonomia — Entretenimento, cultura pop e narrativas de nicho (schema v2).
 *
 * 2 editorias × 4 nichos (subcategorias) — bullets 21–24.
 *
 * @return array<string, mixed>
 */
return array(
	'schema_version' => 2,
	'portal_id'      => 'estrato-culture',
	'menu_order'     => array(
		'jogos-imaginacao',
		'narrativas-som',
	),
	'column_order'   => array(),
	'index_order'    => array(
		'jogos-imaginacao',
		'narrativas-som',
	),
	'legacy_merge'   => array(),
	'legacy_noindex' => array(),
	'defaults'       => array(
		'branding' => array(
			'primary_color'   => '#2D1B69',
			'accent_color'    => '#FF6B9D',
			'secondary_color' => '#1A0F3D',
			'header_variant'  => 'editoria',
		),
		'keywords' => array(
			'include'  => array(),
			'exclude'  => array(),
			'match_in' => array( 'title', 'excerpt', 'content' ),
		),
	),
	'categories'     => array(
		'jogos-imaginacao' => array(
			'name'        => 'Jogos & Imaginação',
			'description' => 'RPG de mesa OSR, board games modernos e eurogames — blogosfera e podcasts independentes brasileiros.',
			'branding'    => array(
				'brand_name'      => 'Jogos',
				'primary_color'   => '#5C4D7D',
				'accent_color'    => '#F4A261',
				'secondary_color' => '#3D3557',
				'header_variant'  => 'editoria',
			),
			'keywords'    => array(
				'include' => array(),
				'exclude' => array( 'ibovespa', 'selic' ),
			),
			'feeds'       => array(
				array(
					'title'    => 'D30 RPG (podcast)',
					'url'      => 'https://d30rpg.com.br/category/podcast/feed/',
					'tier'     => 1,
					'verified' => true,
				),
			),
			'subcategories' => array(
				'rpg-mesa-osr'      => array(
					'name'        => 'RPG de mesa & OSR',
					'description' => 'RPG old-school, zines, cenários OSR e blogosfera brasileira de mestres e jogadores.',
					'keywords'    => array(
						'include' => array(
							'OSR Brasil',
							'RPG old school',
							'zine RPG',
							'Old Dragon',
							'mestre masmorra',
							'RPG de mesa',
							'dungeon crawl',
							'cenário OSR',
							'cenario OSR',
							'Brainstorm RPG',
						),
						'exclude' => array(),
					),
					'feeds'       => array(
						array(
							'title'    => 'OSR Brasil',
							'url'      => 'https://osrbrasil.wordpress.com/feed/',
							'tier'     => 1,
							'verified' => true,
						),
						array(
							'title'    => 'Teratológica / Brainstorm RPG',
							'url'      => 'https://teratologica.blogspot.com/feeds/posts/default?alt=rss',
							'tier'     => 1,
							'verified' => true,
						),
						array(
							'title'    => 'D30 RPG (podcast)',
							'url'      => 'https://d30rpg.com.br/category/podcast/feed/',
							'tier'     => 1,
							'verified' => true,
						),
						array(
							'title' => 'Dados Enfeitiçados & Lápis Afiados',
							'url'   => 'https://dadosenfeiticadoslapisafiados.blogspot.com/feeds/posts/default?alt=rss',
							'tier'  => 2,
						),
						array(
							'title' => 'Guilda dos Blogueiros Old School',
							'url'   => 'https://guildadosblogueirosoldschool.blogspot.com/feeds/posts/default?alt=rss',
							'tier'  => 3,
						),
					),
				),
				'board-games-euro'  => array(
					'name'        => 'Board games & eurogames',
					'description' => 'Jogos de tabuleiro modernos, eurogames, resenhas e podcasts da cena brasileira.',
					'keywords'    => array(
						'include' => array(
							'jogos de tabuleiro',
							'eurogame brasileiro',
							'boardgame nacional',
							'Catarse jogo',
							'resenha jogo de tabuleiro',
							'meeple',
							'Ludopedia',
							'jogo de estratégia',
							'jogo de estrategia',
						),
						'exclude' => array(),
					),
					'feeds'       => array(
						array(
							'title'    => 'Ludopedia — Meeple Maniacs',
							'url'      => 'https://ludopedia-podcasts.nyc3.digitaloceanspaces.com/feed/podcast_2.xml',
							'tier'     => 1,
							'verified' => true,
						),
						array(
							'title'    => 'Ludopedia — BGG II Podcast',
							'url'      => 'https://ludopedia-podcasts.nyc3.digitaloceanspaces.com/feed/podcast_29.xml',
							'tier'     => 1,
							'verified' => true,
						),
						array( 'title' => 'Red Meeple Blog', 'url' => 'https://redmeepleblog.wordpress.com/feed/', 'tier' => 2 ),
						array( 'title' => 'Meeple Divino', 'url' => 'https://meepledivino.blog.br/feed/', 'tier' => 2 ),
					),
				),
			),
		),
		'narrativas-som'   => array(
			'name'        => 'Narrativas & Som',
			'description' => 'Ficção científica em português, música independente de nicho e cultura pop editorial.',
			'branding'    => array(
				'brand_name'      => 'Pop',
				'primary_color'   => '#7B2CBF',
				'accent_color'    => '#E0AAFF',
				'secondary_color' => '#5A189A',
				'header_variant'  => 'editoria',
			),
			'keywords'    => array(
				'include' => array(),
				'exclude' => array(),
			),
			'feeds'       => array(
				array( 'title' => 'Rebobinados', 'url' => 'https://rebobinados.com.br/feed/', 'tier' => 1 ),
			),
			'subcategories' => array(
				'fc-portuguesa'     => array(
					'name'        => 'Ficção científica em português',
					'description' => 'FC e ficção especulativa brasileira — revistas, podcasts, newsletters e ensaios críticos.',
					'keywords'    => array(
						'include' => array(
							'ficção científica brasileira',
							'ficcao cientifica brasileira',
							'ficção especulativa',
							'ficcao especulativa',
							'conto fantástico',
							'conto fantastico',
							'literatura fantástica nacional',
							'literatura fantastica nacional',
							'revista FC',
							'Pindorama',
						),
						'exclude' => array(),
					),
					'feeds'       => array(
						array( 'title' => 'TerraTreva (Substack)', 'url' => 'https://terratreva.substack.com/feed', 'tier' => 1 ),
						array(
							'title'    => 'Pindorama (Leitor Cabuloso)',
							'url'      => 'https://leitorcabuloso.com.br/feed/pindorama/',
							'tier'     => 1,
							'verified' => true,
						),
						array( 'title' => 'Ficção Científica Brasileira', 'url' => 'https://ficcaocientificabrasileira.wordpress.com/feed/', 'tier' => 1 ),
						array( 'title' => 'Fantástika 451', 'url' => 'https://fantastika451.wordpress.com/feed/', 'tier' => 2 ),
						array( 'title' => 'Revista Trasgo (arquivo)', 'url' => 'https://trasgo.com.br/feed/', 'tier' => 3 ),
					),
				),
				'musica-nicho'      => array(
					'name'        => 'Música independente de nicho',
					'description' => 'Shoegaze, dream pop, vaporwave, synthwave e post-rock instrumental brasileiro.',
					'keywords'    => array(
						'include' => array(
							'shoegaze brasileiro',
							'dream pop nacional',
							'vaporwave Brasil',
							'synthwave brasileiro',
							'post-rock instrumental Brasil',
							'netlabel brasileiro',
							'Bandcamp Brasil',
						),
						'exclude' => array(),
					),
					'feeds'       => array(
						array( 'title' => 'Rebobinados', 'url' => 'https://rebobinados.com.br/feed/', 'tier' => 1 ),
						array( 'title' => 'Hits Perdidos', 'url' => 'https://hitsperdidos.com/feed/', 'tier' => 1 ),
						array(
							'title' => 'Neon Retro Records (Open RSS / Bandcamp)',
							'url'   => 'https://openrss.org/neonretrorecords.bandcamp.com/music',
							'tier'  => 3,
						),
						array(
							'title' => 'The Blog That Celebrates Itself (arquivo)',
							'url'   => 'https://theblogthatcelebratesitself.blogspot.com/feeds/posts/default?alt=rss',
							'tier'  => 3,
						),
					),
				),
			),
		),
	),
	'columns'        => array(),
);
