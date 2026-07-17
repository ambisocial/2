<?php
/**
 * YMYL máximo — todos os portais da rede Estrato.
 *
 * Disclaimer visível, schema reviewedBy (editor-chefe), links de
 * metodologia/ética/correções e reforço em saúde/finanças.
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Portais com YMYL alto (dinheiro / saúde / educação).
 *
 * @return array<int, string>
 */
function estrato_ymyl_high_portals() {
	return array(
		'estrato-finance',
		'estrato-saude',
		'estrato-educacao',
		'estrato-mind',
		'estrato-politica',
		'estrato-tech',
	);
}

/**
 * @return bool
 */
function estrato_ymyl_is_high_portal() {
	$portal = function_exists( 'estrato_nav_current_portal_id' )
		? estrato_nav_current_portal_id()
		: 'estrato-finance';
	return in_array( $portal, estrato_ymyl_high_portals(), true );
}

/**
 * Texto do disclaimer por portal.
 *
 * @return string
 */
function estrato_ymyl_disclaimer_text() {
	$portal = function_exists( 'estrato_nav_current_portal_id' )
		? estrato_nav_current_portal_id()
		: 'estrato-finance';
	$blog   = get_bloginfo( 'name' );

	if ( 'estrato-saude' === $portal ) {
		return 'Conteúdo informativo de saúde do ' . $blog . '. Não substitui consulta, diagnóstico ou tratamento com profissional de saúde habilitado. Em emergência, procure serviço médico.';
	}
	if ( 'estrato-finance' === $portal ) {
		return 'Conteúdo jornalístico sobre economia e investimentos do ' . $blog . '. Não constitui recomendação de compra/venda, consultoria de valores mobiliários nem planejamento financeiro personalizado.';
	}
	if ( 'estrato-educacao' === $portal ) {
		return 'Conteúdo informativo sobre educação e carreira do ' . $blog . '. Não substitui orientação acadêmica, jurídica ou profissional personalizada.';
	}
	if ( 'estrato-mind' === $portal ) {
		return 'Conteúdo informativo sobre mente e desenvolvimento pessoal do ' . $blog . '. Não substitui acompanhamento psicológico ou médico.';
	}

	return 'Conteúdo jornalístico do ' . $blog . ' (rede Estrato). Tem caráter informativo e não substitui aconselhamento profissional personalizado em decisões que afetem saúde, finanças ou direitos.';
}

/**
 * Bloco HTML YMYL no single.
 *
 * @param string $content
 * @return string
 */
