<?php
/**
 * Home editorial estilo G1 — hero + Agora + feed finito (Sprint home-g1).
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
 * @return array{label:string,url:string,root:WP_Term|null}
 */
function estrato_home_post_kicker_data( $post ) {
	$empty = array(
		'label' => '',
		'url'   => '',
		'root'  => null,
	);
	$cats  = get_the_category( $post->ID );
	if ( empty( $cats ) ) {
		return $empty;
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
	$sub   = ( $deepest->term_id !== $root->term_id ) ? $deepest->name : '';
	$area  = $root->name;
	$label = estrato_home_kicker_uppercase( $sub ? $area . ' · ' . $sub : $area );
	$link  = get_category_link( $root );
	return array(
		'label' => $label,
		'url'   => is_wp_error( $link ) ? '' : (string) $link,
		'root'  => $root,
	);
}

/**
 * @param WP_Post $post
 * @return string
 */
function estrato_home_post_kicker( $post ) {
	$data = estrato_home_post_kicker_data( $post );
	return $data['label'];
}

/**
 * Uppercase seguro para UTF-8 evitando dupla codificação de entities.
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
		$slug  = estrato_ds_post_editoria_slug( $post->ID );
		$score = (int) get_post_meta( $post->ID, '_yoast_wpseo_linkdex', true );
		$bonus = ( $slug === $priority ) ? 20 : 0;
		$hash  = abs( crc32( $post->ID . '-' . gmdate( 'Y-m-d-H' ) ) ) % 100;
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
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => 6,
		'orderby'             => 'date',
		'order'               => 'DESC',
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);
	return get_posts( array_merge( $defaults, $args ) );
}

/**
 * Normaliza título para dedupe soft (cópias / reexports com mesmo headline).
 *
 * @param string $title
 * @return string
 */
function estrato_home_normalize_title( $title ) {
	$decoded = html_entity_decode( wp_strip_all_tags( (string) $title ), ENT_QUOTES, 'UTF-8' );
	$decoded = function_exists( 'mb_strtolower' ) ? mb_strtolower( $decoded, 'UTF-8' ) : strtolower( $decoded );
	$decoded = preg_replace( '/\s+/u', ' ', $decoded );
	return trim( (string) $decoded );
}

/**
 * @param array<int, WP_Post> $posts
 * @param array<int, int>     $used_ids
 * @param int                 $limit
 * @param array<int, string>  $used_titles
 * @return array{items:array<int,WP_Post>,used:array<int,int>,titles:array<int,string>}
 */
function estrato_home_take_unique( $posts, $used_ids, $limit, $used_titles = array() ) {
	$items = array();
	foreach ( $posts as $post ) {
		if ( in_array( $post->ID, $used_ids, true ) ) {
			continue;
		}
		$norm = estrato_home_normalize_title( get_the_title( $post ) );
		if ( $norm && in_array( $norm, $used_titles, true ) ) {
			continue;
		}
		$items[]    = $post;
		$used_ids[] = $post->ID;
		if ( $norm ) {
			$used_titles[] = $norm;
		}
		if ( count( $items ) >= $limit ) {
			break;
		}
	}
	return array(
		'items'  => $items,
		'used'   => $used_ids,
		'titles' => $used_titles,
	);
}

/**
 * Página do feed contínuo (SSR, 1-indexada).
 *
 * @return int
 */
function estrato_home_feed_page() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$page = isset( $_GET['feed_page'] ) ? (int) $_GET['feed_page'] : 1;
	return max( 1, min( 20, $page ) );
}

/**
 * Tamanho da leva do feed.
 *
 * @return int
 */
function estrato_home_feed_per_page() {
	/* Checklist C3: ≤5 itens na 1ª dobra + CTA “Mostrar mais”. */
	return 5;
}

/**
 * Card HTML.
 *
 * @param WP_Post $post
 * @param string  $variant hero|secondary|list|feed
 * @param array   $opts    {with_thumb?:bool,index?:int}
 * @return string
 */
