<?php
/**
 * V6 (auditoria visual 2026-07-13) — Corrige dois problemas de URL:
 *
 * 1. Slugs de subcategorias com prefixo redundante do pai
 *    (`jogos-imaginacao/jogos-imaginacao-rpg-mesa-osr` → `jogos-imaginacao/rpg-mesa-osr`).
 * 2. Adiciona redirect 301 legado de `/{slug}/` (raiz sem `/category/`) para
 *    `/category/{slug}/` — evita 404 em links diretos para categorias, que
 *    apareciam em auditoria (ex.: /narrativas-som/ → 404).
 *
 * Uso:
 *   wp --path=/var/www/... eval-file scripts/victor/fix-category-urls-and-slugs.php
 *   ESTRATO_SLUG_DRY_RUN=1 wp ... (para dry-run)
 */

$dry_run = ! empty( getenv( 'ESTRATO_SLUG_DRY_RUN' ) ) && '0' !== getenv( 'ESTRATO_SLUG_DRY_RUN' );

echo 'Portal: ' . home_url() . "\n";
echo 'Modo: ' . ( $dry_run ? 'DRY-RUN' : 'APPLY' ) . "\n---\n";

$terms = get_terms(
	array(
		'taxonomy'   => 'category',
		'hide_empty' => false,
	)
);

if ( is_wp_error( $terms ) ) {
	echo "ERRO: " . $terms->get_error_message() . "\n";
	return;
}

$renamed = 0;
$skipped = 0;
foreach ( $terms as $term ) {
	if ( ! $term->parent ) {
		continue;
	}
	$parent = get_term( $term->parent, 'category' );
	if ( ! $parent || is_wp_error( $parent ) ) {
		continue;
	}
	$expected_prefix = $parent->slug . '-';
	if ( strpos( $term->slug, $expected_prefix ) !== 0 ) {
		continue;
	}
	$new_slug = substr( $term->slug, strlen( $expected_prefix ) );
	if ( '' === $new_slug ) {
		continue;
	}
	if ( get_term_by( 'slug', $new_slug, 'category' ) ) {
		$new_slug = $parent->slug . '-child-' . $new_slug;
	}
	echo sprintf(
		"%-8s parent=%s | %s → %s\n",
		$dry_run ? 'DRY' : 'RENAME',
		$parent->slug,
		$term->slug,
		$new_slug
	);
	if ( ! $dry_run ) {
		$res = wp_update_term( $term->term_id, 'category', array( 'slug' => $new_slug ) );
		if ( is_wp_error( $res ) ) {
			echo "  ERRO: " . $res->get_error_message() . "\n";
			++$skipped;
			continue;
		}
		update_term_meta( $term->term_id, '_estrato_old_slug', $term->slug );
		++$renamed;
	}
}
echo "---\nRenamed: $renamed | Skipped: $skipped | Total children checked: " . count( $terms ) . "\n";

if ( ! $dry_run && $renamed > 0 ) {
	flush_rewrite_rules( false );
	echo "flush_rewrite_rules(): OK\n";
}
