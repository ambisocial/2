<?php
/**
 * Sprint A3 — hubs /tudo-sobre/ + FAQPage (satélites).
 *
 * Cria página pai tudo-sobre e hubs por editoria (prefixo antes do hífen,
 * alinhado ao gate AR-NAV-003). Garante ≥3 hubs para AR-AEO-002.
 *
 * Uso: ESTRATO_PORTAL=estrato-mind wp eval-file setup-sprint-a3-portal-hubs.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$portal = getenv( 'ESTRATO_PORTAL' ) ?: '';
if ( '' === $portal ) {
	$host      = (string) wp_parse_url( home_url(), PHP_URL_HOST );
	$by_domain = array(
		'mente.estrato.cc'     => 'estrato-mind',
		'lifestyle.estrato.cc' => 'estrato-lifestyle',
		'science.estrato.cc'   => 'estrato-science',
		'sustain.estrato.cc'   => 'estrato-sustain',
		'culture.estrato.cc'   => 'estrato-culture',
	);
	$portal = $by_domain[ $host ] ?? '';
}

$loaders = array(
	'estrato-mind'      => 'estrato_rss_load_mind_taxonomy',
	'estrato-lifestyle' => 'estrato_rss_load_lifestyle_taxonomy',
	'estrato-science'   => 'estrato_rss_load_science_taxonomy',
	'estrato-sustain'   => 'estrato_rss_load_sustain_taxonomy',
	'estrato-culture'   => 'estrato_rss_load_culture_taxonomy',
);

$loader = $loaders[ $portal ] ?? null;
if ( ! $loader || ! function_exists( $loader ) ) {
	WP_CLI::error( "Loader de taxonomia ausente para {$portal}" );
}

$taxonomy = $loader();
if ( empty( $taxonomy['categories'] ) ) {
	WP_CLI::error( "Taxonomia vazia para {$portal}" );
}

$blog_name = get_bloginfo( 'name' );

/**
 * @param string $slug
 * @param string $title
 * @param string $content
 * @param int    $parent
 * @return int
 */
function estrato_a3_upsert_page( $slug, $title, $content, $parent = 0 ) {
	$existing = null;
	if ( $parent ) {
		$kids = get_children(
			array(
				'post_parent' => $parent,
				'post_type'   => 'page',
				'post_status' => 'any',
				'name'        => $slug,
			)
		);
		$existing = $kids ? reset( $kids ) : null;
	} else {
		$existing = get_page_by_path( $slug, OBJECT, 'page' );
	}

	$data = array(
		'post_title'   => $title,
		'post_name'    => $slug,
		'post_content' => $content,
		'post_status'  => 'publish',
		'post_type'    => 'page',
		'post_parent'  => $parent,
	);

	if ( $existing ) {
		$data['ID'] = $existing->ID;
		$id         = wp_update_post( $data, true );
	} else {
		$id = wp_insert_post( $data, true );
	}

	if ( is_wp_error( $id ) ) {
		WP_CLI::warning( "Página {$slug}: " . $id->get_error_message() );
		return 0;
	}

	WP_CLI::log( 'Página ' . ( $parent ? "tudo-sobre/{$slug}" : $slug ) . " → #{$id}" );
	return (int) $id;
}

/**
 * @param string $category_slug
 * @return string
 */
function estrato_a3_hub_slug( $category_slug ) {
	$pos = strpos( $category_slug, '-' );
	return false === $pos ? $category_slug : substr( $category_slug, 0, $pos );
}

/**
 * @param string $hub_slug
 * @param string $category_slug
 * @param string $title
 * @return string
 */
