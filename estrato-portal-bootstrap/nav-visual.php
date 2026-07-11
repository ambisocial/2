<?php
/**
 * Navegação & visual — Sprint 5 (shortcodes, byline, home editorias).
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Byline "Por Nome, Especialidade" no single (não altera feed/RSS).
 *
 * @param string $name Display name.
 * @return string
 */
function estrato_nav_author_byline( $name ) {
	if ( is_feed() || ! is_singular( 'post' ) ) {
		return $name;
	}

	global $authordata;
	$user_id = isset( $authordata->ID ) ? (int) $authordata->ID : 0;
	if ( ! $user_id ) {
		return $name;
	}

	$job = get_user_meta( $user_id, 'estrato_job_title', true );
	if ( ! $job ) {
		$job = get_user_meta( $user_id, 'wpseo_job_title', true );
	}

	return $job ? $name . ', ' . sanitize_text_field( $job ) : $name;
}
add_filter( 'the_author', 'estrato_nav_author_byline', 10, 1 );

/**
 * Grid de posts por categoria (home / hubs).
 *
 * @param array<string, mixed> $atts
 * @return string
 */
function estrato_shortcode_category_posts( $atts ) {
	$atts = shortcode_atts(
		array(
			'category' => 'economia',
			'count'    => 4,
			'title'    => '',
		),
		$atts,
		'estrato_category_posts'
	);

	$term = get_term_by( 'slug', sanitize_title( $atts['category'] ), 'category' );
	if ( ! $term || is_wp_error( $term ) ) {
		return '';
	}

	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => (int) $atts['count'],
			'cat'            => (int) $term->term_id,
		)
	);

	if ( ! $posts ) {
		return '';
	}

	$heading = $atts['title'] ? $atts['title'] : $term->name;
	$html    = '<div class="estrato-cat-grid"><h2 class="estrato-cat-grid-title"><a href="' . esc_url( get_category_link( $term ) ) . '">'
		. esc_html( $heading ) . '</a></h2><ul class="estrato-cat-grid-list">';

	foreach ( $posts as $post ) {
		$html .= '<li><a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a></li>';
	}

	$html .= '</ul></div>';
	return $html;
}
add_shortcode( 'estrato_category_posts', 'estrato_shortcode_category_posts' );

/**
 * Sete editorias na home (Money Times / InfoMoney).
 *
 * @return string
 */
function estrato_shortcode_home_editorias() {
	$slugs = array( 'economia', 'mercados', 'negocios', 'financas-pessoais', 'criptomoedas', 'agronegocio', 'mundo' );
	$out   = '<div class="estrato-home-editorias">';
	foreach ( $slugs as $slug ) {
		$out .= estrato_shortcode_category_posts(
			array(
				'category' => $slug,
				'count'    => 3,
			)
		);
	}
	$out .= '</div>';
	return $out;
}
add_shortcode( 'estrato_home_editorias', 'estrato_shortcode_home_editorias' );

/**
 * Mais lidas — últimos 7 dias.
 *
 * @param array<string, mixed> $atts
 * @return string
 */
function estrato_shortcode_mais_lidas( $atts ) {
	$atts = shortcode_atts( array( 'count' => 7 ), $atts, 'estrato_mais_lidas' );

	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => (int) $atts['count'],
			'orderby'        => 'comment_count',
			'order'          => 'DESC',
			'date_query'     => array(
				array(
					'after' => '7 days ago',
				),
			),
		)
	);

	if ( ! $posts ) {
		$posts = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => (int) $atts['count'],
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
	}

	$html = '<div class="estrato-mais-lidas"><h2>Mais lidas</h2><ol>';
	foreach ( $posts as $post ) {
		$html .= '<li><a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a></li>';
	}
	$html .= '</ol></div>';
	return $html;
}
add_shortcode( 'estrato_mais_lidas', 'estrato_shortcode_mais_lidas' );

/**
 * Cotações Frankfurter (Money Times).
 *
 * @return string
 */
function estrato_shortcode_cotacoes() {
	$cached = get_transient( 'estrato_cotacoes_frankfurter' );
	if ( false === $cached ) {
		$response = wp_remote_get(
			'https://api.frankfurter.app/latest?from=USD&to=BRL,EUR,GBP,CHF,JPY',
			array( 'timeout' => 12 )
		);
		if ( is_wp_error( $response ) ) {
			return '<p>Cotações temporariamente indisponíveis.</p>';
		}
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['rates'] ) || ! is_array( $body['rates'] ) ) {
			return '<p>Cotações temporariamente indisponíveis.</p>';
		}
		$cached = $body;
		set_transient( 'estrato_cotacoes_frankfurter', $cached, HOUR_IN_SECONDS );
	}

	$date  = isset( $cached['date'] ) ? $cached['date'] : gmdate( 'Y-m-d' );
	$rates = $cached['rates'];
	$html  = '<div class="estrato-cotacoes"><p><em>Referência USD — Frankfurter/ECB — ' . esc_html( $date ) . '</em></p><table><thead><tr><th>Par</th><th>Taxa</th></tr></thead><tbody>';
	foreach ( $rates as $code => $rate ) {
		$html .= '<tr><td>USD/' . esc_html( $code ) . '</td><td>' . esc_html( number_format_i18n( (float) $rate, 4 ) ) . '</td></tr>';
	}
	$html .= '</tbody></table><p><em>Ibovespa e ativos B3 sujeitos a delay de 15 minutos (B3).</em></p></div>';
	return $html;
}
add_shortcode( 'estrato_cotacoes', 'estrato_shortcode_cotacoes' );

/**
 * Posts recentes para páginas hub.
 *
 * @param array<string, mixed> $atts
 * @return string
 */
function estrato_shortcode_hub_posts( $atts ) {
	$atts = shortcode_atts(
		array(
			'category' => 'economia',
			'count'    => 6,
		),
		$atts,
		'estrato_hub_posts'
	);

	return estrato_shortcode_category_posts(
		array(
			'category' => $atts['category'],
			'count'    => $atts['count'],
			'title'    => 'Últimas notícias',
		)
	);
}
add_shortcode( 'estrato_hub_posts', 'estrato_shortcode_hub_posts' );

/**
 * CSS leve para grids Estrato.
 */
function estrato_nav_visual_styles() {
	if ( ! is_front_page() && ! is_page() ) {
		return;
	}
	echo '<style>.estrato-home-editorias{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1.5rem;margin:2rem 0}'
		. '.estrato-cat-grid-title{font-size:1.1rem;margin:0 0 .5rem}'
		. '.estrato-cat-grid-list{margin:0;padding-left:1.1rem}'
		. '.estrato-cotacoes table{width:100%;border-collapse:collapse}'
		. '.estrato-cotacoes td,.estrato-cotacoes th{border:1px solid #ddd;padding:.5rem}</style>';
}
add_action( 'wp_head', 'estrato_nav_visual_styles', 25 );