function estrato_home_render_card( $post, $variant = 'list', $opts = array() ) {
	$opts   = wp_parse_args(
		$opts,
		array(
			'with_thumb' => null,
			'index'      => 0,
		)
	);
	$slug   = estrato_ds_post_editoria_slug( $post->ID );
	$color  = estrato_ds_editoria_color( $slug );
	$kick   = estrato_home_post_kicker_data( $post );
	$time   = estrato_home_relative_time( $post->ID );
	$thumb  = '';
	$show_thumb = $opts['with_thumb'];
	if ( null === $show_thumb ) {
		if ( 'hero' === $variant || 'secondary' === $variant ) {
			$show_thumb = true;
		} elseif ( 'feed' === $variant ) {
			$show_thumb = ( 0 === ( (int) $opts['index'] % 4 ) );
		} else {
			$show_thumb = false;
		}
	}

	if ( $show_thumb ) {
		$size  = ( 'hero' === $variant ) ? 'medium_large' : 'medium';
		$attrs = array(
			'loading'  => ( 'hero' === $variant ) ? 'eager' : 'lazy',
			'decoding' => 'async',
		);
		if ( 'hero' === $variant ) {
			$attrs['fetchpriority'] = 'high';
		}
		$thumb = get_the_post_thumbnail( $post->ID, $size, $attrs );
	}

	$permalink = get_permalink( $post );
	$html      = '<article class="estrato-home-card estrato-home-card--' . esc_attr( $variant ) . '" style="--estrato-cat-color:' . esc_attr( $color ) . '">';

	if ( 'feed' === $variant ) {
		$html .= '<div class="estrato-home-card__row">';
		$html .= '<div class="estrato-home-card__body">';
		if ( $kick['label'] ) {
			if ( $kick['url'] ) {
				$html .= '<a class="estrato-kicker estrato-home-card__kicker" href="' . esc_url( $kick['url'] ) . '">' . esc_html( $kick['label'] ) . '</a>';
			} else {
				$html .= '<span class="estrato-kicker estrato-home-card__kicker">' . esc_html( $kick['label'] ) . '</span>';
			}
		}
		$html .= '<h3 class="estrato-display estrato-home-card__title"><a href="' . esc_url( $permalink ) . '">' . esc_html( get_the_title( $post ) ) . '</a></h3>';
		if ( $time ) {
			$html .= '<time class="estrato-caption" datetime="' . esc_attr( get_the_date( 'c', $post ) ) . '">' . esc_html( $time ) . '</time>';
		}
		$html .= '</div>';
		if ( $thumb ) {
			$html .= '<a class="estrato-home-card__media estrato-home-card__media--feed" href="' . esc_url( $permalink ) . '" tabindex="-1" aria-hidden="true">' . $thumb . '</a>';
		}
		$html .= '</div>';
	} else {
		$html .= '<a href="' . esc_url( $permalink ) . '" class="estrato-home-card__link">';
		if ( $thumb ) {
			$html .= '<div class="estrato-home-card__media">' . $thumb . '</div>';
		}
		$html .= '<div class="estrato-home-card__body">';
		if ( $kick['label'] ) {
			$html .= '<span class="estrato-kicker">' . esc_html( $kick['label'] ) . '</span>';
		}
		$html .= '<h3 class="estrato-display estrato-home-card__title">' . esc_html( get_the_title( $post ) ) . '</h3>';
		if ( $time ) {
			$html .= '<time class="estrato-caption" datetime="' . esc_attr( get_the_date( 'c', $post ) ) . '">' . esc_html( $time ) . '</time>';
		}
		$html .= '</div></a>';
	}

	$html .= '</article>';
	return $html;
}

/**
 * Faixa breaking opcional (só se houver post recente na categoria).
 *
 * @param array<int, int>    $exclude_ids
 * @param array<int, string> $exclude_titles
 * @return array{html:string,used:array<int,int>,titles:array<int,string>}
 */
