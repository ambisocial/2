<?php
/**
 * Qualidade de conteúdo — Sprint 4 (word count, AEO, regression, noindex thin).
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ESTRATO_MIN_PUBLISH_WORDS', 200 );
define( 'ESTRATO_TARGET_WORDS', 300 );
define( 'ESTRATO_AEO_MARKER', '<!-- estrato-aeo -->' );

/**
 * @param string $html
 * @return int
 */
function estrato_content_word_count( $html ) {
	$text = wp_strip_all_tags( (string) $html );
	$text = preg_replace( '/\s+/u', ' ', trim( $text ) );
	if ( '' === $text ) {
		return 0;
	}
	$words = preg_split( '/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY );
	return is_array( $words ) ? count( $words ) : 0;
}

/**
 * @param int $post_id
 * @return int
 */
function estrato_content_post_word_count( $post_id ) {
	return estrato_content_word_count( get_post_field( 'post_content', $post_id ) );
}

/**
 * @param string $category_slug
 * @return string
 */
function estrato_content_category_label( $category_slug ) {
	$labels = array(
		'economia'          => 'economia brasileira',
		'mercados'          => 'mercados financeiros',
		'negocios'          => 'negócios e empresas',
		'financas-pessoais' => 'finanças pessoais',
		'criptomoedas'      => 'criptoativos',
		'agronegocio'       => 'agronegócio',
		'mundo'             => 'economia internacional',
	);
	return $labels[ $category_slug ] ?? 'economia e mercados';
}

/**
 * Blocos AEO (resumo, contexto, FAQ) para enriquecer posts finos.
 *
 * @param string $title
 * @param string $excerpt
 * @param string $content
 * @param string $category_slug
 * @return string
 */
function estrato_content_build_aeo_blocks( $title, $excerpt, $content, $category_slug = '' ) {
	$title   = wp_strip_all_tags( $title );
	$excerpt = wp_strip_all_tags( $excerpt );
	$plain   = wp_strip_all_tags( $content );
	$area    = estrato_content_category_label( $category_slug );

	$resumo = $excerpt;
	if ( strlen( $resumo ) < 80 && strlen( $plain ) > 80 ) {
		$resumo = wp_trim_words( $plain, 40, '…' );
	}
	if ( strlen( $resumo ) < 40 ) {
		$resumo = sprintf(
			'Esta matéria reúne o contexto essencial sobre %s, com foco em impactos para investidores, empresas e consumidores no Brasil.',
			$area
		);
	}

	$faq = array(
		array(
			'q' => sprintf( 'Por que %s está em destaque?', wp_trim_words( $title, 8, '…' ) ),
			'a' => sprintf(
				'O tema dialoga diretamente com tendências de %s e pode influenciar decisões de investimento, custos e expectativas de inflação no curto prazo.',
				$area
			),
		),
		array(
			'q' => 'Quem deve acompanhar esta notícia?',
			'a' => 'Investidores, gestores, empreendedores e leitores que acompanham indicadores macroeconômicos e movimentos setoriais no Brasil.',
		),
		array(
			'q' => 'Como o Estrato trata esta cobertura?',
			'a' => 'A redação cruza fontes primárias, dados públicos e contexto de mercado antes da publicação, conforme nossa política editorial.',
		),
	);

	$faq_html = '';
	foreach ( $faq as $item ) {
		$faq_html .= '<h3>' . esc_html( $item['q'] ) . '</h3><p>' . esc_html( $item['a'] ) . '</p>';
	}

	return ESTRATO_AEO_MARKER . "\n"
		. '<div class="estrato-aeo-resumo"><h2>O que você precisa saber</h2><p><strong>'
		. esc_html( $resumo ) . '</strong></p></div>'
		. '<h2>Contexto de mercado</h2>'
		. '<p>'
		. esc_html(
			sprintf(
				'Em um cenário de %s, movimentos como o descrito em “%s” ajudam a calibrar expectativas sobre juros, câmbio e fluxo de capital. Analistas costumam cruzar estes eventos com dados do Banco Central, IBGE e B3 para avaliar o desdobramento nas próximas semanas.',
				$area,
				wp_trim_words( $title, 12, '…' )
			)
		)
		. '</p>'
		. '<h2>Impacto prático</h2>'
		. '<p>'
		. esc_html(
			sprintf(
				'Para o investidor de varejo e para empresas expostas a %s, o efeito mais imediato costuma aparecer em precificação de ativos, custo de capital e revisão de projeções. Acompanhar comunicados oficiais e a reação do mercado nas sessões seguintes ajuda a separar ruído de mudança estrutural de cenário.',
				$area
			)
		)
		. '</p>'
		. '<h2>Perguntas frequentes</h2>' . $faq_html;
}

/**
 * @param int $post_id
 * @param int $limit
 * @return array<int, array{id:int,title:string,url:string}>
 */
function estrato_content_find_related_posts( $post_id, $limit = 2 ) {
	$cats = wp_get_post_categories( $post_id );
	$args = array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => $limit,
		'post__not_in'   => array( $post_id ),
		'orderby'        => 'date',
		'order'          => 'DESC',
	);
	if ( $cats ) {
		$args['category__in'] = $cats;
	}

	$related = array();
	foreach ( get_posts( $args ) as $post ) {
		$related[] = array(
			'id'    => $post->ID,
			'title' => get_the_title( $post ),
			'url'   => get_permalink( $post ),
		);
	}
	return $related;
}

