<?php
/**
 * Onda 0 — Autores por subcategoria + retratos IA + validação RSS.
 *
 * Uso: wp eval-file setup-estrato-authors-wave0.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

/**
 * Atualiza retrato IA dos autores de editoria existentes.
 */
function estrato_wave0_upgrade_editoria_portraits() {
	$editoria_logins = array(
		'ana-economia',
		'marcos-mercados',
		'lucia-negocios',
		'pedro-financas',
		'rafa-cripto',
		'julia-agro',
		'henrique-mundo',
	);
	$genders = array(
		'ana-economia'      => 'woman',
		'marcos-mercados'   => 'man',
		'lucia-negocios'    => 'woman',
		'pedro-financas'    => 'man',
		'rafa-cripto'       => 'man',
		'julia-agro'        => 'woman',
		'henrique-mundo'    => 'man',
	);

	foreach ( $editoria_logins as $login ) {
		$user = get_user_by( 'login', $login );
		if ( ! $user ) {
			WP_CLI::warning( "Editoria autor ausente: $login" );
			continue;
		}
		if ( ! function_exists( 'estrato_eeat_sideload_portrait' ) ) {
			continue;
		}
		$persona = array(
			'display_name' => $user->display_name,
			'gender'       => $genders[ $login ] ?? 'person',
		);
		$aid = estrato_eeat_sideload_portrait( (int) $user->ID, $login, $persona );
		WP_CLI::log( "Retrato editoria $login → attachment #$aid" );
	}
}

// 1) Sync taxonomia (cria subcategorias + autores via hook).
if ( function_exists( 'estrato_rss_sync_portal_taxonomy' ) ) {
	$sync = estrato_rss_sync_portal_taxonomy();
	WP_CLI::log( 'Taxonomia sync: ' . wp_json_encode( $sync ) );
} else {
	WP_CLI::error( 'estrato_rss_sync_portal_taxonomy ausente — ative estrato-rss-bootstrap' );
}

// 2) Retratos IA para autores de editoria.
estrato_wave0_upgrade_editoria_portraits();

// 3) Mapa de autores.
$term_map = function_exists( 'estrato_eeat_get_term_author_map' )
	? estrato_eeat_get_term_author_map()
	: array();
WP_CLI::log( 'Autores por termo: ' . count( $term_map ) );
foreach ( $term_map as $slug => $uid ) {
	$u = get_user_by( 'id', (int) $uid );
	WP_CLI::log( "  [$slug] → " . ( $u ? $u->display_name . " (/author/{$u->user_nicename}/)" : "#$uid" ) );
}

// 4) Reatribuir posts recentes sem autor de subnicho (amostra 200).
if ( function_exists( 'estrato_eeat_resolve_author_for_post' ) ) {
	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'fields'         => 'ids',
		)
	);
	$updated = 0;
	foreach ( $posts as $pid ) {
		$new_author = estrato_eeat_resolve_author_for_post( (int) $pid );
		if ( $new_author && (int) get_post_field( 'post_author', $pid ) !== $new_author ) {
			wp_update_post(
				array(
					'ID'          => (int) $pid,
					'post_author' => $new_author,
				)
			);
			++$updated;
		}
	}
	WP_CLI::log( "Posts reatribuídos a autores de subnicho: $updated" );
}

// 5) Validar feed (simula o que quebrava com the_author).
ob_start();
global $wp_query;
$GLOBALS['wp_the_query'] = new WP_Query(
	array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => 3,
	)
);
$wp_query = $GLOBALS['wp_the_query'];
while ( have_posts() ) {
	the_post();
	$author_name = get_the_author();
	if ( str_contains( $author_name, 'internal_server_error' ) || str_contains( $author_name, 'Erro' ) ) {
		ob_end_clean();
		WP_CLI::error( 'Feed author ainda quebrado: ' . $author_name );
	}
	WP_CLI::log( 'Feed author OK: ' . $author_name );
}
ob_end_clean();
wp_reset_postdata();

WP_CLI::success( 'Onda 0: autores + RSS validados.' );
