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
 * Dados placeholder quando o cache ainda não foi aquecido.
 *
 * @return array<string, mixed>
 */
function estrato_ticker_placeholder_data() {
	return array(
		'ibov'    => array( 'label' => 'IBOVESPA', 'value' => '—', 'change' => 0 ),
		'usdbrl'  => array( 'label' => 'USD/BRL', 'value' => '—', 'change' => 0 ),
		'selic'   => array( 'label' => 'SELIC', 'value' => '—', 'change' => 0 ),
		'ipca'    => array( 'label' => 'IPCA 12m', 'value' => '—', 'change' => 0 ),
		'btc'     => array( 'label' => 'BITCOIN', 'value' => '—', 'change' => 0 ),
		'updated' => gmdate( 'H:i' ),
	);
}

/**
 * Lê cache sem chamadas HTTP (não bloqueia TTFB/LCP).
 *
 * @return array<string, mixed>
 */
function estrato_ticker_get_cached() {
	$cached = get_transient( 'estrato_ticker_br_v3' );
	if ( false !== $cached && is_array( $cached ) ) {
		return $cached;
	}
	// Migração suave do cache antigo (pode ter IBOV/USD vazios).
	$legacy = get_transient( 'estrato_ticker_br_v2' );
	if ( false !== $legacy && is_array( $legacy ) ) {
		return $legacy;
	}
	return estrato_ticker_placeholder_data();
}

/**
 * @param mixed $response
 * @return array<string, mixed>|null
 */
function estrato_ticker_json_body( $response ) {
	if ( is_wp_error( $response ) ) {
		return null;
	}
	$code = (int) wp_remote_retrieve_response_code( $response );
	if ( $code < 200 || $code >= 300 ) {
		return null;
	}
	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	return is_array( $body ) ? $body : null;
}

/**
 * @param array{label:string,value:string,change:float} $item
 * @return bool
 */
function estrato_ticker_item_is_filled( $item ) {
	if ( ! is_array( $item ) || empty( $item['value'] ) ) {
		return false;
	}
	return '—' !== (string) $item['value'];
}

/**
 * Atualiza cache via APIs externas (cron / CLI apenas).
 * Fallbacks: Yahoo (IBOV), Frankfurter/BCB (USD), CoinGecko (BTC).
 *
 * @return array<string, mixed>
 */
function estrato_ticker_refresh_cache() {
	$prev = estrato_ticker_get_cached();
	$data = estrato_ticker_placeholder_data();

	// IBOVESPA — Yahoo Finance chart (brapi exige token).
	$ibov = wp_remote_get(
		'https://query1.finance.yahoo.com/v8/finance/chart/%5EBVSP?interval=1d&range=5d',
		array(
			'timeout' => 10,
			'headers' => array( 'User-Agent' => 'EstratoTicker/1.0' ),
		)
	);
	$body = estrato_ticker_json_body( $ibov );
	if ( $body && ! empty( $body['chart']['result'][0]['meta']['regularMarketPrice'] ) ) {
		$meta         = $body['chart']['result'][0]['meta'];
		$price        = (float) $meta['regularMarketPrice'];
		$prev_close   = isset( $meta['chartPreviousClose'] ) ? (float) $meta['chartPreviousClose'] : 0.0;
		$change       = ( $prev_close > 0 ) ? ( ( $price - $prev_close ) / $prev_close ) * 100 : 0.0;
		$data['ibov'] = array(
			'label'  => 'IBOVESPA',
			'value'  => number_format_i18n( $price, 0 ),
			'change' => $change,
		);
	}

	// USD/BRL — Frankfurter (AwesomeAPI em 429).
	$fx = wp_remote_get( 'https://api.frankfurter.app/latest?from=USD&to=BRL', array( 'timeout' => 8 ) );
	$body = estrato_ticker_json_body( $fx );
	if ( $body && ! empty( $body['rates']['BRL'] ) ) {
		$data['usdbrl'] = array(
			'label'  => 'USD/BRL',
			'value'  => number_format_i18n( (float) $body['rates']['BRL'], 4 ),
			'change' => 0,
		);
	} else {
		// Fallback BCB PTAX (série 1).
		$ptax = wp_remote_get( 'https://api.bcb.gov.br/dados/serie/bcdata.sgs.1/dados/ultimos/1?formato=json', array( 'timeout' => 8 ) );
		$body = estrato_ticker_json_body( $ptax );
		if ( $body && ! empty( $body[0]['valor'] ) ) {
			$data['usdbrl'] = array(
				'label'  => 'USD/BRL',
				'value'  => number_format_i18n( (float) $body[0]['valor'], 4 ),
				'change' => 0,
			);
		}
	}

	$selic = wp_remote_get( 'https://api.bcb.gov.br/dados/serie/bcdata.sgs.432/dados/ultimos/1?formato=json', array( 'timeout' => 8 ) );
	$body  = estrato_ticker_json_body( $selic );
	if ( $body && ! empty( $body[0]['valor'] ) ) {
		$data['selic'] = array(
			'label'  => 'SELIC',
			'value'  => number_format_i18n( (float) $body[0]['valor'], 2 ) . '%',
			'change' => 0,
		);
	}

	$ipca = wp_remote_get( 'https://api.bcb.gov.br/dados/serie/bcdata.sgs.13522/dados/ultimos/1?formato=json', array( 'timeout' => 8 ) );
	$body = estrato_ticker_json_body( $ipca );
	if ( $body && ! empty( $body[0]['valor'] ) ) {
		$data['ipca'] = array(
			'label'  => 'IPCA 12m',
			'value'  => number_format_i18n( (float) $body[0]['valor'], 1 ) . '%',
			'change' => 0,
		);
	}

	// BITCOIN — CoinGecko (AwesomeAPI em 429).
	$btc = wp_remote_get(
		'https://api.coingecko.com/api/v3/simple/price?ids=bitcoin&vs_currencies=usd&include_24hr_change=true',
		array( 'timeout' => 10 )
	);
	$body = estrato_ticker_json_body( $btc );
	if ( $body && ! empty( $body['bitcoin']['usd'] ) ) {
		$data['btc'] = array(
			'label'  => 'BITCOIN',
			'value'  => 'US$ ' . number_format_i18n( (float) $body['bitcoin']['usd'], 0 ),
			'change' => (float) ( $body['bitcoin']['usd_24h_change'] ?? 0 ),
		);
	}

	// Não regredir para "—" se a API falhou nesta rodada.
	foreach ( array( 'ibov', 'usdbrl', 'selic', 'ipca', 'btc' ) as $key ) {
		if ( ! estrato_ticker_item_is_filled( $data[ $key ] ) && ! empty( $prev[ $key ] ) && estrato_ticker_item_is_filled( $prev[ $key ] ) ) {
			$data[ $key ] = $prev[ $key ];
		}
	}

	$data['updated'] = gmdate( 'H:i' );
	set_transient( 'estrato_ticker_br_v3', $data, 15 * MINUTE_IN_SECONDS );
	delete_transient( 'estrato_ticker_br_v2' );
	return $data;
}

