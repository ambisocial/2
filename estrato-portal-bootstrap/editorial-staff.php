<?php
/**
 * Equipe editorial verificável — 5 autores + 1 editor-chefe por portal.
 *
 * Bios organizadas, retratos P&B, sameAs internos e blogs em
 * https://estrato.cc/blog/{slug}/ (página-mãe da rede).
 *
 * Transparência: perfis são da equipe editorial da marca Estrato;
 * não fabricamos redes sociais ou credenciais externas.
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ESTRATO_STAFF_OPTION', 'estrato_editorial_staff_map' );
define( 'ESTRATO_STAFF_EDITOR_META', 'estrato_is_editor_chefe' );

/**
 * Rosters por portal: 5 autores + 1 editor-chefe.
 *
 * @return array<string, array{editor:array<string,string>,authors:array<int,array<string,string>>}>
 */
function estrato_staff_rosters() {
	return array(
		'estrato-finance'   => array(
			'editor'  => array(
				'login' => 'helena-vaz-editora',
				'first' => 'Helena',
				'last'  => 'Vaz',
				'gender'=> 'woman',
				'job'   => 'Editora-chefe — Economia e mercados',
				'focus' => 'macroeconomia, Copom, mercados e finanças pessoais',
			),
			'authors' => array(
				array( 'login' => 'andre-moura', 'first' => 'André', 'last' => 'Moura', 'gender' => 'man', 'job' => 'Repórter de Economia', 'focus' => 'política econômica e indicadores' ),
				array( 'login' => 'sofia-brandt', 'first' => 'Sofia', 'last' => 'Brandt', 'gender' => 'woman', 'job' => 'Repórter de Mercados', 'focus' => 'bolsa, câmbio e renda fixa' ),
				array( 'login' => 'caio-nunes', 'first' => 'Caio', 'last' => 'Nunes', 'gender' => 'man', 'job' => 'Repórter de Negócios', 'focus' => 'empresas, M&A e resultados' ),
				array( 'login' => 'lara-pimentel', 'first' => 'Lara', 'last' => 'Pimentel', 'gender' => 'woman', 'job' => 'Repórter de Finanças Pessoais', 'focus' => 'crédito, orçamento e previdência' ),
				array( 'login' => 'igor-sales', 'first' => 'Igor', 'last' => 'Sales', 'gender' => 'man', 'job' => 'Repórter de Cripto e Agro', 'focus' => 'criptoativos e agronegócio' ),
			),
		),
		'estrato-mind'      => array(
			'editor'  => array(
				'login' => 'marina-cordeiro-editora',
				'first' => 'Marina',
				'last'  => 'Cordeiro',
				'gender'=> 'woman',
				'job'   => 'Editora-chefe — Mente e aprendizado',
				'focus' => 'cognição, hábitos e desenvolvimento pessoal',
			),
			'authors' => array(
				array( 'login' => 'bruno-leite', 'first' => 'Bruno', 'last' => 'Leite', 'gender' => 'man', 'job' => 'Repórter de Aprendizado', 'focus' => 'estudo, memória e foco' ),
				array( 'login' => 'elena-prado', 'first' => 'Elena', 'last' => 'Prado', 'gender' => 'woman', 'job' => 'Repórter de Neurociência', 'focus' => 'sono, cérebro e performance' ),
				array( 'login' => 'diego-amaral', 'first' => 'Diego', 'last' => 'Amaral', 'gender' => 'man', 'job' => 'Repórter de Filosofia prática', 'focus' => 'autoconhecimento e ética cotidiana' ),
				array( 'login' => 'nina-barros', 'first' => 'Nina', 'last' => 'Barros', 'gender' => 'woman', 'job' => 'Repórter de Finanças comportamentais', 'focus' => 'vieses e decisões' ),
				array( 'login' => 'rafael-gomes', 'first' => 'Rafael', 'last' => 'Gomes', 'gender' => 'man', 'job' => 'Repórter de Produtividade', 'focus' => 'rotinas e trabalho profundo' ),
			),
		),
		'estrato-saude'     => array(
			'editor'  => array(
				'login' => 'clara-mendes-editora',
				'first' => 'Clara',
				'last'  => 'Mendes',
				'gender'=> 'woman',
				'job'   => 'Editora-chefe — Saúde',
				'focus' => 'saúde pública, prevenção e bem-estar (conteúdo informativo)',
			),
			'authors' => array(
				array( 'login' => 'paulo-reis', 'first' => 'Paulo', 'last' => 'Reis', 'gender' => 'man', 'job' => 'Repórter de Saúde pública', 'focus' => 'SUS, epidemiologia e políticas' ),
				array( 'login' => 'isabela-torres', 'first' => 'Isabela', 'last' => 'Torres', 'gender' => 'woman', 'job' => 'Repórter de Medicina', 'focus' => 'pesquisas clínicas e tratamentos' ),
				array( 'login' => 'hugo-faria', 'first' => 'Hugo', 'last' => 'Faria', 'gender' => 'man', 'job' => 'Repórter de Bem-estar', 'focus' => 'hábitos e qualidade de vida' ),
				array( 'login' => 'tatiana-lopes', 'first' => 'Tatiana', 'last' => 'Lopes', 'gender' => 'woman', 'job' => 'Repórter de Saúde mental', 'focus' => 'cuidado psicológico e prevenção' ),
				array( 'login' => 'otavio-braga', 'first' => 'Otávio', 'last' => 'Braga', 'gender' => 'man', 'job' => 'Repórter de Ciência da saúde', 'focus' => 'estudos e evidências' ),
			),
		),
		'estrato-politica'  => array(
			'editor'  => array(
				'login' => 'ricardo-alves-editor',
				'first' => 'Ricardo',
				'last'  => 'Alves',
				'gender'=> 'man',
				'job'   => 'Editor-chefe — Política',
				'focus' => 'Brasília, Congresso e Poder Executivo',
			),
			'authors' => array(
				array( 'login' => 'julia-ferreira', 'first' => 'Júlia', 'last' => 'Ferreira', 'gender' => 'woman', 'job' => 'Repórter de Congresso', 'focus' => 'legislativo e partidos' ),
				array( 'login' => 'marcelo-dias', 'first' => 'Marcelo', 'last' => 'Dias', 'gender' => 'man', 'job' => 'Repórter de Planalto', 'focus' => 'Executivo e políticas públicas' ),
				array( 'login' => 'beatriz-cunha', 'first' => 'Beatriz', 'last' => 'Cunha', 'gender' => 'woman', 'job' => 'Repórter de Política econômica', 'focus' => 'orçamento e reformas' ),
				array( 'login' => 'felipe-andrade', 'first' => 'Felipe', 'last' => 'Andrade', 'gender' => 'man', 'job' => 'Repórter de Eleições', 'focus' => 'campanhas e opinião pública' ),
				array( 'login' => 'camila-rocha', 'first' => 'Camila', 'last' => 'Rocha', 'gender' => 'woman', 'job' => 'Repórter de Justiça e poder', 'focus' => 'STF e instituições' ),
			),
		),
		'estrato-tech'      => array(
			'editor'  => array(
				'login' => 'renata-okada-editora',
				'first' => 'Renata',
				'last'  => 'Okada',
				'gender'=> 'woman',
				'job'   => 'Editora-chefe — Tech',
				'focus' => 'inovação, IA e produtos digitais',
			),
			'authors' => array(
				array( 'login' => 'lucas-vieira', 'first' => 'Lucas', 'last' => 'Vieira', 'gender' => 'man', 'job' => 'Repórter de Inteligência artificial', 'focus' => 'modelos, regulação e trabalho' ),
				array( 'login' => 'amanda-siqueira', 'first' => 'Amanda', 'last' => 'Siqueira', 'gender' => 'woman', 'job' => 'Repórter de Startups', 'focus' => 'venture e produtos' ),
				array( 'login' => 'pedro-han', 'first' => 'Pedro', 'last' => 'Han', 'gender' => 'man', 'job' => 'Repórter de Cibersegurança', 'focus' => 'privacidade e riscos' ),
				array( 'login' => 'giulia-martins', 'first' => 'Giulia', 'last' => 'Martins', 'gender' => 'woman', 'job' => 'Repórter de Consumo tech', 'focus' => 'gadgets e plataformas' ),
				array( 'login' => 'thiago-mello', 'first' => 'Thiago', 'last' => 'Mello', 'gender' => 'man', 'job' => 'Repórter de Infra e cloud', 'focus' => 'infraestrutura e dados' ),
			),
		),
	);
}

