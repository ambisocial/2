<?php
/**
 * FAQ institucional padrão — AEO em matérias, páginas e hubs.
 *
 * Modelo único de credibilidade (fontes, correção, independência, YMYL)
 * + perguntas contextuais por título/editoria. Emite HTML visível + FAQPage.
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ESTRATO_FAQ_MARKER', '<!-- estrato-institutional-faq -->' );

/**
 * @return array<int, array{q:string,a:string}>
 */
function estrato_faq_institutional_base() {
	$blog = get_bloginfo( 'name' );
	$home = home_url( '/' );

	return array(
		array(
			'q' => sprintf( 'Quem edita o conteúdo do %s?', $blog ),
			'a' => sprintf(
				'A cobertura é produzida e revisada pela equipe editorial do %s, com editor-chefe responsável pela linha editorial. Conheça a redação em %s.',
				$blog,
				home_url( '/equipe/' )
			),
		),
		array(
			'q' => 'Quais fontes o Estrato prioriza?',
			'a' => 'Priorizamos fontes primárias (órgãos oficiais, balanços, estudos revisados, documentos públicos) e cruzamos informações antes da publicação, conforme a política editorial.',
		),
		array(
			'q' => 'Como solicitar correção de uma matéria?',
			'a' => sprintf(
				'Envie o pedido pela página de Correções (%s) ou Contato (%s). Erros materiais são corrigidos com registro da atualização.',
				home_url( '/correcoes/' ),
				home_url( '/contato/' )
			),
		),
		array(
			'q' => 'O conteúdo constitui aconselhamento profissional?',
			'a' => 'Não. As matérias têm caráter informativo e jornalístico. Decisões financeiras, de saúde, jurídicas ou educacionais devem considerar profissionais habilitados e a sua situação particular.',
		),
		array(
			'q' => sprintf( 'Onde acompanhar a política editorial do %s?', $blog ),
			'a' => sprintf(
				'Metodologia, ética e transparência estão em %s, %s e %s.',
				home_url( '/metodologia/' ),
				home_url( '/etica-editorial/' ),
				home_url( '/politica-editorial/' )
			),
		),
		array(
			'q' => 'Como o Estrato trata temas sensíveis (YMYL)?',
			'a' => 'Temas que afetam dinheiro, saúde e bem-estar recebem revisão editorial reforçada, disclaimer visível e links para páginas institucionais de metodologia e correções. Portal: ' . $home,
		),
	);
}

/**
 * FAQ contextual a partir do post/página.
 *
 * @param WP_Post $post
 * @return array<int, array{q:string,a:string}>
 */
/**
 * Extrai H2 do conteúdo (texto puro) para FAQ contextual.
 *
 * @param string $content
 * @return array<int, string>
 */
function estrato_faq_extract_h2s( $content ) {
	$heads = array();
	if ( preg_match_all( '/<h2[^>]*>(.*?)<\/h2>/is', (string) $content, $m ) ) {
		foreach ( $m[1] as $raw ) {
			$text = trim( wp_strip_all_tags( html_entity_decode( $raw, ENT_QUOTES, 'UTF-8' ) ) );
			if ( strlen( $text ) < 8 || strlen( $text ) > 120 ) {
				continue;
			}
			if ( preg_match( '/perguntas frequentes|leia também|veja também|relacionad/i', $text ) ) {
				continue;
			}
			$heads[] = $text;
			if ( count( $heads ) >= 5 ) {
				break;
			}
		}
	}
	return $heads;
}

/**
 * Trecho após um H2 (até o próximo heading) para resposta.
 *
 * @param string $content
 * @param string $heading
 * @return string
 */
function estrato_faq_answer_near_h2( $content, $heading ) {
	$quoted = preg_quote( $heading, '/' );
	if ( ! preg_match( '/<h2[^>]*>\s*' . $quoted . '\s*<\/h2>(.*?)(?:<h2|<\/article|$)/is', (string) $content, $m ) ) {
		return '';
	}
	$chunk = wp_trim_words( wp_strip_all_tags( $m[1] ), 42, '…' );
	return strlen( $chunk ) >= 40 ? $chunk : '';
}

