<?php
/**
 * Autores fictícios por editoria / subcategoria / coluna (E-E-A-T + RSS).
 *
 * Regra: ao criar subcategoria ou coluna nova na sync, garante autor WP com bio e retrato.
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ESTRATO_TERM_AUTHOR_OPTION', 'estrato_term_author_map' );

/**
 * Nomes fictícios determinísticos (pares fixos por hash do slug).
 *
 * @return array<int, array{first:string,last:string,gender:string}>
 */
function estrato_eeat_persona_name_pool() {
	return array(
		array( 'first' => 'Carolina', 'last' => 'Fischer', 'gender' => 'woman' ),
		array( 'first' => 'Rafael', 'last' => 'Moraes', 'gender' => 'man' ),
		array( 'first' => 'Beatriz', 'last' => 'Nogueira', 'gender' => 'woman' ),
		array( 'first' => 'Thiago', 'last' => 'Carvalho', 'gender' => 'man' ),
		array( 'first' => 'Mariana', 'last' => 'Duarte', 'gender' => 'woman' ),
		array( 'first' => 'Felipe', 'last' => 'Ramos', 'gender' => 'man' ),
		array( 'first' => 'Camila', 'last' => 'Pacheco', 'gender' => 'woman' ),
		array( 'first' => 'Gustavo', 'last' => 'Azevedo', 'gender' => 'man' ),
		array( 'first' => 'Larissa', 'last' => 'Monteiro', 'gender' => 'woman' ),
		array( 'first' => 'Diego', 'last' => 'Freitas', 'gender' => 'man' ),
	);
}

/**
 * @return array<string, int>
 */
function estrato_eeat_get_term_author_map() {
	$map = get_option( ESTRATO_TERM_AUTHOR_OPTION, array() );
	return is_array( $map ) ? $map : array();
}

/**
 * @param string $term_slug
 * @param int    $user_id
 */
function estrato_eeat_set_term_author( $term_slug, $user_id ) {
	$map                   = estrato_eeat_get_term_author_map();
	$map[ $term_slug ]     = (int) $user_id;
	update_option( ESTRATO_TERM_AUTHOR_OPTION, $map, false );
}

/**
 * Gera persona quando não há bloco `author` na taxonomia.
 *
 * @param string $parent_slug
 * @param string $sub_slug
 * @param string $sub_name
 * @param string $sub_desc
 * @param string $term_type subcategory|column
 * @return array<string, string>
 */
function estrato_eeat_build_persona( $parent_slug, $sub_slug, $sub_name, $sub_desc, $term_type = 'subcategory' ) {
	$pool = estrato_eeat_persona_name_pool();
	$idx  = abs( crc32( $parent_slug . '-' . $sub_slug ) ) % count( $pool );
	$pick = $pool[ $idx ];

	// Nome canônico da editoria: preferir o `name` real do termo (respeita
	// acentos, "&", capitalização). Fallback para whitelist e por último
	// para title-case do slug.
	$parent_term = get_term_by( 'slug', $parent_slug, 'category' );
	$parent_names = array(
		'economia'          => 'Economia',
		'mercados'          => 'Mercados',
		'negocios'          => 'Negócios',
		'financas-pessoais' => 'Finanças Pessoais',
		'criptomoedas'      => 'Criptomoedas',
		'agronegocio'       => 'Agronegócio',
		'mundo'             => 'Internacional',
	);
	if ( $parent_term && ! is_wp_error( $parent_term ) && ! empty( $parent_term->name ) ) {
		$parent_label = html_entity_decode( (string) $parent_term->name, ENT_QUOTES, 'UTF-8' );
	} else {
		$parent_label = $parent_names[ $parent_slug ] ?? ucwords( str_replace( '-', ' ', $parent_slug ) );
	}
	$sub_name = html_entity_decode( (string) $sub_name, ENT_QUOTES, 'UTF-8' );
	$role     = 'column' === $term_type ? 'Colunista' : 'Repórter';
	$display  = $pick['first'] . ' ' . $pick['last'];
	// Fix pós auditoria visual 2026-07-13: quando o "sub" é a própria editoria
	// (autor raiz), sub_name == parent_label — evitar "de Finanças Pessoais · Finanças Pessoais".
	if ( strcasecmp( trim( $sub_name ), trim( $parent_label ) ) === 0 ) {
		$job = $role . ' de ' . $parent_label;
	} else {
		$job = $role . ' de ' . $sub_name . ' · ' . $parent_label;
	}

	$bio = $sub_desc
		? $sub_desc . ' No Estrato, cobre ' . $sub_name . ' com foco em contexto e dados para o leitor brasileiro.'
		: 'Cobre ' . $sub_name . ' na editoria ' . $parent_label . ' do Estrato, com análise clara e linguagem acessível.';

	return array(
		'first_name'   => $pick['first'],
		'last_name'    => $pick['last'],
		'display_name' => $display,
		'job_title'    => $job,
		'bio'          => $bio,
		'gender'       => $pick['gender'],
	);
}