/**
 * @deprecated Use estrato_ticker_get_cached() no front.
 * @return array<string, mixed>
 */
function estrato_ticker_fetch_data() {
	return estrato_ticker_get_cached();
}

/**
 * Cron a cada 15 minutos.
 *
 * @param array<string, mixed> $schedules
 * @return array<string, mixed>
 */
function estrato_ticker_cron_schedules( $schedules ) {
	if ( ! isset( $schedules['estrato_fifteen_minutes'] ) ) {
		$schedules['estrato_fifteen_minutes'] = array(
			'interval' => 15 * MINUTE_IN_SECONDS,
			'display'  => 'A cada 15 minutos (Estrato ticker)',
		);
	}
	return $schedules;
}
add_filter( 'cron_schedules', 'estrato_ticker_cron_schedules' );

/**
 * Agenda cron do ticker.
 */
function estrato_ticker_schedule_cron() {
	if ( wp_next_scheduled( 'estrato_ticker_refresh_event' ) ) {
		return;
	}
	wp_schedule_event( time(), 'estrato_fifteen_minutes', 'estrato_ticker_refresh_event' );
}
add_action( 'init', 'estrato_ticker_schedule_cron' );

/**
 * Handler do cron.
 */
function estrato_ticker_cron_handler() {
	estrato_ticker_refresh_cache();
}
add_action( 'estrato_ticker_refresh_event', 'estrato_ticker_cron_handler' );

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
	$data  = estrato_ticker_get_cached();
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
 * CSS ticker BR + contraste do forex PressGrid.
 */
function estrato_ticker_styles() {
	if ( is_admin() || is_feed() ) {
		return;
	}
	$css = '.estrato-ticker-br{background:#fff;border-bottom:1px solid var(--estrato-line,#e4e1da);font-family:var(--estrato-font-mono,monospace);font-size:12px;color:#1e1e1e}'
		. '.estrato-ticker-br__inner{display:flex;flex-wrap:nowrap;gap:.5rem;align-items:center;overflow-x:auto;max-width:1200px;margin:0 auto;padding:.45rem 1rem;color:#1e1e1e;text-decoration:none;white-space:nowrap}'
		. '.estrato-ticker-sep{opacity:.4;color:#1e1e1e}'
		. '.estrato-ticker-item{color:#1e1e1e}'
		. '.estrato-ticker-item strong{letter-spacing:.04em;margin-right:.25rem;color:#111}'
		/* Forex PressGrid: tema usa texto claro — força contraste no fundo bege. */
		. '.pg-forex-bar{background:#fff1e5!important;border-top:1px solid #e8d5c4!important;border-bottom:1px solid #e8d5c4!important}'
		. '.pg-forex-bar,.pg-forex-ticker,.pg-forex-item,.pg-forex-pair,.pg-forex-rate,.pg-forex-ticker-wrap{color:#1e1e1e!important}'
		. '.pg-forex-pair{font-weight:700!important;color:#111!important}'
		. '.pg-forex-rate{font-weight:600!important;color:#1e1e1e!important}'
		. '.pg-forex-label,.pg-forex-label *,.pg-forex-base{background:#C4170C!important;color:#fff!important}'
		. '.pg-forex-item.pg-forex-source a{color:#0b6e4f!important;text-decoration:underline}';
	if ( function_exists( 'estrato_perf_style_add' ) ) {
		estrato_perf_style_add( 'estrato-ticker-br-css', $css, 'main' );
	} else {
		echo '<style id="estrato-ticker-br-css">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_head', 'estrato_ticker_styles', 20 );
