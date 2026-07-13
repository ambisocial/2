<?php
/**
 * Everest Forms
 *
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly



	/**
	 * Everest Form plugin uses form_id query string to display the form on the homepage
	 * since we are not using the_content in the TieLabs Page Builder the preview will not shown
	 */

if ( TIELABS_EVERESTFORMS_IS_ACTIVE ){

	add_filter( 'init', 'tie_everest_form_preview_url' );
	function tie_everest_form_preview_url(){

		if ( ! is_user_logged_in() || is_admin() ) {
			return;
		}

		if( isset( $_GET['form_id'] ) && isset( $_GET['evf_preview'] ) ){
			add_filter( 'TieLabs/has_builder', '__return_false' );
		}
	}
}