/**
 * @param array<string, string> $persona
 * @param string                $parent_slug
 * @param string                $sub_slug
 * @return string
 */
function estrato_eeat_author_login_from_slugs( $parent_slug, $sub_slug ) {
	return sanitize_title( $parent_slug . '-' . $sub_slug );
}

/**
 * Baixa retrato IA e anexa à biblioteca de mídia.
 *
 * @param int    $user_id
 * @param string $login
 * @param array<string, string> $persona
 * @return int Attachment ID ou 0.
 */
function estrato_eeat_sideload_portrait( $user_id, $login, $persona ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$existing = (int) get_user_meta( $user_id, 'estrato_avatar_attachment_id', true );
	if ( $existing && get_post( $existing ) ) {
		$url = wp_get_attachment_url( $existing );
		if ( $url ) {
			update_user_meta( $user_id, 'estrato_avatar_url', esc_url_raw( $url ) );
			return $existing;
		}
	}

	$display = $persona['display_name'] ?? 'Jornalista';
	$gender  = $persona['gender'] ?? 'person';
	$seed    = abs( crc32( $login ) );
	$prompt  = 'Black and white ultra realistic professional headshot portrait of a Brazilian ' . $gender
		. ' editorial journalist, age 36 to 45, natural skin texture, soft studio lighting, neutral gray background,'
		. ' monochrome photography, sharp eyes, looking at camera, no text, no watermark, no color';
	$img_url = 'https://image.pollinations.ai/prompt/' . rawurlencode( $prompt )
		. '?width=768&height=768&seed=' . $seed . '&nologo=true';

	$tmp = download_url( $img_url, 45 );
	if ( is_wp_error( $tmp ) ) {
		return 0;
	}

	$file_array = array(
		'name'     => 'estrato-author-' . $login . '.jpg',
		'tmp_name' => $tmp,
	);
	$attach_id  = media_handle_sideload( $file_array, 0, 'Retrato ' . $display );
	if ( is_wp_error( $attach_id ) ) {
		@unlink( $tmp );
		return 0;
	}

	$url = wp_get_attachment_url( $attach_id );
	if ( $url ) {
		update_user_meta( $user_id, 'estrato_avatar_url', esc_url_raw( $url ) );
		update_user_meta( $user_id, 'estrato_avatar_attachment_id', (int) $attach_id );
	}

	return (int) $attach_id;
}

/**
 * Cria ou atualiza autor WP para um termo (subcategoria/coluna).
 *
 * @param int                   $term_id
 * @param string                $full_slug   ex.: economia-macro
 * @param string                $parent_slug
 * @param string                $sub_slug
 * @param array<string, mixed>  $term_def
 * @param string                $term_type
 * @return int User ID ou 0.
 */
