<?php
/**
 * Página-mãe estrato.cc — rotas /financas, /mente, /saude…
 *
 * Cada vertical ganha landing própria na raiz da rede (além do subdomínio),
 * fortalecendo SEO do domínio mãe com conteúdo único + links canônicos.
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mapa portal_id → path na mãe.
 *
 * @return array<string, array{path:string,title:string,tagline:string,domain:string}>
 */
function estrato_network_path_map() {
	$out = array();
	if ( ! function_exists( 'estrato_nav_network_catalog' ) ) {
		return $out;
	}
	$defaults = array(
		'estrato-finance'  => 'financas',
		'estrato-mind'     => 'mente',
		'estrato-lifestyle'=> 'lifestyle',
		'estrato-science'  => 'science',
		'estrato-agro'     => 'agro',
		'estrato-esg'      => 'esg',
		'estrato-viagem'   => 'viagem',
		'estrato-politica' => 'politica',
		'estrato-esporte'  => 'esporte',
		'estrato-saude'    => 'saude',
		'estrato-educacao' => 'educacao',
		'estrato-tech'     => 'tech',
		'estrato-carros'   => 'carros',
		'estrato-culture'  => 'culture',
	);
	foreach ( estrato_nav_network_catalog() as $node ) {
		$id = $node['id'] ?? '';
		if ( ! $id || empty( $defaults[ $id ] ) ) {
			continue;
		}
		$host = (string) wp_parse_url( $node['url'] ?? '', PHP_URL_HOST );
		$out[ $id ] = array(
			'path'    => $defaults[ $id ],
			'title'   => (string) ( $node['name'] ?? $defaults[ $id ] ),
			'tagline' => (string) ( $node['tagline'] ?? '' ),
			'domain'  => $host,
			'url'     => (string) ( $node['url'] ?? '' ),
		);
	}
	return $out;
}

/**
 * Conteúdo único da landing path (evita thin duplicate do subdomínio).
 *
 * @param array{path:string,title:string,tagline:string,domain:string,url:string} $node
 * @return string
 */
function estrato_network_path_page_content( $node ) {
	$title   = $node['title'];
	$tagline = $node['tagline'];
	$url     = $node['url'];
	$path    = $node['path'];

	$html  = '<section class="estrato-network-path">';
	$html .= '<p class="estrato-kicker">Rede Estrato</p>';
	$html .= '<h1 class="estrato-display">' . esc_html( $title ) . '</h1>';
	$html .= '<p class="estrato-network-path__lead">' . esc_html( $tagline ) . '</p>';
	$html .= '<p>Esta é a página-hub da vertical <strong>' . esc_html( $path )
		. '</strong> no domínio mãe <strong>estrato.cc</strong>. A cobertura contínua vive no portal especializado '
		. '<a href="' . esc_url( $url ) . '">' . esc_html( $node['domain'] ) . '</a>, '
		. 'com equipe editorial, entity pages e padrões YMYL da rede.</p>';
	$html .= '<h2>O que você encontra nesta vertical</h2>';
	$html .= '<ul>';
	$html .= '<li>Notícias e análises com autoria da equipe editorial.</li>';
	$html .= '<li>Guias evergreen em /tudo-sobre/ no portal especializado.</li>';
	$html .= '<li>Transparência: metodologia, ética, correções e FAQ institucional.</li>';
	$html .= '<li>Abertura GEO: llms.txt e crawlers de IA liberados.</li>';
	$html .= '</ul>';
	$html .= '<p class="estrato-network-path__cta"><a class="estrato-btn" href="' . esc_url( $url ) . '">Abrir '
		. esc_html( $title ) . '</a></p>';
	$html .= '<h2>Outras verticais da rede</h2><ul class="estrato-network-path__grid">';
	foreach ( estrato_network_path_map() as $other ) {
		if ( $other['path'] === $path ) {
			continue;
		}
		$html .= '<li><a href="' . esc_url( home_url( '/' . $other['path'] . '/' ) ) . '">'
			. esc_html( $other['title'] ) . '</a> — ' . esc_html( $other['tagline'] ) . '</li>';
	}
	$html .= '</ul>';
	$html .= '<h2>Página-mãe</h2>';
	$html .= '<p>Volte ao hub principal: <a href="https://estrato.cc/">estrato.cc</a>. '
		. 'Equipe e blogs: <a href="https://estrato.cc/blog/">estrato.cc/blog</a>.</p>';
	$html .= '</section>';
	return $html;
}

/**
 * Cria landings /financas etc. apenas no portal mãe (estrato.cc).
 *
 * @return array<string,int>
 */
