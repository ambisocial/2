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

	$portal = function_exists( 'estrato_nav_current_portal_id' ) ? estrato_nav_current_portal_id() : '';
	$html  = '<aside class="estrato-ymyl-box" aria-label="Aviso YMYL">';
	$html .= '<p class="estrato-ymyl-box__label">Transparência editorial</p>';
	$html .= '<p>' . esc_html( estrato_ymyl_disclaimer_text() ) . '</p>';
	$html .= '<p class="estrato-ymyl-box__links">';
	$html .= '<a href="' . esc_url( home_url( '/metodologia/' ) ) . '">Metodologia</a> · ';
	$html .= '<a href="' . esc_url( home_url( '/etica-editorial/' ) ) . '">Ética</a> · ';
	$html .= '<a href="' . esc_url( home_url( '/correcoes/' ) ) . '">Correções</a> · ';
	$html .= '<a href="' . esc_url( home_url( '/equipe/' ) ) . '">Equipe</a>';
	if ( 'estrato-saude' === $portal || 'estrato-mind' === $portal ) {
		$html .= ' · <a href="' . esc_url( home_url( '/aviso-medico/' ) ) . '">Aviso médico</a>';
	}
	if ( 'estrato-finance' === $portal ) {
		$html .= ' · <a href="' . esc_url( home_url( '/aviso-financeiro/' ) ) . '">Aviso financeiro</a>';
	}
	$reviewed = (int) get_post_meta( get_the_ID(), 'estrato_reviewed_by', true );
	if ( $reviewed ) {
		$rev_user = get_userdata( $reviewed );
		if ( $rev_user ) {
			$html .= ' · Revisado por: <a href="' . esc_url( get_author_posts_url( $reviewed ) ) . '">'
				. esc_html( $rev_user->display_name ) . '</a>';
		}
	} elseif ( $editor ) {
		$html .= ' · Mesa editorial: <a href="' . esc_url( get_author_posts_url( $editor_id ) ) . '">'
			. esc_html( $editor->display_name ) . '</a>';
	}
	$html .= '</p></aside>';

	return $content . "\n" . $html;
}
add_filter( 'the_content', 'estrato_ymyl_append_disclaimer', 30 );

/**
 * Marca revisão do editor-chefe (meta) em posts YMYL altos.
 *
 * @param int $post_id
 */
function estrato_ymyl_mark_reviewed_on_save( $post_id ) {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}
	$post = get_post( $post_id );
	if ( ! $post || 'post' !== $post->post_type || 'publish' !== $post->post_status ) {
		return;
	}
	if ( ! estrato_ymyl_is_high_portal() ) {
		return;
	}
	if ( get_post_meta( $post_id, 'estrato_reviewed_by', true ) ) {
		return;
	}
	$map       = get_option( defined( 'ESTRATO_STAFF_OPTION' ) ? ESTRATO_STAFF_OPTION : 'estrato_editorial_staff_map', array() );
	$editor_id = ! empty( $map['editor_id'] ) ? (int) $map['editor_id'] : 0;
	if ( ! $editor_id ) {
		return;
	}
	update_post_meta( $post_id, 'estrato_reviewed_by', $editor_id );
	update_post_meta( $post_id, 'estrato_reviewed_at', gmdate( 'c' ) );
}
add_action( 'save_post_post', 'estrato_ymyl_mark_reviewed_on_save', 50 );

/**
 * Backfill reviewedBy meta em inventário YMYL.
 *
 * @return array{marked:int}
 */
function estrato_ymyl_backfill_reviewed_meta() {
	if ( ! estrato_ymyl_is_high_portal() ) {
		return array( 'marked' => 0 );
	}
	$map       = get_option( defined( 'ESTRATO_STAFF_OPTION' ) ? ESTRATO_STAFF_OPTION : 'estrato_editorial_staff_map', array() );
	$editor_id = ! empty( $map['editor_id'] ) ? (int) $map['editor_id'] : 0;
	if ( ! $editor_id ) {
		return array( 'marked' => 0 );
	}
	$ids = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => 'estrato_reviewed_by',
					'compare' => 'NOT EXISTS',
				),
			),
		)
	);
	$n = 0;
	foreach ( $ids as $id ) {
		update_post_meta( (int) $id, 'estrato_reviewed_by', $editor_id );
		update_post_meta( (int) $id, 'estrato_reviewed_at', gmdate( 'c' ) );
		++$n;
	}
	return array( 'marked' => $n );
}