/**
 * Roster genérico determinístico para portais sem mesa própria.
 *
 * @param string $portal_id
 * @return array{editor:array<string,string>,authors:array<int,array<string,string>>}
 */
function estrato_staff_roster_for_portal( $portal_id ) {
	$rosters = estrato_staff_rosters();
	if ( isset( $rosters[ $portal_id ] ) ) {
		return $rosters[ $portal_id ];
	}

	$pool = function_exists( 'estrato_eeat_persona_name_pool' )
		? estrato_eeat_persona_name_pool()
		: array( array( 'first' => 'Ana', 'last' => 'Silva', 'gender' => 'woman' ) );

	$portal_label = $portal_id;
	if ( function_exists( 'estrato_nav_network_catalog' ) ) {
		foreach ( estrato_nav_network_catalog() as $node ) {
			if ( ( $node['id'] ?? '' ) === $portal_id ) {
				$portal_label = $node['name'] ?? $portal_id;
				break;
			}
		}
	}

	$base   = abs( crc32( $portal_id ) );
	$editor = $pool[ $base % count( $pool ) ];
	$authors = array();
	for ( $i = 0; $i < 5; $i++ ) {
		$pick = $pool[ ( $base + $i + 1 ) % count( $pool ) ];
		$authors[] = array(
			'login'  => sanitize_title( $portal_id . '-' . $pick['first'] . '-' . $pick['last'] . '-' . $i ),
			'first'  => $pick['first'],
			'last'   => $pick['last'],
			'gender' => $pick['gender'],
			'job'    => 'Repórter — ' . $portal_label,
			'focus'  => 'cobertura editorial de ' . $portal_label,
		);
	}

	return array(
		'editor'  => array(
			'login'  => sanitize_title( $portal_id . '-editor-chefe' ),
			'first'  => $editor['first'],
			'last'   => $editor['last'],
			'gender' => $editor['gender'],
			'job'    => 'Editor(a)-chefe — ' . $portal_label,
			'focus'  => 'linha editorial e revisão YMYL de ' . $portal_label,
		),
		'authors' => $authors,
	);
}