/**
 * Injeta até 3 links internos (categoria + relacionados).
 *
 * @param string $content
 * @param int    $post_id
 * @return string
 */
function estrato_content_inject_internal_links( $content, $post_id ) {
	if ( false !== strpos( $content, 'estrato-internal-links' ) ) {
		return $content;
	}

	$links = array();
	$cats  = get_the_category( $post_id );
	if ( ! empty( $cats[0] ) ) {
		$links[] = array(
			'url'   => get_category_link( $cats[0]->term_id ),
			'title' => 'Mais notícias de ' . $cats[0]->name,
		);
	}

	foreach ( estrato_content_find_related_posts( $post_id, 2 ) as $rel ) {
		$links[] = array(
			'url'   => $rel['url'],
			'title' => $rel['title'],
		);
	}

	if ( count( $links ) < 3 ) {
		$links[] = array(
			'url'   => home_url( '/category/economia/' ),
			'title' => 'Economia no Estrato',
		);
	}

	$links = array_slice( $links, 0, 3 );
	$html  = '<div class="estrato-internal-links"><h2>Leia também</h2><ul>';
	foreach ( $links as $link ) {
		$html .= '<li><a href="' . esc_url( $link['url'] ) . '">' . esc_html( $link['title'] ) . '</a></li>';
	}
	$html .= '</ul></div>';

	return $content . "\n" . $html;
}

/**
 * Parágrafo extra para posts entre 200–299 palavras.
 *
 * @param string $title
 * @param string $category_slug
 * @return string
 */
function estrato_content_build_extension_block( $title, $category_slug = '' ) {
	$area = estrato_content_category_label( $category_slug );
	return '<h2>Análise complementar</h2><p>'
		. esc_html(
			sprintf(
				'O desdobramento de “%s” reforça a importância de monitorar indicadores de %s nas próximas divulgações. Gestores costumam revisar exposição a juros, câmbio e setores cíclicos quando notícias como esta ganham tração na imprensa e nas redes de distribuição de research.',
				wp_trim_words( $title, 10, '…' ),
				$area
			)
		)
		. '</p><p>'
		. esc_html(
			'No Estrato, atualizamos esta cobertura quando surgem novos dados oficiais ou movimentos relevantes de mercado. Consulte nossa seção de mercados e a política editorial para entender critérios de atualização e correção.'
		)
		. '</p>';
}

/**
 * Enriquece post publicado abaixo da meta de palavras.
 *
 * @param int $post_id
 * @return array{updated:bool,words:int,drafted:bool}
 */
