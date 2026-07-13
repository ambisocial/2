<?php
/**
 * Home editorial v2 — template dedicado (evita wp_kses_post do PressGrid).
 *
 * @package EstratoPortalBootstrap
 */

get_header();

if ( function_exists( 'estrato_home_render_layout' ) ) {
	echo estrato_home_render_layout(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

get_footer();
