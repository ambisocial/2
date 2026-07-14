<?php
/**
 * Home editorial — 20+ chamadas, dedupe e hero determinístico (Sprint 3).
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @param int $post_id
 * @return string
 */
function estrato_home_relative_time( $post_id ) {
	$ts = get_post_time( 'U', true, $post_id );
	if ( ! $ts ) {
		return '';
	}
	$diff = time() - $ts;
	if ( $diff < DAY_IN_SECONDS ) {
		if ( $diff < HOUR_IN_SECONDS ) {
			$mins = max( 1, (int) floor( $diff / 60 ) );
			return 'há ' . $mins . ' min';
		}
		$hours = max( 1, (int) floor( $diff / HOUR_IN_SECONDS ) );
		return 'há ' . $hours . ' h';
	}
	return get_the_date( 'd/m H:i', $post_id );
}

/**
 * @param WP_Post $post
 * @return string
 */
function estrato_home_post_kicker( $post ) {
	$cats = get_the_category( $post->ID );
	if ( empty( $cats ) ) {
		return '';
	}
	$deepest = $cats[0];
	foreach ( $cats as $cat ) {
		if ( $cat->parent > $deepest->parent ) {
			$deepest = $cat;
		}
	}
	$root = $deepest;
	while ( $root->parent ) {
		$parent = get_term( $root->parent, 'category' );
		if ( ! $parent || is_wp_error( $parent ) ) {
			break;
		}
		$root = $parent;
	}
	$sub  = ( $deepest->term_id !== $root->term_id ) ? $deepest->name : '';
	$area = $root->name;
	return estrato_home_kicker_uppercase( $sub ? $area . ' · ' . $sub : $area );
}

/**
 * Uppercase seguro para UTF-8 evitando dupla codificação de entities.
 *
 * Nomes de categorias podem chegar com `&amp;` já aplicado. `strtoupper()` não
 * é UTF-8 safe e transforma `amp;` em `AMP;`, além de destruir acentos. O
 * `esc_html` do render então re-codifica em `&amp;AMP;` (bug B3 auditoria
 * visual 2026-07-13). Decodificamos antes, uppercase com mb_strtoupper e
 * deixamos o `esc_html` na renderização voltar a codificar corretamente.
 *
 * @param string $text
 * @return string
 */
function estrato_home_kicker_uppercase( $text ) {
	$decoded = html_entity_decode( (string) $text, ENT_QUOTES, 'UTF-8' );
	if ( function_exists( 'mb_strtoupper' ) ) {
		return mb_strtoupper( $decoded, 'UTF-8' );
	}
	return strtoupper( $decoded );
}

/**
 * Hero determinístico (hash, nunca random).
 *
 * @param array<int, WP_Post> $candidates
 * @param array<int, string>  $editorias    Slugs de editoria do portal.
 * @return WP_Post|null
 */
function estrato_home_pick_hero( $candidates, $editorias = array() ) {
	if ( ! $candidates ) {
		return null;
	}
	if ( ! $editorias ) {
		$editorias = array( 'economia', 'mercados', 'negocios' );
	}
	$hour_slot = (int) gmdate( 'G' );
	$slot_idx  = $hour_slot < 12 ? 0 : ( $hour_slot < 18 ? 1 : 2 );
	$priority  = $editorias[ min( $slot_idx, count( $editorias ) - 1 ) ] ?? $editorias[0];
	$scored    = array();
	foreach ( $candidates as $post ) {
		$slug   = estrato_ds_post_editoria_slug( $post->ID );
		$score  = (int) get_post_meta( $post->ID, '_yoast_wpseo_linkdex', true );
		$bonus  = ( $slug === $priority ) ? 20 : 0;
		$hash   = abs( crc32( $post->ID . '-' . gmdate( 'Y-m-d-H' ) ) ) % 100;
		$scored[] = array(
			'post'  => $post,
			'value' => $score + $bonus + ( $hash / 100 ),
		);
	}
	usort(
		$scored,
		function ( $a, $b ) {
			return $b['value'] <=> $a['value'];
		}
	);
	return $scored[0]['post'] ?? $candidates[0];
}

/**
 * @param array<string, mixed> $args
 * @return array<int, WP_Post>
 */
function estrato_home_fetch_posts( $args ) {
	$defaults = array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => 6,
		'orderby'        => 'date',
		'order'          => 'DESC',
	);
	return get_posts( array_merge( $defaults, $args ) );
}

/**
 * @param array<int, WP_Post> $posts
 * @param array<int, int>     $used_ids
 * @param int                 $limit
 * @return array{items:array<int,WP_Post>,used:array<int,int>}
 */
