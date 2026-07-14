<?php
/**
 * DRAFT P1 — Política (ainda não provisionado como portal).
 * Fontes RSS validadas; ativar quando criar portal/editoria Política.
 *
 * @return array<string, mixed>
 */
return array(
	'schema_version' => 2,
	'portal_id'      => 'estrato-politica',
	'status'         => 'draft_p1',
	'menu_order'     => array( 'brasil-politica', 'congresso', 'eleicoes' ),
	'categories'     => array(
		'brasil-politica' => array(
			'name'  => 'Política',
			'feeds' => array(
				array( 'title' => 'G1 Política', 'url' => 'https://g1.globo.com/rss/g1/politica/', 'tier' => 1 ),
				array( 'title' => 'Folha Poder', 'url' => 'https://feeds.folha.uol.com.br/poder/rss091.xml', 'tier' => 1 ),
				array( 'title' => 'Exame (filtro política)', 'url' => 'https://exame.com/feed/', 'tier' => 2 ),
			),
		),
	),
);
