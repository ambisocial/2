<?php
/**
 * Sprint 7 — Salvaguardas de gate (sem-categoria, categoria padrão no pipeline).
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @param int $post_id
 */
function estrato_gate_ensure_post_category( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post || 'post' !== $post->post_type || 'publish' !== $post->post_status ) {
		return;
	}

	$cats  = wp_get_post_categories( $post_id );
	$uncat = get_term_by( 'slug', 'sem-categoria', 'category' );
	$needs = empty( $cats );
	if ( ! $needs && $uncat && ! is_wp_error( $uncat ) && in_array( (int) $uncat->term_id, $cats, true ) ) {
		$needs = true;
	}
	if ( ! $needs ) {
		return;
	}

	$haystack = strtolower( $post->post_title . ' ' . wp_strip_all_tags( $post->post_content ) );
	$rules    = array(
		'criptomoedas'      => '/\b(bitcoin|btc|ethereum|cripto|blockchain)\b/iu',
		'agronegocio'       => '/\b(agroneg[oó]cio|soja|safra|pecu[aá]ria)\b/iu',
		'mercados'          => '/\b(ibovespa|bolsa|b3|d[oó]lar|selic|juros|cdi)\b/iu',
		'financas-pessoais' => '/\b(cart[aã]o|consignado|inss|aposentadoria)\b/iu',
		'negocios'          => '/\b(empresa|startup|fintech|petrobras|vale\b)\b/iu',
		'economia'          => '/\b(pib|infla[cç][aã]o|ipca|fiscal|imposto)\b/iu',
		'mundo'             => '/\b(eua|europa|china|internacional)\b/iu',
	);
	$target   = 'economia';
	foreach ( $rules as $slug => $pattern ) {
		if ( preg_match( $pattern, $haystack ) ) {
			$target = $slug;
			break;
		}
	}

	$term = get_term_by( 'slug', $target, 'category' );
	if ( ! $term || is_wp_error( $term ) ) {
		return;
	}

	wp_set_post_categories( $post_id, array( (int) $term->term_id ), false );
}
add_action( 'save_post_post', 'estrato_gate_ensure_post_category', 50 );

/**
 * Impede publicação de post sem imagem original da matéria.
 *
 * @param int $post_id
 */
function estrato_gate_require_original_thumbnail( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}
	$post = get_post( $post_id );
	if ( ! $post || 'post' !== $post->post_type || 'publish' !== $post->post_status ) {
		return;
	}
	if ( get_post_meta( $post_id, '_estrato_editorial_source', true ) ) {
		return;
	}
	if ( 'analysis' === get_post_meta( $post_id, '_estrato_content_mode', true ) && has_post_thumbnail( $post_id ) ) {
		return;
	}
	// Satélites: RSS niche feeds frequentemente sem enclosure — aceitar qualquer thumb
	// quando há fonte RSS e o portal não é finance.
	$portal = function_exists( 'estrato_nav_current_portal_id' ) ? estrato_nav_current_portal_id() : 'estrato-finance';
	if ( 'estrato-finance' !== $portal
		&& get_post_meta( $post_id, '_estrato_rss_source_url', true )
		&& has_post_thumbnail( $post_id ) ) {
		return;
	}
	if ( ! function_exists( 'estrato_bridge_post_has_original_thumbnail' ) ) {
		return;
	}
	if ( estrato_bridge_post_has_original_thumbnail( $post_id ) ) {
		return;
	}
	remove_action( 'save_post_post', 'estrato_gate_require_original_thumbnail', 100 );
	wp_update_post(
		array(
			'ID'          => $post_id,
			'post_status' => 'draft',
		)
	);
	add_action( 'save_post_post', 'estrato_gate_require_original_thumbnail', 100 );
	update_post_meta( $post_id, '_estrato_skip_reason', 'no_original_image' );
}
add_action( 'save_post_post', 'estrato_gate_require_original_thumbnail', 100 );

/**
 * Posts publicados antes de 2024-01-01 (para AR-SITEMAP-004).
 *
 * @return int
 */
function estrato_regression_pre2024_posts() {
	$ids = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'date_query'     => array(
				array(
					'before'    => '2024-01-01 00:00:00',
					'inclusive' => false,
					'column'    => 'post_date',
				),
			),
		)
	);
	return is_array( $ids ) ? count( $ids ) : 0;
}