function estrato_eeat_ensure_author_for_term( $term_id, $full_slug, $parent_slug, $sub_slug, $term_def, $term_type = 'subcategory' ) {
	$map = estrato_eeat_get_term_author_map();
	if ( ! empty( $map[ $full_slug ] ) ) {
		$uid = (int) $map[ $full_slug ];
		if ( get_user_by( 'id', $uid ) ) {
			return $uid;
		}
	}

	$sub_name = (string) ( $term_def['name'] ?? $term_def['brand_name'] ?? $sub_slug );
	$sub_desc = (string) ( $term_def['description'] ?? '' );

	if ( ! empty( $term_def['author'] ) && is_array( $term_def['author'] ) ) {
		$a        = $term_def['author'];
		$persona  = array(
			'first_name'   => (string) ( $a['first_name'] ?? '' ),
			'last_name'    => (string) ( $a['last_name'] ?? '' ),
			'display_name' => (string) ( $a['display_name'] ?? trim( ( $a['first_name'] ?? '' ) . ' ' . ( $a['last_name'] ?? '' ) ) ),
			'job_title'    => (string) ( $a['job_title'] ?? '' ),
			'bio'          => (string) ( $a['bio'] ?? '' ),
			'gender'       => (string) ( $a['gender'] ?? 'person' ),
		);
	} else {
		$persona = estrato_eeat_build_persona( $parent_slug, $sub_slug, $sub_name, $sub_desc, $term_type );
	}

	if ( empty( $persona['display_name'] ) ) {
		return 0;
	}

	$login = estrato_eeat_author_login_from_slugs( $parent_slug, $sub_slug );
	$email = $login . '@authors.estrato.cc';

	$user_id = username_exists( $login );
	if ( ! $user_id ) {
		$user_id = wp_insert_user(
			array(
				'user_login'   => $login,
				'user_email'   => $email,
				'user_pass'    => wp_generate_password( 32, true, true ),
				'display_name' => $persona['display_name'],
				'first_name'   => $persona['first_name'],
				'last_name'    => $persona['last_name'],
				'description'  => $persona['bio'],
				'role'         => 'author',
				'user_url'     => home_url( '/author/' . $login . '/' ),
			)
		);
	} else {
		wp_update_user(
			array(
				'ID'           => $user_id,
				'display_name' => $persona['display_name'],
				'first_name'   => $persona['first_name'],
				'last_name'    => $persona['last_name'],
				'description'  => $persona['bio'],
				'role'         => 'author',
			)
		);
	}

	if ( is_wp_error( $user_id ) ) {
		return 0;
	}

	$job = $persona['job_title'] ?? '';
	update_user_meta( $user_id, 'estrato_job_title', $job );
	update_user_meta( $user_id, 'wpseo_job_title', $job );
	// sameAs apenas URLs internas verificáveis (arquivo + blog na página-mãe).
	$same_as = array(
		home_url( '/author/' . $login . '/' ),
		'https://estrato.cc/blog/' . sanitize_title( $login ) . '/',
	);
	update_user_meta( $user_id, 'estrato_same_as', $same_as );
	update_user_meta( $user_id, 'estrato_author_term_slug', $full_slug );
	update_user_meta( $user_id, 'estrato_author_parent', $parent_slug );
	update_user_meta( $user_id, 'estrato_author_type', $term_type );
	update_user_meta( $user_id, 'estrato_author_disclosure', 'Equipe editorial Estrato — perfil de cobertura da marca.' );
	update_term_meta( $term_id, 'estrato_author_user_id', (int) $user_id );

	estrato_eeat_sideload_portrait( (int) $user_id, $login, $persona );
	estrato_eeat_set_term_author( $full_slug, (int) $user_id );

	return (int) $user_id;
}

/**
 * Resolve autor: subcategoria/coluna > editoria > admin.
 *
 * @param string $category_slug  Slug editoria ou termo completo.
 * @param string $sub_slug       Sub-slug opcional.
 * @return int
 */
function estrato_eeat_resolve_author_id_for_terms( $category_slug, $sub_slug = '' ) {
	$category_slug = sanitize_title( $category_slug );
	$term_map      = estrato_eeat_get_term_author_map();

	$candidates = array();
	if ( $sub_slug ) {
		$candidates[] = sanitize_title( $category_slug . '-' . $sub_slug );
	}
	if ( str_contains( $category_slug, '-' ) ) {
		$candidates[] = $category_slug;
	}

	foreach ( $candidates as $slug ) {
		if ( ! empty( $term_map[ $slug ] ) ) {
			$uid = (int) $term_map[ $slug ];
			if ( get_user_by( 'id', $uid ) ) {
				return $uid;
			}
		}
	}

	return estrato_eeat_resolve_author_id( $category_slug );
}

