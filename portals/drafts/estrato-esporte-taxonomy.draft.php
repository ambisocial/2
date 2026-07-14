<?php
/**
 * DRAFT P1 — Esporte (ge.globo / Exame Esporte / IstoÉ Esportes).
 *
 * @return array<string, mixed>
 */
return array(
	'schema_version' => 2,
	'portal_id'      => 'estrato-esporte',
	'status'         => 'draft_p1',
	'menu_order'     => array( 'futebol', 'olimpiadas', 'outros-esportes' ),
	'categories'     => array(
		'futebol' => array(
			'name'  => 'Futebol',
			'feeds' => array(
				array( 'title' => 'GE Futebol', 'url' => 'https://ge.globo.com/dynamo/futebol/rss2.xml', 'tier' => 1 ),
				array( 'title' => 'Lance!', 'url' => 'https://www.lance.com.br/rss.html', 'tier' => 2 ),
			),
		),
	),
);