function estrato_home_take_unique( $posts, $used_ids, $limit ) {
	$items = array();
	foreach ( $posts as $post ) {
		if ( in_array( $post->ID, $used_ids, true ) ) {
			continue;
		}
		$items[]     = $post;
		$used_ids[]  = $post->ID;
		if ( count( $items ) >= $limit ) {
			break;
		}
	}
	return array(
		'items' => $items,
		'used'  => $used_ids,
	);
}

/**
 * Card HTML.
 *
 * @param WP_Post $post
 * @param string  $variant hero|secondary|list
 * @return string
 */
function estrato_home_render_card( $post, $variant = 'list' ) {
	$slug  = estrato_ds_post_editoria_slug( $post->ID );
	$color = estrato_ds_editoria_color( $slug );
	$kicker = estrato_home_post_kicker( $post );
	$time   = estrato_home_relative_time( $post->ID );
	if ( 'hero' === $variant ) {
		$thumb = get_the_post_thumbnail(
			$post->ID,
			'medium_large',
			array(
				'loading'       => 'eager',
				'fetchpriority' => 'high',
				'decoding'      => 'async',
			)
		);
	} else {
		$thumb = get_the_post_thumbnail(
			$post->ID,
			'medium',
			array(
				'loading'  => 'lazy',
				'decoding' => 'async',
			)
		);
	}
	$eager = '';

	$html = '<article class="estrato-home-card estrato-home-card--' . esc_attr( $variant ) . '" style="--estrato-cat-color:' . esc_attr( $color ) . '">';
	$html .= '<a href="' . esc_url( get_permalink( $post ) ) . '" class="estrato-home-card__link">';
	if ( $thumb && 'list' !== $variant ) {
		$html .= '<div class="estrato-home-card__media">' . $thumb . '</div>';
	}
	$html .= '<div class="estrato-home-card__body">';
	if ( $kicker ) {
		$html .= '<span class="estrato-kicker">' . esc_html( $kicker ) . '</span>';
	}
	$html .= '<h3 class="estrato-display estrato-home-card__title">' . esc_html( get_the_title( $post ) ) . '</h3>';
	if ( $time ) {
		$html .= '<time class="estrato-caption" datetime="' . esc_attr( get_the_date( 'c', $post ) ) . '">' . esc_html( $time ) . '</time>';
	}
	$html .= '</div></a></article>';
	return $html;
}

/**
 * Layout completo da home.
 *
 * @return string
 */
