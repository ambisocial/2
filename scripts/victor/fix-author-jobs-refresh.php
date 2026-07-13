<?php
/**
 * Refresh dos meta `estrato_job_title` / `wpseo_job_title` / `description` /
 * `display_name` de todas as personas cujo `user_login` corresponde a um
 * par (parent_slug, sub_slug) do site atual — usa `estrato_eeat_build_persona()`
 * para garantir que os nomes reais dos termos (incluindo acentos e "&") sejam
 * refletidos.
 *
 * Sintoma corrigido: bylines como "Repórter de IA criativa · Ia seguranca"
 * onde o parent_label vinha do title-case do slug em vez do `term->name`.
 *
 * Uso:
 *   sudo -u www-data wp --path=<web_root> eval-file <this>
 *   ESTRATO_FIX_JOBS_REFRESH_DRY_RUN=1 sudo -u www-data wp ... eval-file <this>
 *
 * Idempotente: só grava se o valor mudou.
 */

$dry_run = ( ! empty( getenv( 'ESTRATO_FIX_JOBS_REFRESH_DRY_RUN' ) ) && '0' !== getenv( 'ESTRATO_FIX_JOBS_REFRESH_DRY_RUN' ) );

if ( ! function_exists( 'estrato_eeat_build_persona' ) ) {
	echo "SKIP: estrato_eeat_build_persona() não disponível — plugin não carregado.\n";
	return;
}

$users = get_users(
	array(
		'role__in' => array( 'author', 'contributor', 'editor' ),
		'number'   => -1,
	)
);

echo 'Portal: ' . home_url() . "\n";
echo 'Modo:   ' . ( $dry_run ? 'DRY-RUN' : 'APPLY' ) . "\n";
echo 'Users:  ' . count( $users ) . "\n---\n";

$fixed = 0;
foreach ( $users as $u ) {
	$login = (string) $u->user_login;
	// só personas geradas: padrão `{parent-slug}-{sub-slug}` ou terminado em `-editoria|-coluna`.
	// Descobrir sub_slug: se user_meta `estrato_author_term_slug` existe usa-o.
	$term_slug = (string) get_user_meta( $u->ID, 'estrato_author_term_slug', true );
	$term_type = (string) get_user_meta( $u->ID, 'estrato_author_type', true );
	if ( '' === $term_slug ) {
		continue;
	}
	$term = get_term_by( 'slug', $term_slug, 'category' );
	if ( ! $term || is_wp_error( $term ) ) {
		continue;
	}
	// Identifica parent e sub. Se o term é raiz, sub_slug = parent_slug (persona raiz).
	if ( $term->parent ) {
		$parent = get_term( $term->parent, 'category' );
		if ( ! $parent || is_wp_error( $parent ) ) {
			continue;
		}
		$parent_slug = $parent->slug;
		$sub_slug    = $term->slug;
		$sub_name    = $term->name;
		$sub_desc    = (string) $term->description;
	} else {
		$parent_slug = $term->slug;
		$sub_slug    = $term->slug;
		$sub_name    = $term->name;
		$sub_desc    = (string) $term->description;
	}
	if ( ! $term_type ) {
		$term_type = 'subcategory';
	}
	$persona = estrato_eeat_build_persona( $parent_slug, $sub_slug, $sub_name, $sub_desc, $term_type );
	if ( empty( $persona['job_title'] ) ) {
		continue;
	}
	$new_job     = (string) $persona['job_title'];
	$new_bio     = (string) $persona['bio'];
	$new_display = (string) $persona['display_name'];
	$old_job     = (string) get_user_meta( $u->ID, 'estrato_job_title', true );
	$old_bio     = (string) get_user_meta( $u->ID, 'description', true );
	$old_display = (string) $u->display_name;

	$changed = array();
	if ( $new_job !== $old_job ) {
		$changed[] = 'job';
	}
	if ( $new_bio !== $old_bio ) {
		$changed[] = 'bio';
	}
	// display_name: só corrigir se está vazio; nome de persona é aleatório e não queremos randomizar retroativamente
	if ( '' === $old_display && $new_display ) {
		$changed[] = 'display_name';
	}
	if ( ! $changed ) {
		continue;
	}
	echo sprintf( "  ID=%d login=%s changes=%s\n     old_job=%s\n     new_job=%s\n", $u->ID, $login, implode( ',', $changed ), $old_job, $new_job );
	if ( ! $dry_run ) {
		if ( in_array( 'job', $changed, true ) ) {
			update_user_meta( $u->ID, 'estrato_job_title', $new_job );
			update_user_meta( $u->ID, 'wpseo_job_title', $new_job );
		}
		if ( in_array( 'bio', $changed, true ) ) {
			wp_update_user( array( 'ID' => $u->ID, 'description' => $new_bio ) );
		}
		if ( in_array( 'display_name', $changed, true ) ) {
			wp_update_user( array( 'ID' => $u->ID, 'display_name' => $new_display ) );
		}
	}
	++$fixed;
}

echo "---\nfixed: $fixed\n";
