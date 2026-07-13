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
/**
 * Fix pós-auditoria visual 2026-07-13 (P1 byline duplicada):
 * o filter global `the_author` era aplicado em qualquer chamada de
 * `get_the_author()` e `the_author()`, incluindo a byline do template
 * single, onde o próprio código já concatena `$author . ', ' . $job`.
 * Resultado: duas cópias de `, Repórter de X · X` na tela.
 *
 * Mantemos a função (usada como helper em widgets que sabem chamar
 * `estrato_nav_author_byline` explicitamente), mas removemos o hook global.
 */
if ( ! function_exists( 'estrato_author_byline_string' ) ) {
	/**
	 * @param int $user_id
	 * @return string
	 */
	function estrato_author_byline_string( $user_id ) {
		$user = $user_id ? get_userdata( (int) $user_id ) : null;
		if ( ! $user ) {
			return '';
		}
		return estrato_nav_author_byline( $user->display_name );
	}
}

/**
 * PressGrid renderiza custom_html só quando id === custom_html e sem do_shortcode.
 *
 * @param mixed $sections
 * @return mixed
 */
function estrato_pressgrid_layout_shortcodes( $sections ) {
	if ( ! is_array( $sections ) ) {
		return $sections;
	}
	foreach ( $sections as &$section ) {
		if ( empty( $section['custom_html'] ) || ! is_string( $section['custom_html'] ) ) {
			continue;
		}
		if ( strpos( $section['custom_html'], '[' ) !== false ) {
			$section['custom_html'] = do_shortcode( $section['custom_html'] );
		}
	}
	return $sections;
}
add_filter( 'option_pressgrid_layout_sections', 'estrato_pressgrid_layout_shortcodes', 20 );

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

	$posts = function_exists( 'estrato_retention_get_trending_posts' )
		? estrato_retention_get_trending_posts( (int) $atts['count'], 7 )
		: get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => (int) $atts['count'],
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

	if ( ! $posts ) {
		return '';
	}

	$html = '<div class="estrato-mais-lidas"><h2>Mais lidas</h2><ol>';
	$n    = 1;
	foreach ( $posts as $post ) {
		$views = function_exists( 'estrato_retention_get_trending_posts' )
			? (int) get_post_meta( $post->ID, 'estrato_views', true )
			: 0;
		$html .= '<li><span class="estrato-mais-lidas__n">' . (int) $n . '</span> ';
		$html .= '<a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a>';
		if ( $views > 0 ) {
			$html .= ' <span class="estrato-caption">(' . (int) $views . ')</span>';
		}
		$html .= '</li>';
		++$n;
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

	$brl_cached = get_transient( 'estrato_cotacoes_brl_cross' );
	if ( false === $brl_cached ) {
		$brl_resp = wp_remote_get(
			'https://api.frankfurter.app/latest?from=BRL&to=USD,EUR,GBP',
			array( 'timeout' => 12 )
		);
		if ( ! is_wp_error( $brl_resp ) ) {
			$brl_body = json_decode( wp_remote_retrieve_body( $brl_resp ), true );
			if ( ! empty( $brl_body['rates'] ) && is_array( $brl_body['rates'] ) ) {
				$brl_cached = $brl_body;
				set_transient( 'estrato_cotacoes_brl_cross', $brl_cached, HOUR_IN_SECONDS );
			}
		}
	}

	$crypto = get_transient( 'estrato_cotacoes_crypto' );
	if ( false === $crypto ) {
		$crypto_resp = wp_remote_get(
			'https://api.coingecko.com/api/v3/simple/price?ids=bitcoin,ethereum&vs_currencies=usd,brl',
			array( 'timeout' => 12 )
		);
		if ( ! is_wp_error( $crypto_resp ) ) {
			$crypto_body = json_decode( wp_remote_retrieve_body( $crypto_resp ), true );
			if ( is_array( $crypto_body ) && ! empty( $crypto_body['bitcoin'] ) ) {
				$crypto = $crypto_body;
				set_transient( 'estrato_cotacoes_crypto', $crypto, 15 * MINUTE_IN_SECONDS );
			}
		}
	}

	$date  = isset( $cached['date'] ) ? $cached['date'] : gmdate( 'Y-m-d' );
	$rates = $cached['rates'];
	$html  = '<div class="estrato-cotacoes"><p><em>Referência USD — Frankfurter/ECB — ' . esc_html( $date ) . '</em></p>'
		. '<table><thead><tr><th>Par</th><th>Taxa</th></tr></thead><tbody>';
	foreach ( $rates as $code => $rate ) {
		$html .= '<tr><td>USD/' . esc_html( $code ) . '</td><td>' . esc_html( number_format_i18n( (float) $rate, 4 ) ) . '</td></tr>';
	}
	$html .= '</tbody></table>';

	if ( is_array( $brl_cached ) && ! empty( $brl_cached['rates'] ) ) {
		$html .= '<h3>Cruzamento a partir do real</h3><table><thead><tr><th>Par</th><th>Taxa</th></tr></thead><tbody>';
		foreach ( $brl_cached['rates'] as $code => $rate ) {
			$html .= '<tr><td>BRL/' . esc_html( $code ) . '</td><td>' . esc_html( number_format_i18n( (float) $rate, 4 ) ) . '</td></tr>';
		}
		$html .= '</tbody></table>';
	}

	if ( is_array( $crypto ) ) {
		$html .= '<h3>Cripto (CoinGecko)</h3><table><thead><tr><th>Ativo</th><th>USD</th><th>BRL</th></tr></thead><tbody>';
		foreach ( array( 'bitcoin' => 'Bitcoin', 'ethereum' => 'Ethereum' ) as $id => $label ) {
			if ( empty( $crypto[ $id ] ) ) {
				continue;
			}
			$html .= '<tr><td>' . esc_html( $label ) . '</td><td>'
				. esc_html( number_format_i18n( (float) ( $crypto[ $id ]['usd'] ?? 0 ), 2 ) )
				. '</td><td>' . esc_html( number_format_i18n( (float) ( $crypto[ $id ]['brl'] ?? 0 ), 2 ) )
				. '</td></tr>';
		}
		$html .= '</tbody></table>';
	}

	$html .= '<p><em>Ibovespa e ativos B3 sujeitos a delay de 15 minutos (B3). Cripto: referência CoinGecko, volátil.</em></p></div>';
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
 * Catálogo da rede Estrato (cross-linking Sprint 15).
 *
 * @return array<int, array{id:string,name:string,url:string,tagline:string}>
 */
function estrato_nav_network_catalog() {
	return array(
		array(
			'id'      => 'estrato-finance',
			'name'    => 'Estrato',
			'url'     => 'https://estrato.cc/',
			'tagline' => 'Economia, mercados e finanças',
		),
		array(
			'id'      => 'estrato-mind',
			'name'    => 'Estrato Mente',
			'url'     => 'https://mente.estrato.cc/',
			'tagline' => 'Conhecimento e desenvolvimento pessoal',
		),
		array(
			'id'      => 'estrato-lifestyle',
			'name'    => 'Estrato Lifestyle',
			'url'     => 'https://lifestyle.estrato.cc/',
			'tagline' => 'Estilos de vida e hobbies',
		),
		array(
			'id'      => 'estrato-science',
			'name'    => 'Estrato Science',
			'url'     => 'https://science.estrato.cc/',
			'tagline' => 'Ciência, tecnologia e futuro',
		),
		array(
			'id'      => 'estrato-sustain',
			'name'    => 'Estrato Sustain',
			'url'     => 'https://sustain.estrato.cc/',
			'tagline' => 'Sustentabilidade e economia alternativa',
		),
		array(
			'id'      => 'estrato-culture',
			'name'    => 'Estrato Culture',
			'url'     => 'https://culture.estrato.cc/',
			'tagline' => 'Cultura pop e narrativas de nicho',
		),
	);
}

/**
 * Resolve portal_id atual.
 *
 * @return string
 */
function estrato_nav_current_portal_id() {
	$portal = getenv( 'ESTRATO_PORTAL' ) ?: '';
	if ( $portal ) {
		return sanitize_key( $portal );
	}
	$host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
	$map  = array(
		'estrato.cc'           => 'estrato-finance',
		'mente.estrato.cc'     => 'estrato-mind',
		'lifestyle.estrato.cc' => 'estrato-lifestyle',
		'science.estrato.cc'   => 'estrato-science',
		'sustain.estrato.cc'   => 'estrato-sustain',
		'culture.estrato.cc'   => 'estrato-culture',
	);
	return $map[ $host ] ?? 'estrato-finance';
}

/**
 * HTML do widget "Na rede Estrato".
 *
 * @return string
 */
function estrato_nav_network_footer_html() {
	$current = estrato_nav_current_portal_id();
	$html    = '<div class="estrato-network-footer"><p><strong>Na rede Estrato</strong></p><ul>';
	foreach ( estrato_nav_network_catalog() as $node ) {
		if ( $node['id'] === $current ) {
			continue;
		}
		$html .= '<li><a href="' . esc_url( $node['url'] ) . '">' . esc_html( $node['name'] )
			. '</a> <span class="estrato-network-tagline">— ' . esc_html( $node['tagline'] ) . '</span></li>';
	}
	$html .= '</ul></div>';
	return $html;
}

/**
 * HTML do hub da rede na home (finance — S26).
 *
 * @return string
 */
function estrato_nav_network_hub_html() {
	$html = '<div class="estrato-network-hub"><p>Explore os portais especializados da rede Estrato:</p><ul class="estrato-network-hub-grid">';
	foreach ( estrato_nav_network_catalog() as $node ) {
		if ( 'estrato-finance' === $node['id'] ) {
			continue;
		}
		$html .= '<li><a href="' . esc_url( $node['url'] ) . '"><strong>' . esc_html( $node['name'] )
			. '</strong></a><br><span class="estrato-network-tagline">' . esc_html( $node['tagline'] ) . '</span></li>';
	}
	$html .= '</ul></div>';
	return $html;
}

/**
 * Links de editorias no footer.
 *
 * @return string
 */
function estrato_nav_footer_editorias() {
	$terms = get_terms(
		array(
			'taxonomy'   => 'category',
			'parent'     => 0,
			'hide_empty' => false,
			'number'     => 8,
		)
	);
	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return '';
	}
	$links = array();
	foreach ( $terms as $term ) {
		$legacy = array( 'politica', 'tecnologia', 'brasil', 'sem-categoria' );
		if ( in_array( $term->slug, $legacy, true ) ) {
			continue;
		}
		$links[] = '<a href="' . esc_url( get_category_link( $term ) ) . '">' . esc_html( $term->name ) . '</a>';
	}
	return implode( ' · ', array_slice( $links, 0, 7 ) );
}