/**
 * Bio longa, organizada em seções (arquivo WP + blog).
 *
 * @param array<string,string> $person
 * @param string               $portal_label
 * @param bool                 $is_editor
 * @return string
 */
function estrato_staff_build_bio( $person, $portal_label, $is_editor = false ) {
	$name  = trim( ( $person['first'] ?? '' ) . ' ' . ( $person['last'] ?? '' ) );
	$focus = (string) ( $person['focus'] ?? 'cobertura editorial' );
	$job   = (string) ( $person['job'] ?? 'Redação' );
	$role  = $is_editor ? 'editoria-chefe' : 'reportagem';

	$parts = array(
		$name . ' integra a equipe editorial do ' . $portal_label . ' (rede Estrato) na função de ' . $job . '.',
		'Escopo de cobertura: ' . $focus . '.',
		'No dia a dia, prioriza fontes primárias, datas verificáveis e linguagem clara para o leitor brasileiro.',
		$is_editor
			? 'Como ' . $role . ', revisa pautas sensíveis (YMYL), padrões de correção e alinhamento à política editorial da marca.'
			: 'Trabalha sob revisão da mesa editorial e do editor-chefe, com atualização de matérias quando surgem novos dados oficiais.',
		'Este perfil descreve o papel editorial na marca Estrato. Não constitui aconselhamento profissional personalizado.',
		'Transparência: correções em /correcoes/, metodologia em /metodologia/ e contato em /contato/.',
	);

	return implode( ' ', $parts );
}

/**
 * Conteúdo HTML do blog do autor na página-mãe.
 *
 * @param array<string,string> $person
 * @param string               $portal_label
 * @param bool                 $is_editor
 * @param string               $author_url
 * @return string
 */
