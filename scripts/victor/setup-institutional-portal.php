<?php
/**
 * Sprint 3 genérico — páginas institucionais por portal.
 *
 * Uso: ESTRATO_PORTAL=estrato-mind wp eval-file setup-institutional-portal.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$portal = getenv( 'ESTRATO_PORTAL' ) ?: 'estrato-finance';
$title  = get_bloginfo( 'name' );
$domain = wp_parse_url( home_url(), PHP_URL_HOST );

/**
 * @param string $slug
 * @param string $page_title
 * @param string $content
 * @return int
 */
function estrato_portal_upsert_page( $slug, $page_title, $content ) {
	$existing = get_page_by_path( $slug, OBJECT, 'page' );
	$data     = array(
		'post_title'   => $page_title,
		'post_name'    => $slug,
		'post_content' => $content,
		'post_status'  => 'publish',
		'post_type'    => 'page',
	);
	if ( $existing ) {
		$data['ID'] = $existing->ID;
		$id         = wp_update_post( $data, true );
	} else {
		$id = wp_insert_post( $data, true );
	}
	if ( is_wp_error( $id ) ) {
		WP_CLI::warning( "$slug: " . $id->get_error_message() );
		return 0;
	}
	WP_CLI::log( "/$slug/ → #$id" );
	return (int) $id;
}

$sobre = <<<HTML
<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Sobre o {$title}</h1>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>O <strong>{$title}</strong> ({$domain}) é um portal editorial do ecossistema Estrato, com curadoria de fontes verificáveis e classificação por editorias e nichos.</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Missão</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Organizar conhecimento e notícias de nicho em silos claros, com transparência de fontes e atualização contínua via RSS e pipeline editorial.</p>
<!-- /wp:paragraph -->
HTML;

$editorial = <<<HTML
<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Política editorial</h1>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>O {$title} agrega e curadoria conteúdo de fontes públicas. Cada matéria indica a origem. Correções são registradas nesta página.</p>
<!-- /wp:paragraph -->
<!-- wp:list -->
<ul class="wp-block-list"><li>Priorizamos fontes primárias e veículos com política editorial explícita</li><li>Classificamos por editoria e subcategoria via taxonomia v2</li><li>Não publicamos conteúdo sem atribuição de fonte</li></ul>
<!-- /wp:list -->
HTML;

$contato = <<<HTML
<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Contato</h1>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Redação {$title}: <a href="mailto:tpb@tbj.com.br">tpb@tbj.com.br</a></p>
<!-- /wp:paragraph -->
HTML;

$privacidade = <<<HTML
<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Política de privacidade</h1>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>O {$title} respeita a LGPD. Coletamos apenas dados necessários para analytics e formulários de contato. Não vendemos dados pessoais.</p>
<!-- /wp:paragraph -->
HTML;

estrato_portal_upsert_page( 'sobre', "Sobre o {$title}", $sobre );
estrato_portal_upsert_page( 'politica-editorial', 'Política editorial', $editorial );
estrato_portal_upsert_page( 'contato', 'Contato', $contato );
estrato_portal_upsert_page( 'privacidade', 'Política de privacidade', $privacidade );

// Menu institucional no footer.
$menu_name = 'Estrato Institucional';
$menu_id   = wp_get_nav_menu_object( $menu_name );
if ( ! $menu_id ) {
	$menu_id = wp_create_nav_menu( $menu_name );
} else {
	$menu_id = $menu_id->term_id;
}
$pages = array( 'sobre', 'politica-editorial', 'contato', 'privacidade' );
foreach ( $pages as $slug ) {
	$page = get_page_by_path( $slug );
	if ( ! $page ) {
		continue;
	}
	$items = wp_get_nav_menu_items( $menu_id );
	$found = false;
	if ( $items ) {
		foreach ( $items as $item ) {
			if ( (int) $item->object_id === (int) $page->ID ) {
				$found = true;
				break;
			}
		}
	}
	if ( ! $found ) {
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'  => $page->post_title,
				'menu-item-object' => 'page',
				'menu-item-object-id' => $page->ID,
				'menu-item-type'   => 'post_type',
				'menu-item-status' => 'publish',
			)
		);
	}
}
$locations = get_theme_mod( 'nav_menu_locations', array() );
$locations['footer'] = (int) $menu_id;
set_theme_mod( 'nav_menu_locations', $locations );

WP_CLI::success( "Institucional OK — {$portal}" );