function estrato_faq_contextual_for_post( $post ) {
	$title = wp_strip_all_tags( get_the_title( $post ) );
	$area  = 'esta cobertura';
	$cats  = ( 'post' === $post->post_type ) ? get_the_category( $post->ID ) : array();
	if ( ! empty( $cats[0] ) ) {
		$area = html_entity_decode( (string) $cats[0]->name, ENT_QUOTES, 'UTF-8' );
	} elseif ( function_exists( 'estrato_content_category_label' ) ) {
		$area = estrato_content_category_label( '' );
	}

	// Nunca chamar get_the_excerpt() aqui: em páginas sem excerpt o core aplica
	// the_content e reentra neste filtro (stack overflow / HTTP 500).
	$excerpt = trim( wp_strip_all_tags( (string) $post->post_excerpt ) );
	if ( strlen( $excerpt ) < 60 ) {
		$excerpt = wp_trim_words( wp_strip_all_tags( (string) $post->post_content ), 36, '…' );
	}

	$items = array(
		array(
			'q' => sprintf( 'Sobre o que trata “%s”?', wp_trim_words( $title, 10, '…' ) ),
			'a' => $excerpt
				? $excerpt
				: sprintf( 'Esta matéria organiza o contexto essencial de %s para leitores no Brasil, com fontes verificáveis e atualização quando há novos dados.', $area ),
		),
	);

	$h2s = estrato_faq_extract_h2s( (string) $post->post_content );
	foreach ( $h2s as $h2 ) {
		$ans = estrato_faq_answer_near_h2( (string) $post->post_content, $h2 );
		$items[] = array(
			'q' => ( '?' === substr( $h2, -1 ) ) ? $h2 : ( 'O que a matéria explica sobre “' . wp_trim_words( $h2, 12, '…' ) . '”?' ),
			'a' => $ans
				? $ans
				: sprintf( 'A seção “%s” organiza o contexto de %s com fatos e implicações práticas destacados pela redação.', $h2, $area ),
		);
	}

	if ( count( $h2s ) < 2 ) {
		$items[] = array(
			'q' => sprintf( 'Por que %s importa agora?', $area ),
			'a' => sprintf(
				'O tema impacta decisões e debates cotidianos ligados a %s. A redação destaca fatos, datas e implicações práticas sem substituir orientação profissional personalizada.',
				$area
			),
		);
	}

	return array_slice( $items, 0, 5 );
}

/**
 * Conjunto final (máx. 8) para um post/página.
 *
 * @param WP_Post|null $post
 * @return array<int, array{q:string,a:string}>
 */
function estrato_faq_build_items( $post = null ) {
	$items = estrato_faq_institutional_base();
	if ( $post instanceof WP_Post ) {
		$items = array_merge( estrato_faq_contextual_for_post( $post ), $items );
	}
	return array_slice( $items, 0, 8 );
}

/**
 * HTML do bloco FAQ.
 *
 * @param array<int, array{q:string,a:string}> $items
 * @return string
 */
function estrato_faq_render_html( $items ) {
	if ( count( $items ) < 3 ) {
		return '';
	}
	$html  = ESTRATO_FAQ_MARKER . "\n";
	$html .= '<section class="estrato-faq" id="perguntas-frequentes" aria-labelledby="estrato-faq-title">';
	$html .= '<h2 id="estrato-faq-title" class="estrato-display">Perguntas frequentes</h2>';
	$html .= '<p class="estrato-faq__lead">Respostas institucionais sobre esta cobertura, fontes e limites editoriais.</p>';
	$html .= '<dl class="estrato-faq__list">';
	foreach ( $items as $item ) {
		$html .= '<div class="estrato-faq__item">';
		$html .= '<dt>' . esc_html( $item['q'] ) . '</dt>';
		$html .= '<dd>' . esc_html( $item['a'] ) . '</dd>';
		$html .= '</div>';
	}
	$html .= '</dl></section>';
	return $html;
}

/**
 * JSON-LD FAQPage.
 *
 * @param array<int, array{q:string,a:string}> $items
 */
function estrato_faq_print_schema( $items ) {
	if ( count( $items ) < 3 ) {
		return;
	}
	$entities = array();
	foreach ( $items as $faq ) {
		$entities[] = array(
			'@type'          => 'Question',
			'name'           => $faq['q'],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $faq['a'],
			),
		);
	}
	$schema = array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => $entities,
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}

