<?php
/**
 * Ticker BR — IBOV, USD/BRL, Selic, IPCA, Bitcoin (Sprint 5).
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return array<string, mixed>
 */
function estrato_ticker_fetch_data() {
	$cached = get_transient( 'estrato_ticker_br_v2' );
	if ( false !== $cached && is_array( $cached ) ) {
		return $cached;
	}

	$data = array(
		'ibov'   => array( 'label' => 'IBOVESPA', 'value' => '—', 'change' => 0 ),
		'usdbrl' => array( 'label' => 'USD/BRL', 'value' => '—', 'change' => 0 ),
		'selic'  => array( 'label' => 'SELIC', 'value' => '—', 'change' => 0 ),
		'ipca'   => array( 'label' => 'IPCA 12m', 'value' => '—', 'change' => 0 ),
		'btc'    => array( 'label' => 'BITCOIN', 'value' => '—', 'change' => 0 ),
		'updated'=> gmdate( 'H:i' ),
	);

	$ibov = wp_remote_get( 'https://brapi.dev/api/quote/%5EBVSP', array( 'timeout' => 10 ) );
	if ( ! is_wp_error( $ibov ) ) {
		$body = json_decode( wp_remote_retrieve_body( $ibov ), true );
		if ( ! empty( $body['results'][0] ) ) {
			$r = $body['results'][0];
			$data['ibov'] = array(
				'label'  => 'IBOVESPA',
				'value'  => number_format_i18n( (float) ( $r['regularMarketPrice'] ?? 0 ), 0 ),
				'change' => (float) ( $r['regularMarketChangePercent'] ?? 0 ),
			);
		}
	}

	$fx = wp_remote_get( 'https://economia.awesomeapi.com.br/json/last/USD-BRL', array( 'timeout' => 10 ) );
	if ( ! is_wp_error( $fx ) ) {
		$body = json_decode( wp_remote_retrieve_body( $fx ), true );
		if ( ! empty( $body['USDBRL'] ) ) {
			$r = $body['USDBRL'];
			$data['usdbrl'] = array(
				'label'  => 'USD/BRL',
				'value'  => number_format_i18n( (float) ( $r['bid'] ?? 0 ), 2 ),
				'change' => (float) ( $r['pctChange'] ?? 0 ),
			);
		}
	}

	$selic = wp_remote_get( 'https://api.bcb.gov.br/dados/serie/bcdata.sgs.432/dados/ultimos/1?formato=json', array( 'timeout' => 10 ) );
	if ( ! is_wp_error( $selic ) ) {
		$body = json_decode( wp_remote_retrieve_body( $selic ), true );
		if ( ! empty( $body[0]['valor'] ) ) {
			$data['selic'] = array(
				'label'  => 'SELIC',
				'value'  => number_format_i18n( (float) $body[0]['valor'], 2 ) . '%',
				'change' => 0,
			);
		}
	}

	$ipca = wp_remote_get( 'https://api.bcb.gov.br/dados/serie/bcdata.sgs.13522/dados/ultimos/1?formato=json', array( 'timeout' => 10 ) );
	if ( ! is_wp_error( $ipca ) ) {
		$body = json_decode( wp_remote_retrieve_body( $ipca ), true );
		if ( ! empty( $body[0]['valor'] ) ) {
			$data['ipca'] = array(
				'label'  => 'IPCA 12m',
				'value'  => number_format_i18n( (float) $body[0]['valor'], 1 ) . '%',
				'change' => 0,
			);
		}
	}

	$btc = wp_remote_get( 'https://economia.awesomeapi.com.br/json/last/BTC-USD', array( 'timeout' => 10 ) );
	if ( ! is_wp_error( $btc ) ) {
		$body = json_decode( wp_remote_retrieve_body( $btc ), true );
		if ( ! empty( $body['BTCUSD'] ) ) {
			$r = $body['BTCUSD'];
			$data['btc'] = array(
				'label'  => 'BITCOIN',
				'value'  => 'US$ ' . number_format_i18n( (float) ( $r['bid'] ?? 0 ), 0 ),
				'change' => (float) ( $r['pctChange'] ?? 0 ),
			);
		}
	}

	set_transient( 'estrato_ticker_br_v2', $data, 15 * MINUTE_IN_SECONDS );
	return $data;
}

/**
 * @param array{label:string,value:string,change:float} $item
 * @return string
 */
function estrato_ticker_format_item( $item ) {
	$change = (float) ( $item['change'] ?? 0 );
	$arrow  = $change >= 0 ? '▲' : '▼';
	$class  = $change >= 0 ? 'estrato-price-up' : 'estrato-price-down';
	$pct    = abs( $change ) > 0.001 ? number_format_i18n( abs( $change ), 1 ) . '%' : '';
	return '<span class="estrato-ticker-item"><strong>' . esc_html( $item['label'] ) . '</strong> '
		. esc_html( $item['value'] )
		. ( $pct ? ' <span class="' . esc_attr( $class ) . '">' . esc_html( $arrow . $pct ) . '</span>' : '' )
		. '</span>';
}

/**
 * @return string
 */
function estrato_ticker_render_bar() {
	$data  = estrato_ticker_fetch_data();
	$items = array( 'ibov', 'usdbrl', 'selic', 'ipca', 'btc' );
	$link  = get_page_by_path( 'cotacoes', OBJECT, 'page' );
	$url   = $link ? get_permalink( $link ) : home_url( '/cotacoes/' );

	$html = '<div class="estrato-ticker-br" role="region" aria-label="Cotações">';
	$html .= '<a class="estrato-ticker-br__inner" href="' . esc_url( $url ) . '">';
	foreach ( $items as $key ) {
		if ( ! empty( $data[ $key ] ) ) {
			$html .= estrato_ticker_format_item( $data[ $key ] ) . '<span class="estrato-ticker-sep">·</span>';
		}
	}
	$html .= '<span class="estrato-caption">atualizado ' . esc_html( $data['updated'] ?? '' ) . ' UTC</span>';
	$html .= '</a></div>';
	return $html;
}
add_shortcode( 'estrato_ticker_br', 'estrato_ticker_render_bar' );

/**
 * Substitui ticker PressGrid na home.
 */
function estrato_ticker_inject() {
	if ( is_admin() || is_feed() ) {
		return;
	}
	echo estrato_ticker_render_bar(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_body_open', 'estrato_ticker_inject', 3 );

/**
 * CSS ticker.
 */
function estrato_ticker_styles() {
	if ( is_admin() || is_feed() ) {
		return;
	}
	?>
	<style id="estrato-ticker-br-css">
	.estrato-ticker-br{background:#fff;border-bottom:1px solid var(--estrato-line,#e4e1da);font-family:var(--estrato-font-mono,monospace);font-size:12px}
	.estrato-ticker-br__inner{
		display:flex;flex-wrap:nowrap;gap:.5rem;align-items:center;overflow-x:auto;
		max-width:1200px;margin:0 auto;padding:.45rem 1rem;color:inherit;text-decoration:none;white-space:nowrap
	}
	.estrato-ticker-sep{opacity:.4}
	.estrato-ticker-item strong{letter-spacing:.04em;margin-right:.25rem}
	</style>
	<?php
}
add_action( 'wp_head', 'estrato_ticker_styles', 24 );