function estrato_staff_blog_page_content( $person, $portal_label, $is_editor, $author_url ) {
	$name  = trim( ( $person['first'] ?? '' ) . ' ' . ( $person['last'] ?? '' ) );
	$job   = (string) ( $person['job'] ?? 'Redação' );
	$focus = (string) ( $person['focus'] ?? '' );
	$bio   = estrato_staff_build_bio( $person, $portal_label, $is_editor );

	$html  = '<section class="estrato-author-blog">';
	$html .= '<p class="estrato-kicker">' . esc_html( $is_editor ? 'Editoria-chefe' : 'Equipe editorial' ) . '</p>';
	$html .= '<h1 class="estrato-display">' . esc_html( $name ) . '</h1>';
	$html .= '<p><strong>' . esc_html( $job ) . '</strong> · ' . esc_html( $portal_label ) . '</p>';
	$html .= '<h2>Sobre</h2><p>' . esc_html( $bio ) . '</p>';
	$html .= '<h2>Áreas de cobertura</h2><p>' . esc_html( $focus ) . '</p>';
	$html .= '<h2>Como trabalhamos</h2>';
	$html .= '<ul>';
	$html .= '<li>Fontes primárias e cruzamento editorial antes da publicação.</li>';
	$html .= '<li>Atualização com selo quando há mudança material nos fatos.</li>';
	$html .= '<li>Disclaimer YMYL: conteúdo informativo, não aconselhamento personalizado.</li>';
	$html .= '<li>Canal de correções e ética editorial abertos ao leitor.</li>';
	$html .= '</ul>';
	$html .= '<h2>Arquivo no portal</h2>';
	$html .= '<p><a href="' . esc_url( $author_url ) . '">Ver matérias assinadas por ' . esc_html( $name ) . '</a></p>';
	$html .= '<h2>Transparência</h2>';
	$html .= '<p>Perfil da equipe editorial Estrato. Rede: <a href="https://estrato.cc/">estrato.cc</a>. '
		. 'Política: <a href="https://estrato.cc/politica-editorial/">política editorial</a>.</p>';
	$html .= '</section>';
	return $html;
}

/**
 * Cria/atualiza usuário WP da equipe.
 *
 * @param array<string,string> $person
 * @param string               $portal_id
 * @param string               $portal_label
 * @param bool                 $is_editor
 * @return int
 */
function estrato_staff_ensure_user( $person, $portal_id, $portal_label, $is_editor = false ) {
	$login = sanitize_user( (string) ( $person['login'] ?? '' ), true );
	if ( ! $login ) {
		return 0;
	}

	$display = trim( ( $person['first'] ?? '' ) . ' ' . ( $person['last'] ?? '' ) );
	$bio     = estrato_staff_build_bio( $person, $portal_label, $is_editor );
	$job     = (string) ( $person['job'] ?? '' );
	$email   = $login . '@authors.estrato.cc';
	$role    = $is_editor ? 'editor' : 'author';

	$user_id = username_exists( $login );
	$data    = array(
		'user_login'   => $login,
		'user_email'   => $email,
		'display_name' => $display,
		'first_name'   => (string) ( $person['first'] ?? '' ),
		'last_name'    => (string) ( $person['last'] ?? '' ),
		'description'  => $bio,
		'role'         => $role,
		'user_url'     => 'https://estrato.cc/blog/' . sanitize_title( $login ) . '/',
	);

	if ( ! $user_id ) {
		$data['user_pass'] = wp_generate_password( 32, true, true );
		$user_id           = wp_insert_user( $data );
	} else {
		$data['ID'] = (int) $user_id;
		$user_id    = wp_update_user( $data );
	}

	if ( is_wp_error( $user_id ) ) {
		return 0;
	}

	update_user_meta( $user_id, 'estrato_job_title', $job );
	update_user_meta( $user_id, 'wpseo_job_title', $job );
	update_user_meta( $user_id, ESTRATO_STAFF_EDITOR_META, $is_editor ? '1' : '0' );
	update_user_meta( $user_id, 'estrato_staff_portal', $portal_id );
	update_user_meta( $user_id, 'estrato_works_for', $portal_label );
	update_user_meta( $user_id, 'estrato_author_disclosure', 'Equipe editorial Estrato — perfil de cobertura da marca.' );
	update_user_meta(
		$user_id,
		'estrato_same_as',
		array(
			home_url( '/author/' . $login . '/' ),
			'https://estrato.cc/blog/' . sanitize_title( $login ) . '/',
		)
	);

	$persona = array(
		'display_name' => $display,
		'gender'       => (string) ( $person['gender'] ?? 'person' ),
	);
	if ( function_exists( 'estrato_eeat_sideload_portrait' ) ) {
		estrato_eeat_sideload_portrait( (int) $user_id, $login, $persona );
	}

	return (int) $user_id;
}

/**
 * Garante página /blog/{login}/ na instalação mãe (estrato.cc) ou localmente.
 *
 * @param array<string,string> $person
 * @param string               $portal_label
 * @param bool                 $is_editor
 * @param int                  $user_id
 * @return int Page ID
 */
