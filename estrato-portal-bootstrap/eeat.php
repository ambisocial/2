<?php
/**
 * E-E-A-T — Sprint 3 (autores, avatars, footer, regression helpers).
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ESTRATO_CATEGORY_AUTHOR_OPTION', 'estrato_category_author_map' );

/**
 * @return array<string, int>
 */
function estrato_eeat_default_category_author_map() {
	return array(
		'economia'           => 0,
		'mercados'           => 0,
		'negocios'           => 0,
		'financas-pessoais'  => 0,
		'criptomoedas'       => 0,
		'agronegocio'        => 0,
		'mundo'              => 0,
	);
}

/**
 * @return array<string, int>
 */
function estrato_eeat_get_category_author_map() {
	$map = get_option( ESTRATO_CATEGORY_AUTHOR_OPTION, array() );
	if ( ! is_array( $map ) ) {
		$map = array();
	}

	$resolved = array();
	foreach ( estrato_eeat_default_category_author_map() as $slug => $_ ) {
		if ( ! empty( $map[ $slug ] ) ) {
			$resolved[ $slug ] = (int) $map[ $slug ];
			continue;
		}

		$author_slugs = array(
			'economia'          => 'ana-economia',
			'mercados'          => 'marcos-mercados',
			'negocios'          => 'lucia-negocios',
			'financas-pessoais' => 'pedro-financas',
			'criptomoedas'      => 'rafa-cripto',
			'agronegocio'       => 'julia-agro',
			'mundo'             => 'henrique-mundo',
		);

		if ( empty( $author_slugs[ $slug ] ) ) {
			continue;
		}

		$user = get_user_by( 'slug', $author_slugs[ $slug ] );
		if ( $user ) {
			$resolved[ $slug ] = (int) $user->ID;
		}
	}

	return $resolved;
}

/**
 * @param string $category_slug
 * @return int
 */
function estrato_eeat_resolve_author_id( $category_slug ) {
	$slug = sanitize_title( $category_slug );
	$map  = estrato_eeat_get_category_author_map();

	if ( ! empty( $map[ $slug ] ) ) {
		return (int) $map[ $slug ];
	}

	return 1;
}

/**
 * Avatar customizado por autor (ui-avatars importado no setup).
 *
 * @param string $url
 * @param mixed  $id_or_email
 * @return string
 */
function estrato_eeat_avatar_url( $url, $id_or_email ) {
	$user = null;

	if ( is_numeric( $id_or_email ) ) {
		$user = get_user_by( 'id', (int) $id_or_email );
	} elseif ( is_object( $id_or_email ) && ! empty( $id_or_email->user_id ) ) {
		$user = get_user_by( 'id', (int) $id_or_email->user_id );
	} elseif ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
		$user = get_user_by( 'email', $id_or_email );
	}

	if ( ! $user ) {
		return $url;
	}

	$custom = get_user_meta( $user->ID, 'estrato_avatar_url', true );
	if ( $custom && str_starts_with( $custom, 'http' ) ) {
		return esc_url_raw( $custom );
	}

	return $url;
}
add_filter( 'get_avatar_url', 'estrato_eeat_avatar_url', 10, 2 );

/**
 * Crédito legal no rodapé (CNPJ / razão social).
 */
function estrato_eeat_footer_legal() {
	if ( is_admin() ) {
		return;
	}
	?>
	<style>.estrato-footer-legal{display:block;margin-top:.5rem;font-size:.85rem;opacity:.85}</style>
	<script>
	document.addEventListener('DOMContentLoaded',function(){
		var b=document.querySelector('.pg-footer-bottom');
		if(!b||b.querySelector('.estrato-footer-legal'))return;
		var s=document.createElement('span');
		s.className='estrato-footer-legal';
		s.textContent='<?php echo esc_js( 'Estrato Mídia e Conteúdo Ltda. · CNPJ 45.678.912/0001-34 · São Paulo, SP' ); ?>';
		b.appendChild(s);
	});
	</script>
	<?php
}
add_action( 'wp_footer', 'estrato_eeat_footer_legal', 20 );

/**
 * Contagem de posts publicados cujo autor não tem bio (AR-EEAT-006).
 *
 * @return int
 */
function estrato_regression_authors_without_bio() {
	global $wpdb;

	$sql = "
		SELECT COUNT(DISTINCT p.ID)
		FROM {$wpdb->posts} p
		INNER JOIN {$wpdb->users} u ON u.ID = p.post_author
		LEFT JOIN {$wpdb->usermeta} um ON um.user_id = u.ID AND um.meta_key = 'description'
		WHERE p.post_type = 'post'
		  AND p.post_status = 'publish'
		  AND (um.meta_value IS NULL OR TRIM(um.meta_value) = '')
	";

	return (int) $wpdb->get_var( $sql );
}
