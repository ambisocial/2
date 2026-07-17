<?php
/**
 * Sprint E1 — autores com Person schema (3–7 por portal).
 *
 * - Sync taxonomia + autores por editoria/subcategoria
 * - jobTitle, bio, sameAs (LinkedIn), retrato
 * - Reatribui posts a autores com bio
 *
 * Uso: ESTRATO_PORTAL=estrato-mind wp eval-file setup-sprint-e1-portal-authors.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$portal = getenv( 'ESTRATO_PORTAL' ) ?: '';
if ( '' === $portal && function_exists( 'estrato_nav_current_portal_id' ) ) {
	$portal = estrato_nav_current_portal_id();
}

$presets = array(
	'estrato-finance'   => 'brasil-financeiro',
	'estrato-mind'      => 'brasil-mind',
	'estrato-lifestyle' => 'brasil-lifestyle',
	'estrato-science'   => 'brasil-science',
	'estrato-sustain'   => 'brasil-sustain',
	'estrato-culture'   => 'brasil-culture',
);

$preset = $presets[ $portal ] ?? 'brasil-financeiro';

if ( function_exists( 'estrato_rss_sync_portal_taxonomy' ) ) {
	estrato_rss_sync_portal_taxonomy( $preset );
}

$same_as_patched = 0;
foreach ( get_users( array( 'role' => 'author', 'fields' => 'all' ) ) as $user ) {
	$login = $user->user_login;
	// sameAs apenas URLs internas verificáveis (nunca LinkedIn fabricado).
	$same_as = get_user_meta( $user->ID, 'estrato_same_as', true );
	$needs   = empty( $same_as );
	if ( is_string( $same_as ) && preg_match( '#linkedin\.com/in/#i', $same_as ) ) {
		$needs = true;
	}
	if ( is_array( $same_as ) ) {
		foreach ( $same_as as $u ) {
			if ( preg_match( '#linkedin\.com/in/#i', (string) $u ) ) {
				$needs = true;
				break;
			}
		}
	}
	if ( $needs ) {
		update_user_meta(
			$user->ID,
			'estrato_same_as',
			array(
				home_url( '/author/' . sanitize_title( $login ) . '/' ),
				'https://estrato.cc/blog/' . sanitize_title( $login ) . '/',
			)
		);
		++$same_as_patched;
	}
	$job = get_user_meta( $user->ID, 'estrato_job_title', true );
	if ( ! $job ) {
		$job = 'Repórter · ' . get_bloginfo( 'name' );
		update_user_meta( $user->ID, 'estrato_job_title', $job );
		update_user_meta( $user->ID, 'wpseo_job_title', $job );
	}
}

$a2 = dirname( __FILE__ ) . '/setup-sprint-a2-portal-authors.php';
if ( is_readable( $a2 ) ) {
	require $a2;
	exit( 0 );
}

WP_CLI::error( 'setup-sprint-a2-portal-authors.php ausente' );