function estrato_home_render_breaking( $exclude_ids = array(), $exclude_titles = array() ) {
	$portal = function_exists( 'estrato_nav_current_portal_id' ) ? estrato_nav_current_portal_id() : 'estrato-finance';
	/* Breaking "Mercados" só faz sentido no portal financeiro. */
	if ( 'estrato-finance' !== $portal ) {
		return array(
			'html'   => '',
			'used'   => $exclude_ids,
			'titles' => $exclude_titles,
		);
	}
	$config = function_exists( 'estrato_portal_get_config' ) ? estrato_portal_get_config() : array();
	$label  = (string) ( $config['branding']['breaking_label'] ?? 'Mercados' );
	$mod_id = (int) get_theme_mod( 'pressgrid_breaking_news_category', 0 );
	$slug   = sanitize_title( (string) ( $config['branding']['breaking_category'] ?? 'mercados' ) );

	$term = null;
	if ( $mod_id ) {
		$term = get_term( $mod_id, 'category' );
	}
	if ( ( ! $term || is_wp_error( $term ) ) && $slug ) {
		$term = get_term_by( 'slug', $slug, 'category' );
	}
	if ( ! $term || is_wp_error( $term ) ) {
		return array(
			'html'   => '',
			'used'   => $exclude_ids,
			'titles' => $exclude_titles,
		);
	}
	if ( '' === $label ) {
		$label = $term->name;
	}

	$pool = estrato_home_fetch_posts(
		array(
			'posts_per_page' => 4,
			'cat'            => (int) $term->term_id,
			'date_query'     => array(
				array( 'after' => '8 hours ago' ),
			),
		)
	);
	$pick = estrato_home_take_unique( $pool, $exclude_ids, 1, $exclude_titles );
	if ( ! $pick['items'] ) {
		return array(
			'html'   => '',
			'used'   => $exclude_ids,
			'titles' => $exclude_titles,
		);
	}
	$post = $pick['items'][0];
	$html = '<div class="estrato-home-breaking" role="status">';
	$html .= '<a class="estrato-home-breaking__link" href="' . esc_url( get_permalink( $post ) ) . '">';
	$html .= '<span class="estrato-home-breaking__label">' . esc_html( $label ) . '</span>';
	$html .= '<span class="estrato-home-breaking__title">' . esc_html( get_the_title( $post ) ) . '</span>';
	$html .= '</a></div>';

	return array(
		'html'   => $html,
		'used'   => $pick['used'],
		'titles' => $pick['titles'],
	);
}

/**
 * Query Agora: 4h multi-editoria, fallback 24h.
 *
 * @param array<int, int>    $used_ids
 * @param int                $limit
 * @param array<int, string> $used_titles
 * @return array{items:array<int,WP_Post>,used:array<int,int>,titles:array<int,string>}
 */
function estrato_home_fetch_agora( $used_ids, $limit = 8, $used_titles = array() ) {
	$pool = estrato_home_fetch_posts(
		array(
			'posts_per_page' => 16,
			'date_query'     => array(
				array( 'after' => '4 hours ago' ),
			),
		)
	);
	$agora = estrato_home_take_unique( $pool, $used_ids, $limit, $used_titles );
	if ( count( $agora['items'] ) < 5 ) {
		$pool  = estrato_home_fetch_posts(
			array(
				'posts_per_page' => 16,
				'date_query'     => array(
					array( 'after' => '1 day ago' ),
				),
			)
		);
		$agora = estrato_home_take_unique( $pool, $used_ids, $limit, $used_titles );
	}
	/* Portais de baixo volume: completar com os mais recentes sem janela. */
	if ( count( $agora['items'] ) < 5 ) {
		$pool  = estrato_home_fetch_posts( array( 'posts_per_page' => 16 ) );
		$agora = estrato_home_take_unique( $pool, $used_ids, $limit, $used_titles );
	}
	return $agora;
}

/**
 * Layout completo da home (hero + Agora + ≤5 blocos de editoria).
 *
 * @return string
 */