/**
 * Injeta FAQ no conteúdo de posts e páginas (uma vez).
 *
 * @param string $content
 * @return string
 */
function estrato_faq_append_to_content( $content ) {
	static $guard = false;
	if ( $guard ) {
		return $content;
	}
	if ( is_admin() || is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return $content;
	}
	if ( ! is_singular( array( 'post', 'page' ) ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	if ( false !== strpos( $content, 'estrato-faq' ) || false !== strpos( $content, ESTRATO_FAQ_MARKER ) ) {
		return $content;
	}
	// Evita FAQ duplicado em páginas utilitárias e hubs /tudo-sobre/ (já têm FAQ temático).
	if ( is_page( array( 'busca', 'search', 'login', 'tudo-sobre' ) ) ) {
		return $content;
	}

	$post = get_post();
	if ( ! $post ) {
		return $content;
	}
	if ( is_page() && $post->post_parent ) {
		$parent_slug = get_post_field( 'post_name', $post->post_parent );
		if ( 'tudo-sobre' === $parent_slug ) {
			return $content;
		}
	}

	$guard = true;
	$html  = estrato_faq_render_html( estrato_faq_build_items( $post ) );
	$guard = false;
	return $html ? ( $content . "\n" . $html ) : $content;
}
add_filter( 'the_content', 'estrato_faq_append_to_content', 28 );

/**
 * Schema FAQ em singles/páginas (além dos hubs /tudo-sobre/).
 */
function estrato_faq_output_schema_head() {
	if ( is_singular( array( 'post', 'page' ) ) ) {
		$post = get_queried_object();
		if ( $post instanceof WP_Post ) {
			if ( is_page() && $post->post_parent ) {
				$parent_slug = get_post_field( 'post_name', $post->post_parent );
				if ( 'tudo-sobre' === $parent_slug || 'tudo-sobre' === $post->post_name ) {
					return; // FAQPage temático já em seo-aeo.php
				}
			}
			estrato_faq_print_schema( estrato_faq_build_items( $post ) );
		}
		return;
	}
	if ( is_category() || is_tag() || is_author() ) {
		$blog  = get_bloginfo( 'name' );
		$title = single_term_title( '', false );
		if ( ! $title && is_author() ) {
			$author = get_queried_object();
			$title  = $author && ! empty( $author->display_name ) ? $author->display_name : 'Autor';
		}
		$items = array_merge(
			array(
				array(
					'q' => sprintf( 'O que encontro em %s no %s?', $title, $blog ),
					'a' => sprintf( 'Arquivo editorial com matérias, contexto e atualizações sobre %s, produzidas pela redação do %s.', $title, $blog ),
				),
			),
			estrato_faq_institutional_base()
		);
		estrato_faq_print_schema( array_slice( $items, 0, 7 ) );
	}
}
add_action( 'wp_head', 'estrato_faq_output_schema_head', 7 );

/**
 * CSS do bloco FAQ.
 */
function estrato_faq_styles() {
	if ( ! is_singular( array( 'post', 'page' ) ) && ! is_category() && ! is_author() ) {
		return;
	}
	$css = '.estrato-faq{margin:2.5rem 0 1.5rem;padding-top:1.5rem;border-top:1px solid var(--estrato-line,#e5e5e5)}'
		. '.estrato-faq__lead{opacity:.85;margin:.35rem 0 1.25rem;max-width:42rem}'
		. '.estrato-faq__list{margin:0}'
		. '.estrato-faq__item{margin:0 0 1.1rem;padding-bottom:1rem;border-bottom:1px solid var(--estrato-line,#eee)}'
		. '.estrato-faq__item dt{font-weight:700;margin:0 0 .35rem;font-family:var(--estrato-font-display,inherit)}'
		. '.estrato-faq__item dd{margin:0;line-height:1.55;opacity:.92}';
	if ( function_exists( 'estrato_perf_style_add' ) ) {
		estrato_perf_style_add( 'estrato-faq', $css, 'main' );
	} else {
		echo '<style id="estrato-faq">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_head', 'estrato_faq_styles', 21 );

/**
 * @return int
 */
function estrato_regression_faq_marker_present() {
	return function_exists( 'estrato_faq_institutional_base' ) ? count( estrato_faq_institutional_base() ) : 0;
}