function estrato_staff_ensure_blog_page( $person, $portal_label, $is_editor, $user_id ) {
	$login = sanitize_title( (string) ( $person['login'] ?? '' ) );
	if ( ! $login ) {
		return 0;
	}

	$parent = get_page_by_path( 'blog', OBJECT, 'page' );
	if ( ! $parent ) {
		$parent_id = wp_insert_post(
			array(
				'post_title'   => 'Blog da redação',
				'post_name'    => 'blog',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '<p>Perfis da equipe editorial Estrato — bios, escopo de cobertura e transparência YMYL.</p>',
			),
			true
		);
		if ( is_wp_error( $parent_id ) ) {
			return 0;
		}
		$parent = get_post( $parent_id );
	}

	$author_url = get_author_posts_url( $user_id );
	$content    = estrato_staff_blog_page_content( $person, $portal_label, $is_editor, $author_url );

	$existing = get_children(
		array(
			'post_parent' => $parent->ID,
			'post_type'   => 'page',
			'post_status' => 'any',
			'name'        => $login,
		)
	);
	$page = $existing ? reset( $existing ) : null;

	$data = array(
		'post_title'   => trim( ( $person['first'] ?? '' ) . ' ' . ( $person['last'] ?? '' ) ),
		'post_name'    => $login,
		'post_content' => $content,
		'post_status'  => 'publish',
		'post_type'    => 'page',
		'post_parent'  => (int) $parent->ID,
		'post_author'  => $user_id,
	);

	if ( $page ) {
		$data['ID'] = $page->ID;
		$id         = wp_update_post( $data, true );
	} else {
		$id = wp_insert_post( $data, true );
	}

	if ( is_wp_error( $id ) ) {
		return 0;
	}

	update_post_meta( $id, '_estrato_staff_user_id', $user_id );
	update_user_meta( $user_id, 'estrato_blog_page_id', (int) $id );
	return (int) $id;
}

/**
 * No domínio mãe, cria blogs de todas as mesas da rede em /blog/{slug}/.
 *
 * @return array<string,int>
 */
function estrato_staff_provision_network_blogs_on_hub() {
	$host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
	$portal = function_exists( 'estrato_nav_current_portal_id' ) ? estrato_nav_current_portal_id() : '';
	if ( 'estrato.cc' !== $host && 'estrato-finance' !== $portal ) {
		return array();
	}

	$pages = array();
	foreach ( array_keys( estrato_staff_rosters() ) as $portal_id ) {
		$roster = estrato_staff_roster_for_portal( $portal_id );
		$label  = $portal_id;
		if ( function_exists( 'estrato_nav_network_catalog' ) ) {
			foreach ( estrato_nav_network_catalog() as $node ) {
				if ( ( $node['id'] ?? '' ) === $portal_id ) {
					$label = $node['name'] ?? $portal_id;
					break;
				}
			}
		}
		$editor_id = estrato_staff_ensure_user( $roster['editor'], $portal_id, $label, true );
		if ( $editor_id ) {
			$pages[ $roster['editor']['login'] ] = estrato_staff_ensure_blog_page( $roster['editor'], $label, true, $editor_id );
		}
		foreach ( $roster['authors'] as $person ) {
			$uid = estrato_staff_ensure_user( $person, $portal_id, $label, false );
			if ( $uid ) {
				$pages[ $person['login'] ] = estrato_staff_ensure_blog_page( $person, $label, false, $uid );
			}
		}
	}
	return $pages;
}

/**
 * Provisiona equipe do portal atual e mapeia autores às editorias.
 *
 * @return array<string,mixed>
 */