/**
 * Mapa de cross-links entre portais (finance ↔ satélites).
 *
 * @return array<string, array<string, array<int, array{url:string,title:string}>>>
 */
function estrato_nav_crosslink_map() {
	return array(
		'estrato-finance' => array(
			'selic'       => array(
				array(
					'url'   => 'https://mente.estrato.cc/tudo-sobre/financas/',
					'title' => 'Finanças comportamentais — Estrato Mente',
				),
			),
			'cripto'      => array(
				array(
					'url'   => 'https://science.estrato.cc/tudo-sobre/ia/',
					'title' => 'IA e segurança — Estrato Science',
				),
			),
			'agronegocio' => array(
				array(
					'url'   => 'https://sustain.estrato.cc/tudo-sobre/agro/',
					'title' => 'Agro sustentável — Estrato Sustain',
				),
			),
		),
		'estrato-mind'    => array(
			'financas' => array(
				array(
					'url'   => 'https://estrato.cc/tudo-sobre/selic/',
					'title' => 'Tudo sobre Selic — Estrato',
				),
				array(
					'url'   => 'https://estrato.cc/category/financas-pessoais/',
					'title' => 'Finanças pessoais — Estrato',
				),
			),
		),
		'estrato-science' => array(
			'ia' => array(
				array(
					'url'   => 'https://estrato.cc/tudo-sobre/cripto/',
					'title' => 'Criptomoedas — Estrato',
				),
			),
		),
		'estrato-sustain' => array(
			'agro' => array(
				array(
					'url'   => 'https://estrato.cc/tudo-sobre/agronegocio/',
					'title' => 'Agronegócio — Estrato',
				),
			),
		),
	);
}