function estrato_a3_hub_content( $hub_slug, $category_slug, $title ) {
	global $blog_name;

	$term  = get_term_by( 'slug', $category_slug, 'category' );
	$brand = $term && ! is_wp_error( $term )
		? ( get_term_meta( $term->term_id, '_estrato_brand_name', true ) ?: $term->name )
		: $title;

	$intro = "{$brand} reúne no {$blog_name} notícias, contexto e perguntas frequentes sobre {$title}. "
		. 'A redação acompanha tendências, dados e fontes verificáveis para leitores que buscam profundidade além do headline.';

	$faq = '<h2>Perguntas frequentes</h2>'
		. '<h3>O que você encontra nesta hub?</h3><p>Matérias recentes, contexto editorial e links para a editoria relacionada.</p>'
		. '<h3>Com que frequência atualizamos?</h3><p>Diariamente, conforme a curadoria RSS e a pauta do portal.</p>'
		. '<h3>Onde ver mais conteúdo?</h3><p>Explore a editoria <a href="/category/' . esc_attr( $category_slug ) . '/">' . esc_html( $brand ) . '</a>.</p>';

	return '<!-- wp:paragraph --><p>' . esc_html( $intro ) . '</p><!-- /wp:paragraph -->'
		. '<!-- wp:shortcode -->[estrato_hub_posts category="' . esc_attr( $category_slug ) . '" count="8"]<!-- /wp:shortcode -->'
		. '<!-- wp:html -->' . $faq . '<!-- /wp:html -->';
}

$hub_parent = estrato_a3_upsert_page(
	'tudo-sobre',
	'Tudo sobre',
	'<!-- wp:paragraph --><p>Guias temáticos e cobertura contínua das editorias do ' . esc_html( $blog_name ) . '.</p><!-- /wp:paragraph -->'
);

if ( ! $hub_parent ) {
	WP_CLI::error( 'Falha ao criar página pai tudo-sobre' );
}

$editorias = $taxonomy['index_order'] ?? array_keys( $taxonomy['categories'] );
$hubs      = array();

foreach ( $editorias as $cat_slug ) {
	$cat = $taxonomy['categories'][ $cat_slug ] ?? array();
	$hub = estrato_a3_hub_slug( $cat_slug );
	if ( isset( $hubs[ $hub ] ) ) {
		continue;
	}
	$title = $cat['name'] ?? ucfirst( str_replace( '-', ' ', $cat_slug ) );
	$hubs[ $hub ] = array(
		'title'    => 'Tudo sobre ' . $title,
		'category' => $cat_slug,
	);
}

if ( count( $hubs ) < 3 ) {
	foreach ( $taxonomy['categories'] as $cat_slug => $cat ) {
		if ( count( $hubs ) >= 3 ) {
			break;
		}
		foreach ( (array) ( $cat['subcategories'] ?? array() ) as $sub_slug => $sub ) {
			if ( count( $hubs ) >= 3 ) {
				break 2;
			}
			$hub = estrato_a3_hub_slug( $sub_slug );
			if ( isset( $hubs[ $hub ] ) ) {
				continue;
			}
			$parent_slug = $cat_slug;
			$title       = $sub['name'] ?? ucfirst( str_replace( '-', ' ', $sub_slug ) );
			$hubs[ $hub ] = array(
				'title'    => 'Tudo sobre ' . $title,
				'category' => $parent_slug,
			);
		}
	}
}

$created = 0;
foreach ( $hubs as $hub_slug => $hub ) {
	$content = estrato_a3_hub_content( $hub_slug, $hub['category'], $hub['title'] );
	$id      = estrato_a3_upsert_page( $hub_slug, $hub['title'], $content, $hub_parent );
	if ( $id ) {
		++$created;
	}
}

flush_rewrite_rules( false );

$faq_count = function_exists( 'estrato_regression_hub_faq_schema' )
	? estrato_regression_hub_faq_schema()
	: 0;

WP_CLI::success(
	wp_json_encode(
		array(
			'portal'    => $portal,
			'hubs'      => count( $hubs ),
			'created'   => $created,
			'faq_hubs'  => $faq_count,
		),
		JSON_UNESCAPED_UNICODE
	)
);
