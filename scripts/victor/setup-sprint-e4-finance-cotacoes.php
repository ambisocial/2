<?php
/**
 * Sprint E4 — /cotacoes/ com APIs reais (Frankfurter + CoinGecko).
 *
 * Uso: wp eval-file setup-sprint-e4-finance-cotacoes.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

delete_transient( 'estrato_cotacoes_frankfurter' );
delete_transient( 'estrato_cotacoes_brl_cross' );
delete_transient( 'estrato_cotacoes_crypto' );

$page = get_page_by_path( 'cotacoes', OBJECT, 'page' );
$content = '<!-- wp:paragraph --><p>Acompanhe câmbio USD e pares relevantes para o Brasil, cruzamento a partir do real e referência de criptoativos. Dados de câmbio via <a href="https://www.frankfurter.app/">Frankfurter</a> (ECB). Cripto via <a href="https://www.coingecko.com/">CoinGecko</a>. Índices B3 podem ter delay de 15 minutos.</p><!-- /wp:paragraph -->'
	. '<!-- wp:shortcode -->[estrato_cotacoes]<!-- /wp:shortcode -->';

if ( $page ) {
	wp_update_post(
		array(
			'ID'           => $page->ID,
			'post_content' => $content,
		)
	);
	WP_CLI::log( "Página /cotacoes/ atualizada → #{$page->ID}" );
} else {
	$id = wp_insert_post(
		array(
			'post_title'   => 'Cotações',
			'post_name'    => 'cotacoes',
			'post_content' => $content,
			'post_status'  => 'publish',
			'post_type'    => 'page',
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		WP_CLI::error( $id->get_error_message() );
	}
	WP_CLI::log( "Página /cotacoes/ criada → #{$id}" );
}

$preview = function_exists( 'estrato_shortcode_cotacoes' ) ? estrato_shortcode_cotacoes() : '';
$has_fx  = false !== strpos( $preview, 'USD/' );
$has_crypto = false !== strpos( $preview, 'Bitcoin' );

WP_CLI::success(
	wp_json_encode(
		array(
			'portal'      => 'estrato-finance',
			'frankfurter' => $has_fx,
			'crypto'      => $has_crypto,
		),
		JSON_UNESCAPED_UNICODE
	)
);
