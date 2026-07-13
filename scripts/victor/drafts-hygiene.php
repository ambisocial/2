<?php
/**
 * V8 (auditoria visual 2026-07-13) — Higiene de rascunhos e itens em trash.
 *
 * Regras padrão (configuráveis via env):
 *   - `draft` mais antigo que DRAFT_MAX_DAYS (default 30) → trash;
 *   - `trash` mais antigo que TRASH_MAX_DAYS (default 60) → delete forçado.
 *
 * Uso:
 *   wp eval-file scripts/victor/drafts-hygiene.php
 *   ESTRATO_HYGIENE_DRY_RUN=1 wp ... (dry-run)
 *   ESTRATO_DRAFT_MAX_DAYS=45 wp ...
 */

$dry_run         = ! empty( getenv( 'ESTRATO_HYGIENE_DRY_RUN' ) ) && '0' !== getenv( 'ESTRATO_HYGIENE_DRY_RUN' );
$draft_max_days  = (int) ( getenv( 'ESTRATO_DRAFT_MAX_DAYS' ) ?: 30 );
$trash_max_days  = (int) ( getenv( 'ESTRATO_TRASH_MAX_DAYS' ) ?: 60 );

echo 'Portal: ' . home_url() . "\n";
echo 'Modo: ' . ( $dry_run ? 'DRY-RUN' : 'APPLY' ) . "\n";
echo "Draft > $draft_max_days d → trash | Trash > $trash_max_days d → delete\n---\n";

$now = time();

$draft_cutoff = gmdate( 'Y-m-d H:i:s', $now - ( $draft_max_days * DAY_IN_SECONDS ) );
$draft_ids = get_posts(
	array(
		'post_type'      => 'post',
		'post_status'    => 'draft',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'date_query'     => array(
			array(
				'before'    => $draft_cutoff,
				'column'    => 'post_modified_gmt',
				'inclusive' => true,
			),
		),
		'no_found_rows'  => true,
	)
);
$to_trash = is_array( $draft_ids ) ? count( $draft_ids ) : 0;
echo "Draft candidates (>$draft_max_days d since modification): $to_trash\n";

$trashed = 0;
if ( ! $dry_run && $to_trash > 0 ) {
	foreach ( $draft_ids as $id ) {
		if ( wp_trash_post( $id ) ) {
			update_post_meta( $id, '_estrato_hygiene_trashed_at', current_time( 'mysql', true ) );
			++$trashed;
		}
	}
}
echo "  → trashed: $trashed\n";

$trash_cutoff = gmdate( 'Y-m-d H:i:s', $now - ( $trash_max_days * DAY_IN_SECONDS ) );
$trash_ids = get_posts(
	array(
		'post_type'      => 'post',
		'post_status'    => 'trash',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'date_query'     => array(
			array(
				'before'    => $trash_cutoff,
				'column'    => 'post_modified_gmt',
				'inclusive' => true,
			),
		),
		'no_found_rows'  => true,
	)
);
$to_delete = is_array( $trash_ids ) ? count( $trash_ids ) : 0;
echo "Trash candidates (>$trash_max_days d since modification): $to_delete\n";

$deleted = 0;
if ( ! $dry_run && $to_delete > 0 ) {
	foreach ( $trash_ids as $id ) {
		if ( wp_delete_post( $id, true ) ) {
			++$deleted;
		}
	}
}
echo "  → deleted: $deleted\n";
echo "---\n";
