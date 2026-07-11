<?php
/**
 * Sprint 8 — taxonomia financeira: sync, merge legado, subcategorias, layout 1:1.
 *
 * Uso: wp eval-file setup-sprint8-taxonomy.php
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

/**
 * @param array<int, string> $slugs
 * @return array<string, int>
 */
function estrato_s8_category_ids( $slugs ) {
	$ids = array();
	foreach ( $slugs as $slug ) {
		$term = get_term_by( 'slug', $slug, 'category' );
		if ( $term && ! is_wp_error( $term ) ) {
			$ids[ $slug ] = (int) $term->term_id;
		}
	}
	return $ids;
}

// ─── S8.4 Sync YAML/PHP → preset ─────────────────────────────────
if ( function_exists( 'estrato_rss_sync_portal_taxonomy' ) ) {
	$sync = estrato_rss_sync_portal_taxonomy();
	WP_CLI::log( 'Taxonomia sincronizada: ' . wp_json_encode( $sync ) );
} elseif ( function_exists( 'estrato_rss_apply_preset' ) ) {
	estrato_rss_apply_preset( 'brasil-financeiro' );
	WP_CLI::log( 'Preset brasil-financeiro aplicado (fallback)' );
}

// ─── S8.2 Merge categorias legado ────────────────────────────────
$merge_file = dirname( __FILE__ ) . '/merge-legacy-categories.php';
if ( is_readable( $merge_file ) ) {
	require $merge_file;
}

// ─── S8.1 Auditoria volume/thin ──────────────────────────────────
$finance_slugs = function_exists( 'estrato_rss_get_finance_menu_order' )
	? estrato_rss_get_finance_menu_order()
	: array( 'economia', 'mercados', 'negocios', 'financas-pessoais', 'criptomoedas', 'agronegocio', 'mundo' );

WP_CLI::log( '--- Auditoria categorias financeiras ---' );
foreach ( $finance_slugs as $slug ) {
	$term = get_term_by( 'slug', $slug, 'category' );
	if ( ! $term ) {
		WP_CLI::warning( "Categoria ausente: $slug" );
		continue;
	}
	$count = (int) $term->count;
	$thin  = 0;
	if ( function_exists( 'estrato_content_post_word_count' ) ) {
		$posts = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				'category'       => (int) $term->term_id,
				'fields'         => 'ids',
			)
		);
		foreach ( $posts as $pid ) {
			if ( estrato_content_post_word_count( $pid ) < 300 ) {
				++$thin;
			}
		}
	}
	$desc_len = strlen( (string) $term->description );
	WP_CLI::log( sprintf( '%s: posts=%d thin_sample=%d desc_chars=%d', $slug, $count, $thin, $desc_len ) );
}

// ─── S8.5 Descrições SEO (do taxonomy file) ───────────────────────
if ( function_exists( 'estrato_rss_load_finance_taxonomy' ) ) {
	$taxonomy = estrato_rss_load_finance_taxonomy();
	if ( ! empty( $taxonomy['categories'] ) ) {
		foreach ( $taxonomy['categories'] as $slug => $cat ) {
			$term = get_term_by( 'slug', $slug, 'category' );
			if ( ! $term || empty( $cat['description'] ) ) {
				continue;
			}
			wp_update_term(
				(int) $term->term_id,
				'category',
				array( 'description' => (string) $cat['description'] )
			);
		}
		WP_CLI::log( 'Descrições SEO de categorias atualizadas' );
	}
}

// ─── S8.6 PressGrid sections 1:1 ───────────────────────────────
$cat_ids = estrato_s8_category_ids( $finance_slugs );
if ( count( $cat_ids ) < 7 ) {
	WP_CLI::warning( 'Menos de 7 categorias resolvidas para layout: ' . count( $cat_ids ) );
}

$newsletter_html = '<div class="estrato-newsletter"><h2>Newsletter Estrato</h2><p>Receba os destaques de economia e mercados no seu e-mail.</p>[contact-form-7 id="4" title="Contact form 1"]</div>';
$mais_lidas_html = '[estrato_mais_lidas count="7"]';