/**
 * Bloco HTML de cross-links para página hub.
 *
 * @param string $hub_slug
 * @return string
 */
function estrato_nav_render_crosslinks_block( $hub_slug ) {
	$portal = estrato_nav_current_portal_id();
	$map    = estrato_nav_crosslink_map();
	if ( empty( $map[ $portal ][ $hub_slug ] ) ) {
		return '';
	}
	$html = '<div class="estrato-network-crosslinks"><h2>Na rede Estrato</h2><ul>';
	foreach ( $map[ $portal ][ $hub_slug ] as $link ) {
		$html .= '<li><a href="' . esc_url( $link['url'] ) . '">' . esc_html( $link['title'] ) . '</a></li>';
	}
	$html .= '</ul></div>';
	return $html;
}

/**
 * CSS leve para grids Estrato.
 */
function estrato_nav_visual_styles() {
	if ( ! is_front_page() && ! is_page() ) {
		return;
	}
	$css = '.estrato-home-editorias{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1.5rem;margin:2rem 0}'
		. '.estrato-cat-grid-title{font-size:1.1rem;margin:0 0 .5rem}'
		. '.estrato-cat-grid-list{margin:0;padding-left:1.1rem}'
		. '.estrato-cotacoes table{width:100%;border-collapse:collapse}'
		. '.estrato-cotacoes td,.estrato-cotacoes th{border:1px solid #ddd;padding:.5rem}';
	if ( function_exists( 'estrato_perf_style_add' ) ) {
		estrato_perf_style_add( 'estrato-nav-visual', $css, 'main' );
	} else {
		echo '<style id="estrato-nav-visual">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_head', 'estrato_nav_visual_styles', 20 );