/**
 * reviewedBy + disclaimers no NewsArticle (Yoast).
 * reviewedBy só quando há meta estrato_reviewed_by.
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

	$reviewed = (int) get_post_meta( get_the_ID(), 'estrato_reviewed_by', true );
	if ( $reviewed ) {
		$editor = get_userdata( $reviewed );
		if ( $editor ) {
			$data['reviewedBy'] = array(
				'@type'    => 'Person',
				'name'     => $editor->display_name,
				'jobTitle' => (string) get_user_meta( $reviewed, 'estrato_job_title', true ),
				'url'      => get_author_posts_url( $reviewed ),
			);
			$at = (string) get_post_meta( get_the_ID(), 'estrato_reviewed_at', true );
			if ( $at ) {
				$data['lastReviewed'] = $at;
			}
		}
	}

	$portal = function_exists( 'estrato_nav_current_portal_id' ) ? estrato_nav_current_portal_id() : '';
	$data['disclaimer'] = estrato_ymyl_disclaimer_text();
	if ( 'estrato-saude' === $portal ) {
		$data['about'] = array(
			'@type' => 'MedicalWebPage',
			'name'  => get_the_title(),
		);
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
			'content' => '<p>Encontrou um erro? Escreva para redacao@estrato.cc ou use a página de Contato. Correções materiais são atualizadas na matéria com indicação de horário.</p><div class="estrato-corrections-log" data-estrato-corrections="1"></div>',
		),
		'equipe'              => array(
			'title'   => 'Equipe',
			'content' => '<p>A redação de cada portal conta com cinco autores e um editor-chefe. Bios e escopo de cobertura estão nos perfis e em <a href="https://estrato.cc/blog/">estrato.cc/blog</a>.</p>',
		),
		'politica-editorial'  => array(
			'title'   => 'Política editorial',
			'content' => '<p>Política de seleção, verificação, atualização e transparência YMYL da rede Estrato. Conteúdo informativo; não substitui aconselhamento profissional personalizado.</p>',
		),
		'aviso-medico'        => array(
			'title'   => 'Aviso médico',
			'content' => '<p>O conteúdo de saúde do Estrato é informativo e educacional. Não substitui consulta, diagnóstico, prescrição ou tratamento com profissional de saúde habilitado. Em emergência, procure serviço médico ou SAMU 192.</p><p>Fontes clínicas e estudos são citados quando disponíveis; a interpretação jornalística não constitui parecer médico.</p>',
		),
		'aviso-financeiro'    => array(
			'title'   => 'Aviso financeiro',
			'content' => '<p>O conteúdo econômico e financeiro do Estrato é jornalístico. Não constitui recomendação de compra ou venda de ativos, consultoria de valores mobiliários, análise de investimento personalizada nem planejamento financeiro.</p><p>Decisões de investimento envolvem risco de perda. Consulte profissionais habilitados quando necessário.</p>',
		),
	);

	$ids = array();
	foreach ( $pages as $slug => $def ) {
		$existing = get_page_by_path( $slug, OBJECT, 'page' );
		if ( $existing ) {
			$ids[ $slug ] = (int) $existing->ID;
			// Atualiza páginas de aviso/correções se conteúdo estiver vazio/curto.
			if ( in_array( $slug, array( 'aviso-medico', 'aviso-financeiro', 'correcoes' ), true )
				&& strlen( wp_strip_all_tags( (string) $existing->post_content ) ) < 40 ) {
				wp_update_post(
					array(
						'ID'           => $existing->ID,
						'post_content' => $def['content'],
					)
				);
			}
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

/**
 * Semear log de correções a partir de posts atualizados materialmente.
 *
 * @param int $limit
 * @return int
 */
function estrato_ymyl_seed_corrections_log( $limit = 15 ) {
	$log = get_option( 'estrato_corrections_log', array() );
	if ( ! is_array( $log ) ) {
		$log = array();
	}
	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 40,
			'orderby'        => 'modified',
			'order'          => 'DESC',
		)
	);
	foreach ( $posts as $p ) {
		$pub = get_post_time( 'U', true, $p );
		$mod = get_post_modified_time( 'U', true, $p );
		if ( ! $pub || ! $mod || ( $mod - $pub ) < HOUR_IN_SECONDS ) {
			continue;
		}
		$key = 'p' . $p->ID . '-' . $mod;
		$exists = false;
		foreach ( $log as $row ) {
			if ( ( $row['key'] ?? '' ) === $key ) {
				$exists = true;
				break;
			}
		}
		if ( $exists ) {
			continue;
		}
		array_unshift(
			$log,
			array(
				'key'     => $key,
				'post_id' => (int) $p->ID,
				'title'   => get_the_title( $p ),
				'url'     => get_permalink( $p ),
				'at'      => gmdate( 'c', $mod ),
				'note'    => 'Atualização material registrada na matéria (selo ATUALIZADO).',
			)
		);
		if ( count( $log ) >= $limit ) {
			break;
		}
	}
	$log = array_slice( $log, 0, $limit );
	update_option( 'estrato_corrections_log', $log, false );
	return count( $log );
}

/**
 * Injeta lista pública de correções na página /correcoes/.
 *
 * @param string $content
 * @return string
 */
function estrato_ymyl_corrections_page_content( $content ) {
	if ( ! is_page( 'correcoes' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	if ( false !== strpos( $content, 'estrato-corrections-log__list' ) ) {
		return $content;
	}
	$log = get_option( 'estrato_corrections_log', array() );
	if ( ! is_array( $log ) || ! $log ) {
		estrato_ymyl_seed_corrections_log( 12 );
		$log = get_option( 'estrato_corrections_log', array() );
	}
	$html  = '<section class="estrato-corrections-log__list" aria-label="Registro de correções">';
	$html .= '<h2>Registro recente</h2>';
	if ( ! $log ) {
		$html .= '<p>Nenhuma correção material registrada neste período. Para reportar um erro: redacao@estrato.cc.</p>';
	} else {
		$html .= '<ul>';
		foreach ( $log as $row ) {
			$ts = strtotime( (string) ( $row['at'] ?? '' ) );
			$html .= '<li><time datetime="' . esc_attr( (string) ( $row['at'] ?? '' ) ) . '">'
				. esc_html( $ts ? date_i18n( 'd/m/Y H:i', $ts ) : '' )
				. '</time> — <a href="' . esc_url( (string) ( $row['url'] ?? '#' ) ) . '">'
				. esc_html( (string) ( $row['title'] ?? 'Matéria' ) ) . '</a>: '
				. esc_html( (string) ( $row['note'] ?? '' ) ) . '</li>';
		}
		$html .= '</ul>';
	}
	$html .= '</section>';
	if ( false !== strpos( $content, 'data-estrato-corrections' ) ) {
		return preg_replace( '/<div class="estrato-corrections-log"[^>]*><\/div>/', $html, $content, 1 );
	}
	return $content . $html;
}
add_filter( 'the_content', 'estrato_ymyl_corrections_page_content', 32 );