$columns_html = '';
if ( shortcode_exists( 'estrato_home_columns' ) ) {
	$columns_html = do_shortcode( '[estrato_home_columns]' );
}
if ( ! $columns_html ) {
	$columns_html = '[estrato_home_columns]';
}

$sections = array(
	array(
		'id'          => 'hero',
		'label'       => 'Economia',
		'enabled'     => true,
		'layout'      => 'hero-grid',
		'category'    => $cat_ids['economia'] ?? 0,
		'post_count'  => 3,
		'custom_html' => '',
	),
	array(
		'id'          => 'custom_html',
		'label'       => 'Colunas',
		'enabled'     => true,
		'layout'      => 'custom_html',
		'category'    => 0,
		'post_count'  => 0,
		'custom_html' => $columns_html,
	),
	array(
		'id'          => 'latest_posts',
		'label'       => 'Últimas',
		'enabled'     => true,
		'layout'      => 'grid-3',
		'category'    => 0,
		'post_count'  => 6,
		'custom_html' => '',
	),
	array(
		'id'          => 'category_mercados',
		'label'       => 'Mercados',
		'enabled'     => true,
		'layout'      => 'grid-4',
		'category'    => $cat_ids['mercados'] ?? 0,
		'post_count'  => 4,
		'custom_html' => '',
	),
	array(
		'id'          => 'trending',
		'label'       => 'Mais lidas',
		'enabled'     => true,
		'layout'      => 'custom_html',
		'category'    => 0,
		'post_count'  => 0,
		'custom_html' => $mais_lidas_html,
	),
	array(
		'id'          => 'category_negocios',
		'label'       => 'Negócios',
		'enabled'     => true,
		'layout'      => 'grid-4',
		'category'    => $cat_ids['negocios'] ?? 0,
		'post_count'  => 4,
		'custom_html' => '',
	),
	array(
		'id'          => 'category_financas',
		'label'       => 'Finanças Pessoais',
		'enabled'     => true,
		'layout'      => 'grid-2',
		'category'    => $cat_ids['financas-pessoais'] ?? 0,
		'post_count'  => 4,
		'custom_html' => '',
	),
	array(
		'id'          => 'category_cripto',
		'label'       => 'Criptomoedas',
		'enabled'     => true,
		'layout'      => 'grid-3',
		'category'    => $cat_ids['criptomoedas'] ?? 0,
		'post_count'  => 4,
		'custom_html' => '',
	),
	array(
		'id'          => 'category_agro',
		'label'       => 'Agronegócio',
		'enabled'     => true,
		'layout'      => 'grid-3',
		'category'    => $cat_ids['agronegocio'] ?? 0,
		'post_count'  => 4,
		'custom_html' => '',
	),
	array(
		'id'          => 'category_mundo',
		'label'       => 'Internacional',
		'enabled'     => true,
		'layout'      => 'grid-3',
		'category'    => $cat_ids['mundo'] ?? 0,
		'post_count'  => 4,
		'custom_html' => '',
	),
	array(
		'id'          => 'newsletter',
		'label'       => 'Newsletter',
		'enabled'     => true,
		'layout'      => 'newsletter',
		'category'    => 0,
		'post_count'  => 0,
		'custom_html' => $newsletter_html,
	),
);

update_option( 'pressgrid_layout_sections', $sections, false );
WP_CLI::log( 'PressGrid layout 1:1 com 7 editorias configurado' );

if ( ! empty( $cat_ids['mercados'] ) ) {
	set_theme_mod( 'pressgrid_breaking_news_category', (int) $cat_ids['mercados'] );
	set_theme_mod( 'pressgrid_forex_category_slug', 'mercados' );
}

$menu = wp_get_nav_menu_object( 'Estrato Principal' );
if ( $menu ) {
	$locations = get_theme_mod( 'nav_menu_locations', array() );
	$locations['primary'] = (int) $menu->term_id;
	$col_menu             = wp_get_nav_menu_object( 'Estrato Colunas' );
	if ( $col_menu ) {
		$locations['secondary'] = (int) $col_menu->term_id;
	}
	set_theme_mod( 'nav_menu_locations', $locations );
}

flush_rewrite_rules( false );
WP_CLI::success( 'Sprint 8 taxonomia financeira configurada.' );