function estrato_staff_provision_current_portal() {
	$portal_id = function_exists( 'estrato_nav_current_portal_id' )
		? estrato_nav_current_portal_id()
		: 'estrato-finance';
	$roster    = estrato_staff_roster_for_portal( $portal_id );

	$portal_label = get_bloginfo( 'name' );
	$is_hub       = ( 'estrato-finance' === $portal_id )
		|| ( 'estrato.cc' === (string) wp_parse_url( home_url(), PHP_URL_HOST ) );

	$editor_id = estrato_staff_ensure_user( $roster['editor'], $portal_id, $portal_label, true );
	$author_ids = array();
	foreach ( $roster['authors'] as $person ) {
		$uid = estrato_staff_ensure_user( $person, $portal_id, $portal_label, false );
		if ( $uid ) {
			$author_ids[] = $uid;
		}
		if ( $is_hub || 'estrato-finance' === $portal_id ) {
			estrato_staff_ensure_blog_page( $person, $portal_label, false, $uid );
		}
	}
	if ( $editor_id && ( $is_hub || 'estrato-finance' === $portal_id ) ) {
		estrato_staff_ensure_blog_page( $roster['editor'], $portal_label, true, $editor_id );
	}

	$network_blogs = array();
	if ( $is_hub || 'estrato-finance' === $portal_id ) {
		$network_blogs = estrato_staff_provision_network_blogs_on_hub();
	}

	$map = array(
		'portal'         => $portal_id,
		'editor_id'      => $editor_id,
		'author_ids'     => $author_ids,
		'network_blogs'  => count( $network_blogs ),
		'updated'        => time(),
	);
	update_option( ESTRATO_STAFF_OPTION, $map, false );

	// Distribui autores pelas editorias raiz (rotação).
	$editorias = function_exists( 'estrato_aeo_portal_editorias' ) ? estrato_aeo_portal_editorias() : array();
	$cat_map   = get_option( ESTRATO_CATEGORY_AUTHOR_OPTION, array() );
	if ( ! is_array( $cat_map ) ) {
		$cat_map = array();
	}
	foreach ( array_values( $editorias ) as $idx => $slug ) {
		if ( empty( $author_ids ) ) {
			break;
		}
		$cat_map[ $slug ] = (int) $author_ids[ $idx % count( $author_ids ) ];
	}
	update_option( ESTRATO_CATEGORY_AUTHOR_OPTION, $cat_map, false );

	return $map;
}

/**
 * Resolve autor da equipe (rotação por categoria) com fallback legado.
 *
 * @param int $post_id
 * @return int
 */
function estrato_staff_resolve_author_for_post( $post_id ) {
	$map = get_option( ESTRATO_STAFF_OPTION, array() );
	if ( empty( $map['author_ids'] ) || ! is_array( $map['author_ids'] ) ) {
		return function_exists( 'estrato_eeat_resolve_author_for_post' )
			? estrato_eeat_resolve_author_for_post( $post_id )
			: 1;
	}

	$cats = get_the_category( $post_id );
	$slug = ! empty( $cats[0] ) ? $cats[0]->slug : '';
	while ( ! empty( $cats[0] ) && (int) $cats[0]->parent > 0 ) {
		$parent = get_term( (int) $cats[0]->parent, 'category' );
		if ( ! $parent || is_wp_error( $parent ) ) {
			break;
		}
		$slug = $parent->slug;
		break;
	}

	$cat_map = function_exists( 'estrato_eeat_get_category_author_map' )
		? estrato_eeat_get_category_author_map()
		: array();
	if ( $slug && ! empty( $cat_map[ $slug ] ) ) {
		return (int) $cat_map[ $slug ];
	}

	$ids = array_values( array_filter( array_map( 'intval', $map['author_ids'] ) ) );
	if ( ! $ids ) {
		return 1;
	}
	$idx = abs( crc32( (string) $post_id . $slug ) ) % count( $ids );
	return $ids[ $idx ];
}

/**
 * Atribui autor da equipe ao publicar/importar.
 *
 * @param int $post_id
 */
function estrato_staff_assign_on_save( $post_id ) {
	static $guard = array();
	if ( isset( $guard[ $post_id ] ) ) {
		return;
	}
	$post = get_post( $post_id );
	if ( ! $post || 'post' !== $post->post_type || wp_is_post_revision( $post_id ) ) {
		return;
	}
	$map = get_option( ESTRATO_STAFF_OPTION, array() );
	if ( empty( $map['author_ids'] ) ) {
		return;
	}
	$uid = estrato_staff_resolve_author_for_post( $post_id );
	if ( $uid && (int) $post->post_author !== $uid ) {
		$guard[ $post_id ] = true;
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_author' => $uid,
			)
		);
	}
}
add_action( 'save_post_post', 'estrato_staff_assign_on_save', 40 );

/**
 * Hero enriquecido no arquivo do autor.
 */