/**
 * A partir das categorias de um post, escolhe o autor mais específico.
 *
 * @param int   $post_id
 * @param int   $fallback_editoria_id
 * @return int
 */
function estrato_eeat_resolve_author_for_post( $post_id, $fallback_editoria_id = 0 ) {
	$terms = get_the_category( $post_id );
	if ( ! $terms ) {
		return estrato_eeat_resolve_author_id( 'economia' );
	}

	$term_map = estrato_eeat_get_term_author_map();
	$deepest  = null;
	$depth    = -1;

	foreach ( $terms as $term ) {
		if ( is_wp_error( $term ) ) {
			continue;
		}
		$d = (int) $term->parent > 0 ? 2 : 1;
		if ( $d > $depth && ! empty( $term_map[ $term->slug ] ) ) {
			$depth   = $d;
			$deepest = $term;
		}
	}

	if ( $deepest && ! empty( $term_map[ $deepest->slug ] ) ) {
		return (int) $term_map[ $deepest->slug ];
	}

	$editoria_slug = '';
	foreach ( $terms as $term ) {
		if ( 0 === (int) $term->parent ) {
			$editoria_slug = $term->slug;
			break;
		}
	}
	if ( ! $editoria_slug && $fallback_editoria_id ) {
		$parent = get_term( $fallback_editoria_id, 'category' );
		if ( $parent && ! is_wp_error( $parent ) ) {
			$editoria_slug = $parent->slug;
		}
	}

	return $editoria_slug ? estrato_eeat_resolve_author_id( $editoria_slug ) : 1;
}

/**
 * Página de autor — destaque bio + especialidade.
 */
function estrato_eeat_author_archive_intro() {
	if ( ! is_author() ) {
		return;
	}
	$user = get_queried_object();
	if ( ! $user || empty( $user->ID ) ) {
		return;
	}
	$bio = get_user_meta( $user->ID, 'description', true );
	$job = get_user_meta( $user->ID, 'estrato_job_title', true );
	$term_slug = get_user_meta( $user->ID, 'estrato_author_term_slug', true );
	?>
	<style>
	.estrato-author-hero{display:flex;gap:1.25rem;align-items:flex-start;margin:1.5rem 0 2rem;padding:1.25rem;border:1px solid #e5e5e5;border-radius:8px}
	.estrato-author-hero img{border-radius:50%;width:96px;height:96px;object-fit:cover}
	.estrato-author-hero h1{font-size:1.5rem;margin:0 0 .35rem}
	.estrato-author-hero .estrato-author-job{opacity:.85;margin:0 0 .5rem;font-weight:600}
	.estrato-author-hero .estrato-author-bio{margin:0;line-height:1.5}
	</style>
	<div class="estrato-author-hero">
		<?php echo get_avatar( $user->ID, 96 ); ?>
		<div>
			<h1><?php echo esc_html( $user->display_name ); ?></h1>
			<?php if ( $job ) : ?>
				<p class="estrato-author-job"><?php echo esc_html( $job ); ?></p>
			<?php endif; ?>
			<?php if ( $bio ) : ?>
				<p class="estrato-author-bio"><?php echo esc_html( $bio ); ?></p>
			<?php endif; ?>
			<?php if ( $term_slug ) :
				$term_obj = get_term_by( 'slug', $term_slug, 'category' );
				if ( $term_obj && ! is_wp_error( $term_obj ) ) : ?>
				<p class="estrato-author-bio"><a href="<?php echo esc_url( get_category_link( $term_obj ) ); ?>">Ver cobertura em <?php echo esc_html( $term_obj->name ); ?></a></p>
			<?php endif; endif; ?>
		</div>
	</div>
	<?php
}
add_action( 'loop_start', function () {
	if ( is_author() && in_the_loop() && 0 === get_query_var( 'paged', 0 ) ) {
		static $done = false;
		if ( ! $done ) {
			$done = true;
			estrato_eeat_author_archive_intro();
		}
	}
}, 5 );

/**
 * @return int
 */
function estrato_regression_term_authors_count() {
	return count( estrato_eeat_get_term_author_map() );
}