function estrato_home_render_layout() {
	$used      = array();
	$editorias = function_exists( 'estrato_aeo_portal_editorias' )
		? estrato_aeo_portal_editorias()
		: array( 'economia', 'mercados', 'negocios', 'financas-pessoais', 'criptomoedas', 'agronegocio', 'mundo' );

	$pool = estrato_home_fetch_posts( array( 'posts_per_page' => 40 ) );
	$hero = estrato_home_pick_hero( $pool, $editorias );
	if ( $hero ) {
		$used[] = $hero->ID;
	}

	$secondary_pool = estrato_home_fetch_posts( array( 'posts_per_page' => 12 ) );
	$sec            = estrato_home_take_unique( $secondary_pool, $used, 3 );
	$used           = $sec['used'];

	$agora_pool = estrato_home_fetch_posts( array( 'posts_per_page' => 10, 'date_query' => array( array( 'after' => '1 day ago' ) ) ) );
	$agora      = estrato_home_take_unique( $agora_pool, $used, 5 );
	$used       = $agora['used'];

	$html = '<div class="estrato-home-v2">';

	if ( $hero ) {
		$html .= '<section class="estrato-home-hero" aria-label="Manchete">';
		$html .= '<div class="estrato-home-hero__main">' . estrato_home_render_card( $hero, 'hero' ) . '</div>';
		$html .= '<div class="estrato-home-hero__side">';
		foreach ( $sec['items'] as $post ) {
			$html .= estrato_home_render_card( $post, 'list' );
		}
		$html .= '</div></section>';
	}

	$html .= '<section class="estrato-home-agora" aria-label="Agora"><h2 class="estrato-kicker">Agora</h2><ul class="estrato-home-agora__list">';
	foreach ( $agora['items'] as $post ) {
		$html .= '<li>' . estrato_home_render_card( $post, 'list' ) . '</li>';
	}
	$html .= '</ul>';
	if ( $agora['items'] ) {
		$posts_url = get_option( 'page_for_posts' ) ? get_permalink( (int) get_option( 'page_for_posts' ) ) : home_url( '/' );
		$html     .= '<p class="estrato-home-agora__more"><a href="' . esc_url( $posts_url ) . '">Ver todas</a></p>';
	}
	$html .= '</section>';

	$blocks_shown = 0;
	$max_blocks   = 5;
	$html        .= '<div id="estrato-home-blocks">';
	foreach ( $editorias as $slug ) {
		if ( $blocks_shown >= $max_blocks ) {
			break;
		}
		$term = get_term_by( 'slug', $slug, 'category' );
		if ( ! $term || is_wp_error( $term ) ) {
			continue;
		}
		$block_posts = estrato_home_fetch_posts(
			array(
				'posts_per_page' => 6,
				'cat'            => (int) $term->term_id,
			)
		);
		$block = estrato_home_take_unique( $block_posts, $used, 4 );
		$used  = $block['used'];
		if ( ! $block['items'] ) {
			continue;
		}
		++$blocks_shown;
		$color     = estrato_ds_editoria_color( $slug );
		$term_name = html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' );
		$html     .= '<section class="estrato-home-block" style="--estrato-cat-color:' . esc_attr( $color ) . '" aria-label="' . esc_attr( $term_name ) . '">';
		$html     .= '<header class="estrato-home-block__head"><h2 class="estrato-display"><a href="' . esc_url( get_category_link( $term ) ) . '">' . esc_html( $term->name ) . '</a></h2></header>';
		$html     .= '<div class="estrato-home-block__grid">';
		$first     = true;
		foreach ( $block['items'] as $post ) {
			$html .= estrato_home_render_card( $post, $first ? 'secondary' : 'list' );
			$first = false;
		}
		$html .= '</div>';
		$html .= '<p class="estrato-home-block__more"><a href="' . esc_url( get_category_link( $term ) ) . '">Ver mais em ' . esc_html( $term_name ) . '</a></p>';
		$html .= '</section>';
	}
	$html .= '</div>';

	if ( function_exists( 'estrato_nav_network_hub_html' ) ) {
		$html .= '<section class="estrato-home-rede" aria-label="Mais da Rede Estrato">';
		$html .= '<h2 class="estrato-kicker">Mais da Rede Estrato</h2>';
		$html .= estrato_nav_network_hub_html();
		$html .= '</section>';
	}

	$html .= '<section class="estrato-home-guias" aria-label="Guias"><h2 class="estrato-kicker">Guias</h2><ul class="estrato-home-guias__list">';
	$hubs = function_exists( 'estrato_aeo_portal_hubs' ) ? estrato_aeo_portal_hubs() : array();
	if ( $hubs ) {
		foreach ( array_slice( $hubs, 0, 6 ) as $hub ) {
			$html .= '<li><a href="' . esc_url( $hub['url'] ) . '">' . esc_html( $hub['title'] ) . '</a></li>';
		}
	} else {
		foreach ( array( 'selic', 'ibovespa', 'dolar' ) as $hub ) {
			$page = get_page_by_path( 'tudo-sobre/' . $hub, OBJECT, 'page' );
			if ( $page ) {
				$html .= '<li><a href="' . esc_url( get_permalink( $page ) ) . '">Tudo sobre ' . esc_html( ucfirst( $hub ) ) . '</a></li>';
			}
		}
	}
	$html .= '</ul></section>';

	if ( shortcode_exists( 'estrato_newsletter' ) ) {
		$html .= do_shortcode( '[estrato_newsletter]' );
	}

	$html .= '</div>';
	return $html;
}
add_shortcode( 'estrato_home_v2', 'estrato_home_render_layout' );

/**
 * Home editorial ativa (blog index ou página estática).
 *
 * @return bool
 */
function estrato_home_is_active() {
	if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}
	if ( is_paged() ) {
		return false;
	}
	if ( function_exists( 'is_front_page' ) && is_front_page() ) {
		return true;
	}
	return function_exists( 'is_home' ) && is_home() && 'posts' === get_option( 'show_on_front' );
}

/**
 * Template plugin na home — template_include aceita path absoluto fora do tema.
 *
 * @param string $template
 * @return string
 */
function estrato_home_template_include( $template ) {
	if ( ! estrato_home_is_active() ) {
		return $template;
	}
	$plugin_tpl = __DIR__ . '/templates/home-v2.php';
	return is_readable( $plugin_tpl ) ? $plugin_tpl : $template;
}
add_filter( 'template_include', 'estrato_home_template_include', 99 );

/**
 * PressGrid home não usa the_content — substitui seções pelo layout v2 (fallback).
 *
 * @param mixed $sections
 * @return mixed
 */
function estrato_home_pressgrid_sections( $sections ) {
	if ( ! estrato_home_is_active() ) {
		return $sections;
	}
	return array(
		array(
			'id'          => 'custom_html',
			'label'       => 'Home Estrato',
			'enabled'     => true,
			'layout'      => 'custom_html',
			'category'    => 0,
			'post_count'  => 0,
			'custom_html' => '[estrato_home_v2]',
		),
	);
}
add_filter( 'option_pressgrid_layout_sections', 'estrato_home_pressgrid_sections', 4 );