function estrato_staff_author_archive_extras() {
	if ( ! is_author() ) {
		return;
	}
	$user = get_queried_object();
	if ( ! $user || empty( $user->ID ) ) {
		return;
	}
	$blog_id = (int) get_user_meta( $user->ID, 'estrato_blog_page_id', true );
	$blog    = $blog_id ? get_permalink( $blog_id ) : ( 'https://estrato.cc/blog/' . $user->user_nicename . '/' );
	$is_ed   = '1' === (string) get_user_meta( $user->ID, ESTRATO_STAFF_EDITOR_META, true );
	$disc    = (string) get_user_meta( $user->ID, 'estrato_author_disclosure', true );
	echo '<div class="estrato-author-trust">';
	if ( $is_ed ) {
		echo '<p><strong>Editor(a)-chefe</strong> — revisão YMYL e linha editorial.</p>';
	}
	if ( $disc ) {
		echo '<p class="estrato-author-disclosure">' . esc_html( $disc ) . '</p>';
	}
	echo '<p><a href="' . esc_url( $blog ) . '">Blog e bio completa</a> · ';
	echo '<a href="' . esc_url( home_url( '/equipe/' ) ) . '">Equipe</a> · ';
	echo '<a href="' . esc_url( home_url( '/metodologia/' ) ) . '">Metodologia</a></p>';
	echo '</div>';
}
add_action( 'loop_start', function () {
	if ( is_author() && in_the_loop() && 0 === (int) get_query_var( 'paged', 0 ) ) {
		static $done = false;
		if ( ! $done ) {
			$done = true;
			estrato_staff_author_archive_extras();
		}
	}
}, 6 );

/**
 * @return int
 */
function estrato_regression_staff_count() {
	$map = get_option( ESTRATO_STAFF_OPTION, array() );
	$n   = empty( $map['author_ids'] ) ? 0 : count( (array) $map['author_ids'] );
	if ( ! empty( $map['editor_id'] ) ) {
		++$n;
	}
	return $n;
}

/**
 * Remove sameAs LinkedIn fabricado e normaliza URLs internas.
 *
 * @return array{scrubbed:int,users:int}
 */
function estrato_staff_scrub_fake_same_as() {
	$users = get_users(
		array(
			'fields' => array( 'ID', 'user_login' ),
			'number' => -1,
		)
	);
	$scrubbed = 0;
	foreach ( $users as $user ) {
		$uid     = (int) $user->ID;
		$login   = (string) $user->user_login;
		$same_as = get_user_meta( $uid, 'estrato_same_as', true );
		$changed = false;
		$urls    = array();

		if ( is_string( $same_as ) && $same_as ) {
			$same_as = array( $same_as );
		}
		if ( ! is_array( $same_as ) ) {
			$same_as = array();
		}

		foreach ( $same_as as $url ) {
			$url = trim( (string) $url );
			if ( ! $url ) {
				continue;
			}
			if ( preg_match( '#linkedin\.com/in/#i', $url ) ) {
				$changed = true;
				continue;
			}
			$urls[] = $url;
		}

		// Garante sameAs interno mínimo para quem tem meta de equipe/persona.
		$is_staff = (bool) get_user_meta( $uid, 'estrato_staff_portal', true )
			|| (bool) get_user_meta( $uid, 'estrato_author_term_slug', true )
			|| (bool) get_user_meta( $uid, ESTRATO_STAFF_EDITOR_META, true );
		if ( $is_staff || $changed ) {
			$internal = array(
				home_url( '/author/' . sanitize_title( $login ) . '/' ),
				'https://estrato.cc/blog/' . sanitize_title( $login ) . '/',
			);
			$urls = array_values( array_unique( array_merge( $urls, $internal ) ) );
			$urls = array_values(
				array_filter(
					$urls,
					static function ( $u ) {
						return (bool) preg_match( '#^https?://#i', $u )
							&& ! preg_match( '#linkedin\.com/in/#i', $u );
					}
				)
			);
			update_user_meta( $uid, 'estrato_same_as', $urls );
			++$scrubbed;
		}

		// Yoast person social (se existir).
		foreach ( array( 'wpseo_user_schema', 'facebook', 'twitter', 'linkedin' ) as $key ) {
			$val = get_user_meta( $uid, $key, true );
			if ( is_string( $val ) && preg_match( '#linkedin\.com/in/#i', $val ) ) {
				delete_user_meta( $uid, $key );
				$changed = true;
			}
		}
	}

	return array(
		'scrubbed' => $scrubbed,
		'users'    => count( $users ),
	);
}

/**
 * Reatribui posts publicados à equipe editorial (staff map).
 *
 * @param int $batch Tamanho do lote (0 = todos).
 * @return array{updated:int,total:int,skipped:int}
 */
