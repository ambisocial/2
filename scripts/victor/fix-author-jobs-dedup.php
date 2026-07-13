<?php
/**
 * Corrige o meta `estrato_job_title` / `wpseo_job_title` de autores que
 * receberam "Repórter de X · X" (sub_name == parent_label).
 *
 * Bug de origem: `estrato_eeat_persona_from_terms()` concatenava
 * `$sub_name . ' · ' . $parent_label` mesmo quando os dois valores eram
 * idênticos (autor raiz da editoria).
 *
 * Uso:
 *   sudo -u www-data wp --path=<web_root> eval-file <this>
 *   ESTRATO_FIX_JOBS_DRY_RUN=1 sudo -u www-data wp ... eval-file <this>
 *
 * Idempotente: só grava se o valor mudou.
 */

$dry_run = ( ! empty( getenv( 'ESTRATO_FIX_JOBS_DRY_RUN' ) ) && '0' !== getenv( 'ESTRATO_FIX_JOBS_DRY_RUN' ) );

$users = get_users(
	array(
		'role__in' => array( 'author', 'contributor', 'editor', 'administrator' ),
		'number'   => -1,
		'fields'   => 'ID',
	)
);

echo 'Portal: ' . home_url() . "\n";
echo 'Modo:   ' . ( $dry_run ? 'DRY-RUN' : 'APPLY' ) . "\n";
echo 'Users:  ' . count( $users ) . "\n---\n";

$fixed = 0;
foreach ( $users as $user_id ) {
	$job = (string) get_user_meta( $user_id, 'estrato_job_title', true );
	if ( '' === $job || false === strpos( $job, ' · ' ) ) {
		continue;
	}
	list( $left, $right ) = array_map( 'trim', explode( ' · ', $job, 2 ) );
	// left = "Repórter de X" ou "Colunista de X"
	// right = "X" (parent_label)
	if ( '' === $right ) {
		continue;
	}
	if ( preg_match( '/^(Repórter|Colunista) de (.+)$/u', $left, $m ) ) {
		if ( 0 === strcasecmp( trim( $m[2] ), $right ) ) {
			$new_job = $left; // "Repórter de X" — sem o duplicado
			$u       = get_user_by( 'id', $user_id );
			$login   = $u ? $u->user_login : '';
			echo sprintf( "  ID=%d login=%s old=%s new=%s\n", $user_id, $login, $job, $new_job );
			if ( ! $dry_run ) {
				update_user_meta( $user_id, 'estrato_job_title', $new_job );
				update_user_meta( $user_id, 'wpseo_job_title', $new_job );
			}
			++$fixed;
		}
	}
}

echo "---\nfixed: $fixed\n";