function estrato_home_render_layout() {
	$used      = array();
	$titles    = array();
	$editorias = function_exists( 'estrato_aeo_portal_editorias' )
		? estrato_aeo_portal_editorias()
		: array( 'economia', 'mercados', 'negocios', 'financas-pessoais', 'criptomoedas', 'agronegocio', 'mundo' );

	$pool = estrato_home_fetch_posts( array( 'posts_per_page' => 40 ) );
	$hero = estrato_home_pick_hero( $pool, $editorias );
	if ( $hero ) {
		$used[]  = $hero->ID;
		$norm    = estrato_home_normalize_title( get_the_title( $hero ) );
		if ( $norm ) {
			$titles[] = $norm;
		}
	}

	/* Agora antes das laterais: inventário fino não esvazia a faixa “Agora”. */
	$agora  = estrato_home_fetch_agora( $used, 5, $titles );
	$used   = $agora['used'];
	$titles = $agora['titles'];

	$secondary_pool = estrato_home_fetch_posts( array( 'posts_per_page' => 12 ) );
	$sec            = estrato_home_take_unique( $secondary_pool, $used, 2, $titles );
	$used           = $sec['used'];
	$titles         = $sec['titles'];

	$breaking = estrato_home_render_breaking( $used, $titles );
	$used     = $breaking['used'];
	$titles   = $breaking['titles'];

	/* Checklist C3: até 5 blocos de editoria com CTA “Ver mais em …”. */
	$max_blocks   = 5;
	$blocks_shown = 0;
	$block_html   = '';
	foreach ( $editorias as $slug ) {
		if ( $blocks_shown >= $max_blocks ) {
			break;
		}
		$term = get_term_by( 'slug', $slug, 'category' );
		if ( ! $term || is_wp_error( $term ) ) {
			continue;
		}
		$cat_pool = estrato_home_fetch_posts(
			array(
				'posts_per_page' => 4,
				'cat'            => (int) $term->term_id,
			)
		);
		$block = estrato_home_take_unique( $cat_pool, $used, 4, $titles );
		if ( ! $block['items'] ) {
			continue;
		}
		$used   = $block['used'];
		$titles = $block['titles'];
		++$blocks_shown;
		$color     = estrato_ds_editoria_color( $slug );
		$term_name = html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' );
		$block_html .= '<section class="estrato-home-block" style="--estrato-cat-color:' . esc_attr( $color ) . '" aria-label="' . esc_attr( $term_name ) . '">';
		$block_html .= '<header class="estrato-home-block__head"><h2 class="estrato-display"><a href="' . esc_url( get_category_link( $term ) ) . '">' . esc_html( $term->name ) . '</a></h2></header>';
		$block_html .= '<div class="estrato-home-block__grid">';
		$first = true;
		foreach ( $block['items'] as $post ) {
			$block_html .= estrato_home_render_card( $post, $first ? 'secondary' : 'list' );
			$first       = false;
		}
		$block_html .= '</div>';
		$block_html .= '<p class="estrato-home-block__more"><a href="' . esc_url( get_category_link( $term ) ) . '">Ver mais em ' . esc_html( $term_name ) . '</a></p>';
		$block_html .= '</section>';
	}

	/* Feed finito G1: leva curta (≤5) abaixo dos blocos, sem competir com a 1ª dobra. */
	$feed_page  = estrato_home_feed_page();
	$per_page   = estrato_home_feed_per_page();
	$feed_need  = $feed_page * $per_page;
	$feed_pool  = estrato_home_fetch_posts( array( 'posts_per_page' => max( 48, $feed_need + 8 ) ) );
	$feed_all   = estrato_home_take_unique( $feed_pool, $used, $feed_need + 1, $titles );
	$has_more   = count( $feed_all['items'] ) > $feed_need;
	$feed_items = $has_more ? array_slice( $feed_all['items'], 0, $feed_need ) : $feed_all['items'];

	$html = '<div class="estrato-home-v2">';

	if ( $breaking['html'] ) {
		$html .= $breaking['html'];
	}

	if ( $hero ) {
		$html .= '<section class="estrato-home-hero" aria-label="Manchete">';
		$html .= '<div class="estrato-home-hero__main">' . estrato_home_render_card( $hero, 'hero' ) . '</div>';
		if ( $sec['items'] ) {
			$html .= '<div class="estrato-home-hero__side">';
			foreach ( $sec['items'] as $post ) {
				$html .= estrato_home_render_card( $post, 'list' );
			}
			$html .= '</div>';
		}
		$html .= '</section>';
	}

	/* Omitir Agora vazia (portais novos / inventário fino). */
	if ( $agora['items'] ) {
		$html .= '<section class="estrato-home-agora" aria-label="Agora"><h2 class="estrato-kicker">Agora</h2>';
		$html .= '<ul class="estrato-home-agora__list">';
		foreach ( $agora['items'] as $post ) {
			$html .= '<li>' . estrato_home_render_card( $post, 'list' ) . '</li>';
		}
		$html .= '</ul>';
		$html .= '<p class="estrato-home-agora__more"><a href="#estrato-home-feed">Ver todas</a></p>';
		$html .= '</section>';
	}

	if ( $block_html ) {
		$html .= '<div id="estrato-home-blocks">' . $block_html . '</div>';
	}

	if ( $feed_items ) {
		$html .= '<section class="estrato-home-feed" aria-label="Feed de notícias" id="estrato-home-feed">';
		$html .= '<h2 class="estrato-kicker">Em destaque</h2>';
		$html .= '<div class="estrato-home-feed__list">';
		foreach ( $feed_items as $i => $post ) {
			$html .= estrato_home_render_card(
				$post,
				'feed',
				array(
					'index' => $i,
				)
			);
		}
		$html .= '</div>';

		if ( $has_more ) {
			$next = add_query_arg(
				'feed_page',
				$feed_page + 1,
				home_url( '/' )
			);
			$html .= '<p class="estrato-home-feed__more"><a class="estrato-home-feed__more-btn" href="' . esc_url( $next ) . '#estrato-home-feed">Mostrar mais</a></p>';
		}
		$html .= '</section>';
	}

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
	/* Permite ?feed_page=N na home; bloqueia só /page/N/ do blog. */
	if ( is_paged() && empty( $_GET['feed_page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
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
 *
 * @param string $content
 * @return string
 */
function estrato_home_inject_front_page( $content ) {
	if ( ! estrato_home_is_active() ) {
		return $content;
	}
	return estrato_home_render_layout() . $content;
}
add_filter( 'the_content', 'estrato_home_inject_front_page', 8 );

/**
 * CSS da home v2 — G1 feed.
 */
function estrato_home_styles() {
	if ( ! estrato_home_is_active() ) {
		return;
	}
	$css = '.estrato-home-v2{max-width:1200px;margin:0 auto;padding:var(--estrato-space-3) 1rem}'
		. '@media(min-width:640px){.estrato-home-v2{padding:var(--estrato-space-4) 1rem}}'
		. '.estrato-home-breaking{margin:0 0 var(--estrato-space-3);background:#fff1e5;border:1px solid #e8d5c4;border-radius:var(--estrato-radius,4px)}'
		. '.estrato-home-breaking__link{display:flex;align-items:center;gap:.65rem;padding:.55rem .75rem;color:inherit;text-decoration:none;min-height:44px}'
		. '.estrato-home-breaking__label{flex:0 0 auto;background:#C4170C;color:#fff;font-size:.6875rem;font-weight:800;letter-spacing:.04em;text-transform:uppercase;padding:.25rem .5rem}'
		. '.estrato-home-breaking__title{font-size:.875rem;font-weight:600;line-height:1.35}'
		. '.estrato-home-hero{display:grid;gap:var(--estrato-space-4);margin-bottom:var(--estrato-space-5)}'
		. '@media(min-width:900px){.estrato-home-hero{grid-template-columns:1.6fr 1fr}}'
		. '.estrato-home-hero__side{display:grid;gap:var(--estrato-space-2)}'
		. '.estrato-home-hero__side .estrato-home-card{border-bottom:1px solid var(--estrato-line)}'
		. '@media(min-width:900px){.estrato-home-hero__side .estrato-home-card{border-bottom:0}}'
		. '.estrato-home-card{border-bottom:1px solid var(--estrato-line);padding-bottom:var(--estrato-space-3);margin-bottom:var(--estrato-space-3)}'
		. '.estrato-home-card__link{color:inherit;text-decoration:none;display:block}'
		. '.estrato-home-card__title{font-size:clamp(18px,2.5vw,26px);margin:.35rem 0}'
		. '.estrato-home-card__title a{color:inherit;text-decoration:none}'
		. '.estrato-home-card__title a:hover{text-decoration:underline}'
		. '.estrato-home-card--hero .estrato-home-card__title{font-size:clamp(26px,5vw,42px)}'
		. '.estrato-home-card__media img{width:100%;height:auto;border-radius:var(--estrato-radius);display:block}'
		. '.estrato-home-card__kicker{display:inline-block;text-decoration:none}'
		. '.estrato-home-card__kicker:hover{text-decoration:underline}'
		. '.estrato-home-agora{margin-bottom:var(--estrato-space-5)}'
		. '.estrato-home-agora__list{list-style:none;margin:0;padding:0;display:grid;gap:var(--estrato-space-2);grid-template-columns:1fr}'
		. '@media(min-width:640px){.estrato-home-agora__list{grid-template-columns:repeat(2,1fr)}}'
		. '@media(min-width:960px){.estrato-home-agora__list{grid-template-columns:repeat(4,1fr)}}'
		. '.estrato-home-agora__more,.estrato-home-block__more{margin:.75rem 0 0;font-size:.875rem;font-weight:600}'
		. '.estrato-home-agora__more a,.estrato-home-block__more a{color:var(--estrato-cat-color,#C4170C);text-decoration:none}'
		. '.estrato-home-agora__more a:hover,.estrato-home-block__more a:hover{text-decoration:underline}'
		. '.estrato-home-block{margin-bottom:var(--estrato-space-5);border-top:4px solid var(--estrato-cat-color);padding-top:var(--estrato-space-3)}'
		. '.estrato-home-block__head .estrato-display{font-size:clamp(1.125rem,2.5vw,1.5rem);margin:0 0 var(--estrato-space-3)}'
		. '.estrato-home-block__head a{color:inherit;text-decoration:none}'
		. '.estrato-home-block__head a:hover{text-decoration:underline}'
		. '.estrato-home-block__grid{display:grid;gap:var(--estrato-space-3);grid-template-columns:1fr}'
		. '@media(min-width:640px){.estrato-home-block__grid{grid-template-columns:1fr 1fr}}'
		. '@media(min-width:900px){.estrato-home-block__grid{grid-template-columns:1.2fr 1fr 1fr}}'
		. '.estrato-home-feed{margin-bottom:var(--estrato-space-5)}'
		. '.estrato-home-feed__list{display:flex;flex-direction:column}'
		. '.estrato-home-card--feed{padding-bottom:var(--estrato-space-3)}'
		. '.estrato-home-card__row{display:grid;gap:var(--estrato-space-3);align-items:start;grid-template-columns:1fr}'
		. '.estrato-home-card__media--feed{max-width:140px;justify-self:end}'
		. '@media(min-width:640px){.estrato-home-card--feed .estrato-home-card__row:has(.estrato-home-card__media--feed){grid-template-columns:1fr 140px}}'
		. '.estrato-home-feed__more{margin:var(--estrato-space-4) 0 0;text-align:center}'
		. '.estrato-home-feed__more-btn{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:.65rem 1.25rem;border:1px solid var(--estrato-line,#E4E1DA);background:#fff;color:var(--estrato-ink,#191919);font-weight:700;font-size:.875rem;text-decoration:none;border-radius:4px}'
		. '.estrato-home-feed__more-btn:hover{border-color:#C4170C;color:#C4170C}'
		. '.estrato-home-rede{margin-bottom:var(--estrato-space-5);padding:var(--estrato-space-3) 0;border-top:1px solid var(--estrato-line)}'
		. '.estrato-home-guias{margin-bottom:var(--estrato-space-5)}'
		. '.estrato-home-guias__list{display:flex;flex-wrap:wrap;gap:1rem;list-style:none;margin:0;padding:0}'
		. '@media(prefers-reduced-motion:reduce){.estrato-home-feed__more-btn{transition:none}}';
	if ( function_exists( 'estrato_perf_style_add' ) ) {
		estrato_perf_style_add( 'estrato-home-v2-css', $css, 'main' );
	} else {
		echo '<style id="estrato-home-v2-css">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_head', 'estrato_home_styles', 20 );
