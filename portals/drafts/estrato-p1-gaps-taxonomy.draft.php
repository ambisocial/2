<?php
/**
 * DRAFT P1 — Saúde, Educação/Carreira, Tecnologia, Carros.
 * Gaps G1/Exame/IstoÉ com RSS verificados (HTTP 200).
 *
 * @return array<string, mixed>
 */
return array(
	'schema_version' => 2,
	'status'         => 'draft_p1_bundle',
	'portals'        => array(
		'estrato-saude' => array(
			'menu_order' => array( 'saude' ),
			'categories' => array(
				'saude' => array(
					'name'  => 'Saúde',
					'feeds' => array(
						array( 'title' => 'G1 Ciência e Saúde', 'url' => 'https://g1.globo.com/rss/g1/ciencia-e-saude/', 'tier' => 1 ),
						array( 'title' => 'Veja Saúde', 'url' => 'https://vejasp.abril.com.br/feed/', 'tier' => 3 ),
					),
				),
			),
		),
		'estrato-educacao' => array(
			'menu_order' => array( 'educacao', 'carreira' ),
			'categories' => array(
				'educacao' => array(
					'name'  => 'Educação',
					'feeds' => array(
						array( 'title' => 'G1 Educação', 'url' => 'https://g1.globo.com/rss/g1/educacao/', 'tier' => 1 ),
					),
				),
			),
		),
		'estrato-tech' => array(
			'menu_order' => array( 'tecnologia' ),
			'categories' => array(
				'tecnologia' => array(
					'name'  => 'Tecnologia',
					'feeds' => array(
						array( 'title' => 'G1 Tecnologia', 'url' => 'https://g1.globo.com/rss/g1/tecnologia/', 'tier' => 1 ),
						array( 'title' => 'Tecnoblog', 'url' => 'https://tecnoblog.net/feed/', 'tier' => 1 ),
					),
				),
			),
		),
		'estrato-carros' => array(
			'menu_order' => array( 'carros' ),
			'categories' => array(
				'carros' => array(
					'name'  => 'Carros',
					'feeds' => array(
						array( 'title' => 'G1 Carros', 'url' => 'https://g1.globo.com/rss/g1/carros/', 'tier' => 1 ),
					),
				),
			),
		),
	),
);