/**
 * @param string $category_slug
 * @return string
 */
function estrato_gate_default_category_slug( $category_slug ) {
	if ( '' !== $category_slug ) {
		return $category_slug;
	}
	return 'economia';
}

/**
 * AR-CONTENT-005 — Impede publicação de posts com fonte RSS fora da matriz
 * de RSS do portal atual (`estrato_rss_import_matrix`).
 *
 * Bug de origem: auditoria visual 2026-07-13 (B2). Historicamente, um snapshot
 * antigo do RSS pipeline importou o preset `brasil-financeiro` em cada
 * satélite, categorizando forçadamente na editoria root default e contaminando
 * 66-82% do conteúdo com posts financeiros em portais de nicho (Culture,
 * Mente, Lifestyle, Science, Sustain). Este gate impede regressão.
 *
 * @param int $post_id
 */
function estrato_gate_require_source_in_matrix( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}
	$post = get_post( $post_id );
	if ( ! $post || 'post' !== $post->post_type || 'publish' !== $post->post_status ) {
		return;
	}
	if ( get_post_meta( $post_id, '_estrato_editorial_source', true ) ) {
		return;
	}
	if ( 'analysis' === get_post_meta( $post_id, '_estrato_content_mode', true ) ) {
		return;
	}
	// Fix pós V3-V8: publisher-bridge grava `_estrato_source_url`; RSS legado
	// grava `_estrato_rss_source_url`. Aceitar ambas.
	$src = get_post_meta( $post_id, '_estrato_rss_source_url', true );
	if ( ! $src ) {
		$src = get_post_meta( $post_id, '_estrato_source_url', true );
	}
	if ( ! $src ) {
		// Pipeline quebrado (ex.: syndication fatal antes do meta) → não fica público.
		remove_action( 'save_post_post', 'estrato_gate_require_source_in_matrix', 105 );
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'draft',
			)
		);
		add_action( 'save_post_post', 'estrato_gate_require_source_in_matrix', 105 );
		update_post_meta( $post_id, '_estrato_skip_reason', 'missing_source_url' );
		return;
	}

	$rss_src = (string) get_post_meta( $post_id, '_estrato_rss_source_url', true );
	// Pipeline rewrite (`_estrato_source_url` só) é livre de matriz no finance.
	// Off-matrix só bloqueia import RSS (meta RSS) ou satélites.
	$portal = function_exists( 'estrato_nav_current_portal_id' ) ? estrato_nav_current_portal_id() : 'estrato-finance';
	$is_rss = ( '' !== $rss_src );
	if ( ! $is_rss && 'estrato-finance' === $portal ) {
		return;
	}

	$src_host = strtolower( (string) wp_parse_url( $src, PHP_URL_HOST ) );
	if ( ! $src_host ) {
		return;
	}
	$allowed_hosts = estrato_gate_rss_matrix_hosts();
	if ( empty( $allowed_hosts ) ) {
		return;
	}
	// Aceitar www. e bare host.
	$bare = preg_replace( '/^www\./', '', $src_host );
	if ( isset( $allowed_hosts[ $src_host ] ) || isset( $allowed_hosts[ 'www.' . $bare ] ) || isset( $allowed_hosts[ $bare ] ) ) {
		return;
	}
	remove_action( 'save_post_post', 'estrato_gate_require_source_in_matrix', 105 );
	wp_update_post(
		array(
			'ID'          => $post_id,
			'post_status' => 'draft',
		)
	);
	add_action( 'save_post_post', 'estrato_gate_require_source_in_matrix', 105 );
	update_post_meta( $post_id, '_estrato_skip_reason', 'source_off_matrix' );
	update_post_meta( $post_id, '_estrato_skip_source_host', $src_host );
}
add_action( 'save_post_post', 'estrato_gate_require_source_in_matrix', 105 );

/**
 * Bloqueia categorias legado (AR-TAX-002): politica / tecnologia / brasil.
 *
 * @param int $post_id Post ID.
 */