/**
 * Injeta home v2 na front page (fallback para temas que usam the_content).
 */
function estrato_home_inject_front_page( $content ) {
	if ( ! estrato_home_is_active() ) {
		return $content;
	}
	return estrato_home_render_layout() . $content;
}
add_filter( 'the_content', 'estrato_home_inject_front_page', 8 );

/**
 * CSS da home v2.
 */
function estrato_home_styles() {
	if ( ! estrato_home_is_active() ) {
		return;
	}
	$css = '.estrato-home-v2{max-width:1200px;margin:0 auto;padding:var(--estrato-space-3) 1rem}'
		. '@media(min-width:640px){.estrato-home-v2{padding:var(--estrato-space-4) 1rem}}'
		. '.estrato-home-hero{display:grid;gap:var(--estrato-space-4);margin-bottom:var(--estrato-space-5)}'
		. '@media(min-width:900px){.estrato-home-hero{grid-template-columns:1.4fr 1fr}}'
		. '.estrato-home-hero__side{display:grid;gap:var(--estrato-space-2)}'
		. '@media(max-width:899px){.estrato-home-hero__side .estrato-home-card{border-bottom:1px solid var(--estrato-line)}}'
		. '.estrato-home-card{border-bottom:1px solid var(--estrato-line);padding-bottom:var(--estrato-space-3)}'
		. '.estrato-home-card__link{color:inherit;text-decoration:none;display:block}'
		. '.estrato-home-card__title{font-size:clamp(18px,2.5vw,26px);margin:.35rem 0}'
		. '.estrato-home-card--hero .estrato-home-card__title{font-size:clamp(26px,5vw,40px)}'
		. '.estrato-home-card__media img{width:100%;height:auto;border-radius:var(--estrato-radius)}'
		. '.estrato-home-agora{margin-bottom:var(--estrato-space-5)}'
		. '.estrato-home-agora__list{list-style:none;margin:0;padding:0;display:grid;gap:var(--estrato-space-2);grid-template-columns:1fr}'
		. '@media(min-width:640px){.estrato-home-agora__list{grid-template-columns:repeat(2,1fr)}}'
		. '@media(min-width:960px){.estrato-home-agora__list{grid-template-columns:repeat(5,1fr)}}'
		. '.estrato-home-agora__more,.estrato-home-block__more{margin:.75rem 0 0;font-size:.875rem;font-weight:600}'
		. '.estrato-home-agora__more a,.estrato-home-block__more a{color:var(--estrato-cat-color,#C4170C);text-decoration:none}'
		. '.estrato-home-agora__more a:hover,.estrato-home-block__more a:hover{text-decoration:underline}'
		. '.estrato-home-block{margin-bottom:var(--estrato-space-5);border-top:4px solid var(--estrato-cat-color);padding-top:var(--estrato-space-3)}'
		. '.estrato-home-block__grid{display:grid;gap:var(--estrato-space-3);grid-template-columns:1fr}'
		. '@media(min-width:640px){.estrato-home-block__grid{grid-template-columns:1fr 1fr}}'
		. '@media(min-width:900px){.estrato-home-block__grid{grid-template-columns:1.2fr 1fr 1fr}}'
		. '.estrato-home-rede{margin-bottom:var(--estrato-space-5);padding:var(--estrato-space-3) 0;border-top:1px solid var(--estrato-line)}'
		. '.estrato-network-hub-grid{display:grid;gap:var(--estrato-space-3);list-style:none;margin:var(--estrato-space-3) 0 0;padding:0;grid-template-columns:1fr}'
		. '@media(min-width:640px){.estrato-network-hub-grid{grid-template-columns:1fr 1fr}}'
		. '@media(min-width:960px){.estrato-network-hub-grid{grid-template-columns:repeat(3,1fr)}}'
		. '.estrato-network-hub-grid a{color:inherit;text-decoration:none}'
		. '.estrato-network-hub-grid a:hover strong{text-decoration:underline}'
		. '.estrato-network-tagline{color:var(--estrato-muted);font-size:.8125rem}'
		. '.estrato-home-guias{margin-bottom:var(--estrato-space-5)}'
		. '.estrato-home-guias__list{display:flex;flex-wrap:wrap;gap:1rem;list-style:none;margin:0;padding:0}';
	if ( function_exists( 'estrato_perf_style_add' ) ) {
		estrato_perf_style_add( 'estrato-home-v2-css', $css, 'main' );
	} else {
		echo '<style id="estrato-home-v2-css">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_head', 'estrato_home_styles', 20 );