function estrato_network_ensure_path_hubs() {
	$host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
	$portal = function_exists( 'estrato_nav_current_portal_id' ) ? estrato_nav_current_portal_id() : '';
	if ( 'estrato.cc' !== $host && 'estrato-finance' !== $portal ) {
		return array();
	}

	$ids = array();
	foreach ( estrato_network_path_map() as $portal_id => $node ) {
		$slug = $node['path'];
		$page = get_page_by_path( $slug, OBJECT, 'page' );
		$data = array(
			'post_title'   => $node['title'],
			'post_name'    => $slug,
			'post_content' => estrato_network_path_page_content( $node ),
			'post_status'  => 'publish',
			'post_type'    => 'page',
		);
		if ( $page ) {
			$data['ID'] = $page->ID;
			$id         = wp_update_post( $data, true );
		} else {
			$id = wp_insert_post( $data, true );
		}
		if ( ! is_wp_error( $id ) ) {
			$ids[ $slug ] = (int) $id;
			update_post_meta( (int) $id, '_estrato_network_portal', $portal_id );
			// Canonical da landing aponta para ela mesma no domínio mãe.
			update_post_meta( (int) $id, '_yoast_wpseo_canonical', home_url( '/' . $slug . '/' ) );
		}
	}

	update_option( 'estrato_network_path_hubs', $ids, false );
	return $ids;
}

/**
 * Enrich network catalog with mother path URLs.
 *
 * @param array<int,array<string,mixed>> $catalog
 * @return array<int,array<string,mixed>>
 */
function estrato_network_catalog_with_paths( $catalog ) {
	$map = estrato_network_path_map();
	foreach ( $catalog as &$node ) {
		$id = $node['id'] ?? '';
		if ( $id && isset( $map[ $id ] ) ) {
			$node['path']     = $map[ $id ]['path'];
			$node['path_url'] = 'https://estrato.cc/' . $map[ $id ]['path'] . '/';
		}
	}
	unset( $node );
	return $catalog;
}

/**
 * Injeta links path no hub da rede (home finance).
 *
 * @return string
 */
function estrato_network_hub_html_with_paths() {
	$html = '<div class="estrato-network-hub"><p>Explore as verticais da rede Estrato (domínio mãe + portais):</p>';
	$html .= '<ul class="estrato-network-hub-grid">';
	foreach ( estrato_network_path_map() as $node ) {
		$path_url = home_url( '/' . $node['path'] . '/' );
		// Em satélites, apontar para estrato.cc/path.
		if ( 'estrato.cc' !== (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) {
			$path_url = 'https://estrato.cc/' . $node['path'] . '/';
		}
		$html .= '<li><a href="' . esc_url( $path_url ) . '"><strong>' . esc_html( $node['title'] )
			. '</strong></a><br><span class="estrato-network-tagline">' . esc_html( $node['tagline'] )
			. '</span><br><a class="estrato-network-sub" href="' . esc_url( $node['url'] ) . '">'
			. esc_html( $node['domain'] ) . '</a></li>';
	}
	$html .= '</ul></div>';
	return $html;
}

/**
 * Substitui HTML do hub quando disponível.
 *
 * @return string
 */
function estrato_network_hub_html_filter() {
	return estrato_network_hub_html_with_paths();
}

/**
 * Schema WebPage nas landings path.
 */
function estrato_network_path_schema() {
	if ( ! is_page() ) {
		return;
	}
	$post = get_queried_object();
	if ( ! $post || ! get_post_meta( $post->ID, '_estrato_network_portal', true ) ) {
		return;
	}
	$schema = array(
		'@context'   => 'https://schema.org',
		'@type'      => 'WebPage',
		'name'       => get_the_title( $post ),
		'url'        => get_permalink( $post ),
		'isPartOf'   => array(
			'@type' => 'WebSite',
			'name'  => 'Estrato',
			'url'   => 'https://estrato.cc/',
		),
		'about'      => array(
			'@type' => 'Thing',
			'name'  => get_the_title( $post ),
		),
		'inLanguage' => 'pt-BR',
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}
add_action( 'wp_head', 'estrato_network_path_schema', 6 );

/**
 * CSS leve.
 */
function estrato_network_path_styles() {
	if ( ! is_page() && ! is_front_page() ) {
		return;
	}
	$css = '.estrato-network-path{max-width:48rem;margin:0 auto;padding:1.5rem 1rem 2.5rem}'
		. '.estrato-network-path__lead{font-size:1.15rem;opacity:.9}'
		. '.estrato-network-path__cta{margin:1.5rem 0}'
		. '.estrato-network-path__cta a{font-weight:700}'
		. '.estrato-network-path__grid{display:grid;gap:.5rem;padding-left:1.1rem}'
		. '.estrato-network-sub{font-size:.85rem;opacity:.8}';
	if ( function_exists( 'estrato_perf_style_add' ) ) {
		estrato_perf_style_add( 'estrato-network-path', $css, 'main' );
	} else {
		echo '<style id="estrato-network-path">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_head', 'estrato_network_path_styles', 23 );