function estrato_staff_reassign_inventory( $batch = 0 ) {
	$map = get_option( ESTRATO_STAFF_OPTION, array() );
	if ( empty( $map['author_ids'] ) ) {
		if ( function_exists( 'estrato_staff_provision_current_portal' ) ) {
			$map = estrato_staff_provision_current_portal();
		}
	}
	if ( empty( $map['author_ids'] ) ) {
		return array(
			'updated' => 0,
			'total'   => 0,
			'skipped' => 0,
		);
	}

	$args = array(
		'post_type'              => 'post',
		'post_status'            => 'publish',
		'posts_per_page'         => $batch > 0 ? (int) $batch : -1,
		'fields'                 => 'ids',
		'orderby'                => 'ID',
		'order'                  => 'DESC',
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
	);
	$ids = get_posts( $args );
	$updated = 0;
	$skipped = 0;

	// Evita cascata de save_post (syndication, assign, Yoast…) no bulk.
	remove_action( 'save_post_post', 'estrato_staff_assign_on_save', 40 );
	global $wpdb;

	foreach ( $ids as $post_id ) {
		$post_id = (int) $post_id;
		$uid     = estrato_staff_resolve_author_for_post( $post_id );
		if ( ! $uid ) {
			++$skipped;
			continue;
		}
		$current = (int) get_post_field( 'post_author', $post_id );
		if ( $current === $uid ) {
			++$skipped;
			continue;
		}
		$ok = $wpdb->update(
			$wpdb->posts,
			array( 'post_author' => $uid ),
			array( 'ID' => $post_id ),
			array( '%d' ),
			array( '%d' )
		);
		if ( false !== $ok ) {
			clean_post_cache( $post_id );
			++$updated;
		} else {
			++$skipped;
		}
	}
	add_action( 'save_post_post', 'estrato_staff_assign_on_save', 40 );

	return array(
		'updated' => $updated,
		'total'   => count( $ids ),
		'skipped' => $skipped,
	);
}

/**
 * Backfill de retratos P&B para equipe (staff + editor).
 *
 * @param bool $force Regenera mesmo se já houver attachment.
 * @return array{ok:int,fail:int,skipped:int}
 */
function estrato_staff_backfill_portraits( $force = false ) {
	if ( ! function_exists( 'estrato_eeat_sideload_portrait' ) ) {
		return array(
			'ok'      => 0,
			'fail'    => 0,
			'skipped' => 0,
		);
	}

	$map = get_option( ESTRATO_STAFF_OPTION, array() );
	$ids = array();
	if ( ! empty( $map['editor_id'] ) ) {
		$ids[] = (int) $map['editor_id'];
	}
	foreach ( (array) ( $map['author_ids'] ?? array() ) as $id ) {
		$ids[] = (int) $id;
	}
	$ids = array_values( array_unique( array_filter( $ids ) ) );

	$ok = 0;
	$fail = 0;
	$skipped = 0;

	foreach ( $ids as $uid ) {
		$user = get_userdata( $uid );
		if ( ! $user ) {
			++$fail;
			continue;
		}
		$existing = (int) get_user_meta( $uid, 'estrato_avatar_attachment_id', true );
		if ( $existing && get_post( $existing ) && ! $force ) {
			++$skipped;
			continue;
		}
		if ( $force && $existing ) {
			delete_user_meta( $uid, 'estrato_avatar_attachment_id' );
			delete_user_meta( $uid, 'estrato_avatar_url' );
		}
		$persona = array(
			'display_name' => $user->display_name,
			'gender'       => 'person',
		);
		// Gênero a partir do roster quando possível.
		$roster = estrato_staff_roster_for_portal(
			(string) ( get_user_meta( $uid, 'estrato_staff_portal', true ) ?: ( $map['portal'] ?? '' ) )
		);
		foreach ( array_merge( array( $roster['editor'] ), $roster['authors'] ) as $person ) {
			if ( ( $person['login'] ?? '' ) === $user->user_login ) {
				$persona['gender'] = $person['gender'] ?? $persona['gender'];
				break;
			}
		}

		$attach = estrato_eeat_sideload_portrait( $uid, $user->user_login, $persona );
		if ( $attach ) {
			++$ok;
		} else {
			++$fail;
		}
	}

	return array(
		'ok'      => $ok,
		'fail'    => $fail,
		'skipped' => $skipped,
	);
}