function estrato_content_enrich_post( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post || 'post' !== $post->post_type ) {
		return array(
			'updated' => false,
			'words'   => 0,
			'drafted' => false,
		);
	}

	$content = $post->post_content;
	$words   = estrato_content_word_count( $content );

	if ( $words >= ESTRATO_TARGET_WORDS
		&& false !== strpos( $content, ESTRATO_AEO_MARKER )
		&& false !== strpos( $content, 'estrato-internal-links' ) ) {
		return array(
			'updated' => false,
			'words'   => $words,
			'drafted' => false,
		);
	}

	$cat_slug = '';
	$cats     = get_the_category( $post_id );
	if ( ! empty( $cats[0] ) ) {
		$cat_slug = $cats[0]->slug;
	}

	if ( $words < ESTRATO_TARGET_WORDS && false === strpos( $content, ESTRATO_AEO_MARKER ) ) {
		$content .= "\n" . estrato_content_build_aeo_blocks(
			$post->post_title,
			$post->post_excerpt,
			$content,
			$cat_slug
		);
	}

	$content = estrato_content_inject_internal_links( $content, $post_id );
	$words   = estrato_content_word_count( $content );

	if ( $words < ESTRATO_TARGET_WORDS && $words >= ESTRATO_MIN_PUBLISH_WORDS ) {
		$content .= "\n" . estrato_content_build_extension_block( $post->post_title, $cat_slug );
		$words    = estrato_content_word_count( $content );
	}

	$status = $post->post_status;
	$drafted = false;
	if ( $words < ESTRATO_MIN_PUBLISH_WORDS && 'publish' === $status ) {
		$status  = 'draft';
		$drafted = true;
	}

	wp_update_post(
		array(
			'ID'           => $post_id,
			'post_content' => $content,
			'post_status'  => $status,
		)
	);

	estrato_content_sync_yoast_index( $post_id, $words );

	return array(
		'updated' => true,
		'words'   => $words,
		'drafted' => $drafted,
	);
}

/**
 * @param int $post_id
 * @param int $words
 */
function estrato_content_sync_yoast_index( $post_id, $words ) {
	if ( $words < ESTRATO_MIN_PUBLISH_WORDS ) {
		update_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', '1' );
	} else {
		delete_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex' );
	}
}

/**
 * @param int $post_id
 */
function estrato_content_on_publish( $post_id ) {
	if ( defined( 'ESTRATO_ENRICHING' ) && ESTRATO_ENRICHING ) {
		return;
	}
	$post = get_post( $post_id );
	if ( ! $post || 'post' !== $post->post_type ) {
		return;
	}

	$words = estrato_content_post_word_count( $post_id );
	estrato_content_sync_yoast_index( $post_id, $words );

	if ( $words < ESTRATO_MIN_PUBLISH_WORDS && 'publish' === $post->post_status ) {
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'draft',
			)
		);
	}
}
add_action( 'save_post_post', 'estrato_content_on_publish', 99 );

/**
 * @return int
 */
function estrato_regression_thin_posts() {
	$count = 0;
	foreach ( get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	) as $post_id ) {
		if ( estrato_content_post_word_count( $post_id ) < ESTRATO_MIN_PUBLISH_WORDS ) {
			++$count;
		}
	}
	return $count;
}

/**
 * @return float
 */
function estrato_regression_word_ratio() {
	$total = 0;
	$ok    = 0;
	foreach ( get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	) as $post_id ) {
		++$total;
		if ( estrato_content_post_word_count( $post_id ) >= ESTRATO_TARGET_WORDS ) {
			++$ok;
		}
	}
	if ( 0 === $total ) {
		return 1.0;
	}
	return round( $ok / $total, 4 );
}

/**
 * @return int
 */
function estrato_regression_posts_without_thumbnail() {
	$count = 0;
	foreach ( get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	) as $post_id ) {
		if ( ! has_post_thumbnail( $post_id ) ) {
			++$count;
		}
	}
	return $count;
}

/**
 * @return int
 */
function estrato_regression_pipeline_without_source() {
	global $wpdb;

	$sql = "
		SELECT COUNT(p.ID)
		FROM {$wpdb->posts} p
		INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_estrato_pipeline_id'
		LEFT JOIN {$wpdb->postmeta} src ON src.post_id = p.ID AND src.meta_key = '_estrato_source_url'
		WHERE p.post_type = 'post'
		  AND p.post_status = 'publish'
		  AND (src.meta_value IS NULL OR TRIM(src.meta_value) = '')
	";

	return (int) $wpdb->get_var( $sql );
}