function estrato_gate_block_legacy_categories( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}
	$post = get_post( $post_id );
	if ( ! $post || 'post' !== $post->post_type || 'publish' !== $post->post_status ) {
		return;
	}
	$legacy = array( 'politica', 'tecnologia', 'brasil', 'sem-categoria' );
	$slugs  = wp_get_post_terms( $post_id, 'category', array( 'fields' => 'slugs' ) );
	if ( is_wp_error( $slugs ) || ! $slugs ) {
		return;
	}
	$hit = array_values( array_intersect( $legacy, $slugs ) );
	if ( ! $hit ) {
		return;
	}
	$fallback_slug = function_exists( 'estrato_gate_default_category_slug' )
		? estrato_gate_default_category_slug( '' )
		: 'economia';
	$fallback      = get_term_by( 'slug', $fallback_slug, 'category' );
	$keep          = array();
	foreach ( $slugs as $slug ) {
		if ( in_array( $slug, $legacy, true ) ) {
			continue;
		}
		$term = get_term_by( 'slug', $slug, 'category' );
		if ( $term && ! is_wp_error( $term ) ) {
			$keep[] = (int) $term->term_id;
		}
	}
	if ( ! $keep && $fallback && ! is_wp_error( $fallback ) ) {
		$keep[] = (int) $fallback->term_id;
	}
	if ( $keep ) {
		wp_set_post_categories( $post_id, $keep, false );
	}
	// Conteúdo tipicamente off-editoria nestas cats: demota até revisão humana.
	remove_action( 'save_post_post', 'estrato_gate_block_legacy_categories', 110 );
	wp_update_post(
		array(
			'ID'          => $post_id,
			'post_status' => 'draft',
		)
	);
	add_action( 'save_post_post', 'estrato_gate_block_legacy_categories', 110 );
	update_post_meta( $post_id, '_estrato_skip_reason', 'legacy_category:' . implode( ',', $hit ) );
}
add_action( 'save_post_post', 'estrato_gate_block_legacy_categories', 110 );

/**
 * @return array<string, bool>
 */
function estrato_gate_rss_matrix_hosts() {
	$cached = wp_cache_get( 'estrato_gate_rss_matrix_hosts', 'estrato' );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	$matrix        = get_option( 'estrato_rss_import_matrix' );
	$allowed_hosts = array();
	if ( is_array( $matrix ) ) {
		foreach ( $matrix as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}
			$node = $entry['node'] ?? null;
			if ( is_string( $node ) ) {
				$decoded = json_decode( $node, true );
				if ( is_array( $decoded ) ) {
					$node = $decoded;
				}
			}
			$feeds = ( is_array( $node ) && ! empty( $node['feeds'] ) ) ? $node['feeds'] : ( ! empty( $entry['feeds'] ) && is_array( $entry['feeds'] ) ? $entry['feeds'] : array() );
			foreach ( (array) $feeds as $feed ) {
				$url = is_string( $feed ) ? $feed : ( isset( $feed['url'] ) ? (string) $feed['url'] : '' );
				if ( ! $url ) {
					continue;
				}
				$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
				if ( $host ) {
					$allowed_hosts[ $host ] = true;
				}
			}
		}
	}
	wp_cache_set( 'estrato_gate_rss_matrix_hosts', $allowed_hosts, 'estrato', 300 );
	return $allowed_hosts;
}

/**
 * Métrica para AR-CONTENT-005 — retorna quantos posts publicados têm
 * `_estrato_rss_source_url` fora da matriz.
 *
 * @return int
 */
function estrato_regression_off_matrix_posts() {
	$ids = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);
	if ( ! is_array( $ids ) ) {
		return 0;
	}
	$allowed = estrato_gate_rss_matrix_hosts();
	if ( empty( $allowed ) ) {
		return 0;
	}
	$off = 0;
	foreach ( $ids as $id ) {
		$src = get_post_meta( $id, '_estrato_rss_source_url', true );
		if ( ! $src ) {
			$src = get_post_meta( $id, '_estrato_source_url', true );
		}
		if ( ! $src ) {
			continue;
		}
		if ( get_post_meta( $id, '_estrato_editorial_source', true ) ) {
			continue;
		}
		if ( 'analysis' === get_post_meta( $id, '_estrato_content_mode', true ) ) {
			continue;
		}
		$host = strtolower( (string) wp_parse_url( $src, PHP_URL_HOST ) );
		if ( $host && ! isset( $allowed[ $host ] ) ) {
			++$off;
		}
	}
	return $off;
}