function estrato_ymyl_append_disclaimer( $content ) {
	if ( is_admin() || is_feed() || ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	if ( false !== strpos( $content, 'estrato-ymyl-box' ) ) {
		return $content;
	}

	$map       = get_option( defined( 'ESTRATO_STAFF_OPTION' ) ? ESTRATO_STAFF_OPTION : 'estrato_editorial_staff_map', array() );
	$editor_id = ! empty( $map['editor_id'] ) ? (int) $map['editor_id'] : 0;
	$editor    = $editor_id ? get_userdata( $editor_id ) : null;

	$html  = '<aside class="estrato-ymyl-box" aria-label="Aviso YMYL">';
	$html .= '<p class="estrato-ymyl-box__label">Transparência editorial</p>';
	$html .= '<p>' . esc_html( estrato_ymyl_disclaimer_text() ) . '</p>';
	$html .= '<p class="estrato-ymyl-box__links">';
	$html .= '<a href="' . esc_url( home_url( '/metodologia/' ) ) . '">Metodologia</a> · ';
	$html .= '<a href="' . esc_url( home_url( '/etica-editorial/' ) ) . '">Ética</a> · ';
	$html .= '<a href="' . esc_url( home_url( '/correcoes/' ) ) . '">Correções</a> · ';
	$html .= '<a href="' . esc_url( home_url( '/equipe/' ) ) . '">Equipe</a>';
	if ( $editor ) {
		$html .= ' · Revisado pela mesa: <a href="' . esc_url( get_author_posts_url( $editor_id ) ) . '">'
			. esc_html( $editor->display_name ) . '</a>';
	}
	$html .= '</p></aside>';

	return $content . "\n" . $html;
}
add_filter( 'the_content', 'estrato_ymyl_append_disclaimer', 30 );

/**
 * reviewedBy + disclaimers no NewsArticle (Yoast).
 *
 * @param array<string,mixed> $data
 * @return array<string,mixed>
 */
function estrato_ymyl_schema_article( $data ) {
	if ( ! is_array( $data ) || ! is_singular( 'post' ) ) {
		return $data;
	}

	$data['isAccessibleForFree'] = true;
	$data['inLanguage']            = 'pt-BR';

	$map       = get_option( defined( 'ESTRATO_STAFF_OPTION' ) ? ESTRATO_STAFF_OPTION : 'estrato_editorial_staff_map', array() );
	$editor_id = ! empty( $map['editor_id'] ) ? (int) $map['editor_id'] : 0;
	if ( $editor_id ) {
		$editor = get_userdata( $editor_id );
		if ( $editor ) {
			$data['reviewedBy'] = array(
				'@type'    => 'Person',
				'name'     => $editor->display_name,
				'jobTitle' => (string) get_user_meta( $editor_id, 'estrato_job_title', true ),
				'url'      => get_author_posts_url( $editor_id ),
			);
		}
	}

	$portal = function_exists( 'estrato_nav_current_portal_id' ) ? estrato_nav_current_portal_id() : '';
	if ( 'estrato-saude' === $portal ) {
		$data['disclaimer'] = estrato_ymyl_disclaimer_text();
		$data['about']      = array(
			'@type' => 'MedicalWebPage',
			'name'  => get_the_title(),
		);
	} elseif ( 'estrato-finance' === $portal ) {
		$data['disclaimer'] = estrato_ymyl_disclaimer_text();
	} else {
		$data['disclaimer'] = estrato_ymyl_disclaimer_text();
	}

	$data['publisher'] = array(
		'@type' => 'NewsMediaOrganization',
		'name'  => get_bloginfo( 'name' ),
		'url'   => home_url( '/' ),
	);

	return $data;
}
add_filter( 'wpseo_schema_article', 'estrato_ymyl_schema_article', 20 );

/**
 * Faixa YMYL no rodapé (todos os portais).
 */
function estrato_ymyl_footer_band() {
	if ( is_admin() ) {
		return;
	}
	$text = estrato_ymyl_disclaimer_text();
	echo '<div class="estrato-ymyl-footer" role="note"><p>' . esc_html( $text ) . ' ';
	echo '<a href="' . esc_url( home_url( '/politica-editorial/' ) ) . '">Política editorial</a></p></div>';
}
add_action( 'wp_footer', 'estrato_ymyl_footer_band', 18 );

/**
 * CSS YMYL.
 */
function estrato_ymyl_styles() {
	$css = '.estrato-ymyl-box{margin:2rem 0;padding:1rem 1.15rem;border-left:4px solid var(--estrato-cat-color,#222);background:rgba(0,0,0,.03)}'
		. '.estrato-ymyl-box__label{font-size:.75rem;letter-spacing:.06em;text-transform:uppercase;margin:0 0 .35rem;opacity:.75}'
		. '.estrato-ymyl-box__links{margin:.65rem 0 0;font-size:.92rem}'
		. '.estrato-ymyl-footer{max-width:1200px;margin:0 auto;padding:1rem;font-size:.85rem;opacity:.88;border-top:1px solid var(--estrato-line,#e5e5e5)}'
		. '.estrato-author-trust{margin:0 0 1.25rem;padding:.85rem 1rem;background:rgba(0,0,0,.03);border-radius:6px}'
		. '.estrato-author-disclosure{opacity:.9}';
	if ( function_exists( 'estrato_perf_style_add' ) ) {
		estrato_perf_style_add( 'estrato-ymyl', $css, 'main' );
	} else {
		echo '<style id="estrato-ymyl">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_head', 'estrato_ymyl_styles', 22 );

/**
 * Garante páginas institucionais mínimas YMYL.
 *
 * @return array<string,int>
 */
function estrato_ymyl_ensure_institutional_pages() {
	$pages = array(
		'metodologia'         => array(
			'title'   => 'Metodologia',
			'content' => '<p>O Estrato publica com fontes primárias, revisão editorial e correção registrada. Temas YMYL (dinheiro, saúde e bem-estar) passam por mesa editorial reforçada.</p><p>Critérios: verificação de datas, atribuição de autoria da equipe, disclaimer informativo e links para ética e correções.</p>',
		),
		'etica-editorial'     => array(
			'title'   => 'Ética editorial',
			'content' => '<p>Independência editorial, distinção entre fato e opinião, e compromisso com correções públicas. A rede Estrato não vende recomendação financeira ou médica disfarçada de notícia.</p>',
		),
		'correcoes'           => array(
			'title'   => 'Correções',
			'content' => '<p>Encontrou um erro? Escreva para redacao@estrato.cc ou use a página de Contato. Correções materiais são atualizadas na matéria com indicação de horário.</p>',
		),
		'equipe'              => array(
			'title'   => 'Equipe',
			'content' => '<p>A redação de cada portal conta com cinco autores e um editor-chefe. Bios e escopo de cobertura estão nos perfis e em <a href="https://estrato.cc/blog/">estrato.cc/blog</a>.</p>',
		),
		'politica-editorial'  => array(
			'title'   => 'Política editorial',
			'content' => '<p>Política de seleção, verificação, atualização e transparência YMYL da rede Estrato. Conteúdo informativo; não substitui aconselhamento profissional personalizado.</p>',
		),
	);

	$ids = array();
	foreach ( $pages as $slug => $def ) {
		$existing = get_page_by_path( $slug, OBJECT, 'page' );
		if ( $existing ) {
			$ids[ $slug ] = (int) $existing->ID;
			continue;
		}
		$id = wp_insert_post(
			array(
				'post_title'   => $def['title'],
				'post_name'    => $slug,
				'post_content' => $def['content'],
				'post_status'  => 'publish',
				'post_type'    => 'page',
			),
			true
		);
		if ( ! is_wp_error( $id ) ) {
			$ids[ $slug ] = (int) $id;
		}
	}
	return $ids;
}
