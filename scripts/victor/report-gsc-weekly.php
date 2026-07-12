<?php
/**
 * Sprint 10 — Relatório semanal por categoria (métricas WP + slot GSC).
 *
 * Uso: wp eval-file report-gsc-weekly.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

/**
 * @param string $slug
 * @param int    $days
 * @return int
 */
function estrato_ops_posts_last_days( $slug, $days = 7 ) {
	$term = get_term_by( 'slug', $slug, 'category' );
	if ( ! $term || is_wp_error( $term ) ) {
		return 0;
	}
	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'category'       => (int) $term->term_id,
			'fields'         => 'ids',
			'date_query'     => array(
				array(
					'after' => gmdate( 'Y-m-d', strtotime( "-{$days} days" ) ),
				),
			),
		)
	);
	return is_array( $posts ) ? count( $posts ) : 0;
}

$portal = getenv( 'ESTRATO_PORTAL' ) ?: '';
if ( '' === $portal && function_exists( 'estrato_nav_current_portal_id' ) ) {
	$portal = estrato_nav_current_portal_id();
}

if ( function_exists( 'estrato_aeo_portal_editorias' ) ) {
	$slugs = estrato_aeo_portal_editorias();
} elseif ( function_exists( 'estrato_rss_get_finance_menu_order' ) ) {
	$slugs = estrato_rss_get_finance_menu_order();
} else {
	$slugs = array( 'economia', 'mercados', 'negocios', 'financas-pessoais', 'criptomoedas', 'agronegocio', 'mundo' );
}

$ratio = function_exists( 'estrato_regression_word_ratio' ) ? estrato_regression_word_ratio() : -1;
$thin  = function_exists( 'estrato_regression_thin_posts' ) ? estrato_regression_thin_posts() : -1;
$feed  = function_exists( 'estrato_rss_feed_health_ratio' ) ? estrato_rss_feed_health_ratio() : -1;
$gsc   = get_option( 'estrato_gsc_manual_metrics', array() );

$lines   = array();
$lines[] = '# Relatório semanal ' . get_bloginfo( 'name' ) . ' — ' . gmdate( 'Y-m-d' );
if ( $portal ) {
	$lines[] = 'Portal: `' . $portal . '`';
}
$lines[] = '';
$lines[] = '## Resumo portal';
$lines[] = sprintf( '- Posts 300+ palavras: **%.1f%%**', $ratio >= 0 ? $ratio * 100 : 0 );
$lines[] = sprintf( '- Posts finos (<200): **%d**', max( 0, (int) $thin ) );
$lines[] = sprintf( '- Saúde feeds RSS: **%.0f%%**', $feed >= 0 ? $feed * 100 : 0 );
$lines[] = '';
$lines[] = '## Publicações por editoria (últimos 7 dias)';
$lines[] = '| Editoria | Novos posts | GSC impressões* | GSC cliques* |';
$lines[] = '|----------|-------------|-----------------|--------------|';

foreach ( $slugs as $slug ) {
	$new   = estrato_ops_posts_last_days( $slug, 7 );
	$imp   = isset( $gsc[ $slug ]['impressions'] ) ? (int) $gsc[ $slug ]['impressions'] : '—';
	$clk   = isset( $gsc[ $slug ]['clicks'] ) ? (int) $gsc[ $slug ]['clicks'] : '—';
	$lines[] = sprintf( '| %s | %d | %s | %s |', $slug, $new, $imp, $clk );
}

$lines[] = '';
$last_sync = get_option( 'estrato_gsc_last_sync', '' );
$lines[] = '*Colunas GSC: `python3 scripts/victor/sync-gsc-metrics.py` (última sync: ' . ( $last_sync ? $last_sync : 'nunca' ) . ').';
$lines[] = '';
$lines[] = '## Ações recomendadas';
if ( $feed >= 0 && $feed < 0.8 ) {
	$lines[] = '- Rodar `bash scripts/victor/setup-estrato-rss-curation.sh` (feeds <80% saudáveis).';
}
if ( $thin > 0 ) {
	$lines[] = '- Rodar `enrich-mid-posts.php` / `enrich-all-thin-posts.php`.';
}
$lines[] = '- Verificar cobertura GSC por `/category/{slug}/` antes de indexar próximo silo.';

$report = implode( "\n", $lines ) . "\n";
$dir    = '/var/www/estrato/reports';
if ( ! is_dir( $dir ) ) {
	wp_mkdir_p( $dir );
}
$file = $dir . '/gsc-weekly-' . gmdate( 'Y-m-d' ) . '.md';
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
file_put_contents( $file, $report, LOCK_EX );

update_option( 'estrato_ops_last_weekly_report', gmdate( 'c' ), false );
update_option( 'estrato_ops_last_weekly_report_path', $file, false );

echo "weekly_report=$file\n";
echo $report;
