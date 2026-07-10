<?php
/**
 * Funde categorias legado (política, tecnologia, brasil) nas editorias financeiras.
 * Uso: ESTRATO_DRY_RUN=1 wp eval-file merge-legacy-categories.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$dry_run = '1' === getenv( 'ESTRATO_DRY_RUN' );

$merge_map = array();
if ( function_exists( 'estrato_rss_load_finance_taxonomy' ) ) {
	$taxonomy = estrato_rss_load_finance_taxonomy();
	if ( ! empty( $taxonomy['legacy_merge'] ) && is_array( $taxonomy['legacy_merge'] ) ) {
		$merge_map = $taxonomy['legacy_merge'];
	}
}
if ( empty( $merge_map ) ) {
	$merge_map = array(
		'politica'   => 'economia',
		'tecnologia' => 'negocios',
		'brasil'     => 'mundo',
	);
}

$moved   = 0;
$skipped = 0;

foreach ( $merge_map as $legacy_slug => $target_slug ) {
	$legacy = get_term_by( 'slug', $legacy_slug, 'category' );
	$target = get_term_by( 'slug', $target_slug, 'category' );
	if ( ! $legacy || is_wp_error( $legacy ) ) {
		++$skipped;
		continue;
	}
	if ( ! $target || is_wp_error( $target ) ) {
		WP_CLI::warning( "Alvo $target_slug ausente para legado $legacy_slug" );
		++$skipped;
		continue;
	}

	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'category'       => (int) $legacy->term_id,
			'fields'         => 'ids',
		)
	);

	foreach ( $posts as $post_id ) {
		if ( $dry_run ) {
			WP_CLI::log( "[dry-run] #$post_id $legacy_slug → $target_slug" );
			++$moved;
			continue;
		}

		$cats = wp_get_post_categories( $post_id );
		$cats = array_diff( $cats, array( (int) $legacy->term_id ) );
		$cats[] = (int) $target->term_id;
		$cats   = array_values( array_unique( array_map( 'intval', $cats ) ) );
		wp_set_post_categories( $post_id, $cats, false );
		++$moved;
	}

	if ( ! $dry_run ) {
		update_term_meta( (int) $legacy->term_id, 'wpseo_noindex', 'noindex' );
		$remaining = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'category'       => (int) $legacy->term_id,
				'fields'         => 'ids',
			)
		);
		if ( empty( $remaining ) ) {
			wp_update_term(
				(int) $legacy->term_id,
				'category',
				array( 'description' => 'Categoria arquivada — conteúdo migrado para editorias financeiras.' )
			);
		}
	}
}

$mode = $dry_run ? 'DRY-RUN' : 'APPLIED';
WP_CLI::success( "{$mode}: {$moved} posts migrados, {$skipped} categorias legado ausentes." );
