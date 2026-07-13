<?php
/**
 * V7 fase 2 (auditoria visual 2026-07-13) — Consolidador de CSS inline.
 *
 * Agrega os múltiplos `<style>` que módulos individuais imprimem no
 * `wp_head` em apenas dois blocos:
 *   - critical (prio 3): fontes, tokens do design-system e CSS crítico de
 *     home/single (bloqueiam LCP).
 *   - main    (prio 25): navegação, ticker, cards, arquivos, footer.
 *
 * Módulos migrados chamam `estrato_perf_style_add()` em vez de `echo '<style>'`.
 * Blocos coletados são deduplicados por `$id` (última chamada vence, para
 * permitir overrides).
 *
 * Fora do runtime WP (ex.: CLI puro), a função de emissão é no-op.
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return array<string, array<string, string>>
 */
function &estrato_perf_style_registry() {
	static $registry = null;
	if ( null === $registry ) {
		$registry = array(
			'critical' => array(),
			'main'     => array(),
		);
	}
	return $registry;
}

/**
 * Adiciona um bloco CSS a ser emitido em wp_head consolidado.
 *
 * @param string $id     Identificador (usado no atributo `id` do <style>).
 * @param string $css    Conteúdo CSS já pronto (sem <style>).
 * @param string $bucket 'critical' | 'main'.
 * @return void
 */
function estrato_perf_style_add( $id, $css, $bucket = 'main' ) {
	$id = sanitize_html_class( (string) $id );
	if ( '' === $id ) {
		return;
	}
	$css = trim( (string) $css );
	if ( '' === $css ) {
		return;
	}
	if ( ! in_array( $bucket, array( 'critical', 'main' ), true ) ) {
		$bucket = 'main';
	}
	$registry = &estrato_perf_style_registry();
	$registry[ $bucket ][ $id ] = $css;
}

/**
 * Emite os blocos consolidados no wp_head.
 *
 * @param string $bucket
 * @return void
 */
function estrato_perf_style_flush( $bucket ) {
	$registry = &estrato_perf_style_registry();
	if ( empty( $registry[ $bucket ] ) ) {
		return;
	}
	$id = 'estrato-consolidated-' . $bucket;
	echo "<style id=\"" . esc_attr( $id ) . "\">\n";
	foreach ( $registry[ $bucket ] as $handle => $css ) {
		echo "/* @" . esc_html( $handle ) . " */\n";
		echo $css . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	echo "</style>\n";
	unset( $registry[ $bucket ] );
	$registry[ $bucket ] = array();
}

/**
 * @return void
 */
function estrato_perf_style_flush_critical() {
	estrato_perf_style_flush( 'critical' );
}
add_action( 'wp_head', 'estrato_perf_style_flush_critical', 3 );

/**
 * @return void
 */
function estrato_perf_style_flush_main() {
	estrato_perf_style_flush( 'main' );
}
add_action( 'wp_head', 'estrato_perf_style_flush_main', 25 );
