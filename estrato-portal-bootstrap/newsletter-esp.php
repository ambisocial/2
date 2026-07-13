<?php
/**
 * Newsletter ESP — Brevo / Mailchimp (P1 auditoria).
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ESTRATO_NEWSLETTER_PROVIDER_OPTION', 'estrato_newsletter_provider' );
define( 'ESTRATO_NEWSLETTER_API_KEY_OPTION', 'estrato_newsletter_api_key' );
define( 'ESTRATO_NEWSLETTER_LIST_ID_OPTION', 'estrato_newsletter_list_id' );

/**
 * @return string brevo|mailchimp|none
 */
function estrato_newsletter_esp_provider() {
	$from_env = getenv( 'ESTRATO_NEWSLETTER_PROVIDER' );
	if ( $from_env && in_array( $from_env, array( 'brevo', 'mailchimp' ), true ) ) {
		return $from_env;
	}
	$opt = get_option( ESTRATO_NEWSLETTER_PROVIDER_OPTION, 'none' );
	return in_array( $opt, array( 'brevo', 'mailchimp' ), true ) ? $opt : 'none';
}

/**
 * @return string
 */
function estrato_newsletter_esp_api_key() {
	$key = getenv( 'ESTRATO_NEWSLETTER_API_KEY' );
	if ( $key ) {
		return (string) $key;
	}
	return (string) get_option( ESTRATO_NEWSLETTER_API_KEY_OPTION, '' );
}

/**
 * @return string
 */
function estrato_newsletter_esp_list_id() {
	$id = getenv( 'ESTRATO_NEWSLETTER_LIST_ID' );
	if ( $id ) {
		return (string) $id;
	}
	return (string) get_option( ESTRATO_NEWSLETTER_LIST_ID_OPTION, '' );
}

/**
 * @param string $email
 * @return bool|WP_Error
 */
function estrato_newsletter_esp_subscribe( $email ) {
	$email = sanitize_email( $email );
	if ( ! is_email( $email ) ) {
		return new WP_Error( 'invalid_email', 'E-mail inválido' );
	}

	$provider = estrato_newsletter_esp_provider();
	$api_key  = estrato_newsletter_esp_api_key();
	$list_id  = estrato_newsletter_esp_list_id();

	if ( 'none' === $provider || '' === $api_key || '' === $list_id ) {
		return true;
	}

	if ( 'brevo' === $provider ) {
		return estrato_newsletter_esp_brevo_subscribe( $email, $api_key, $list_id );
	}

	return estrato_newsletter_esp_mailchimp_subscribe( $email, $api_key, $list_id );
}

/**
 * @param string $email
 * @param string $api_key
 * @param string $list_id
 * @return bool|WP_Error
 */
function estrato_newsletter_esp_brevo_subscribe( $email, $api_key, $list_id ) {
	$list_id = (int) $list_id;
	$body    = array(
		'email'            => $email,
		'listIds'          => array( $list_id ),
		'updateEnabled'    => true,
		'emailBlacklisted' => false,
	);

	$response = wp_remote_post(
		'https://api.brevo.com/v3/contacts',
		array(
			'timeout' => 15,
			'headers' => array(
				'api-key'      => $api_key,
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
			),
			'body'    => wp_json_encode( $body ),
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	if ( in_array( $code, array( 200, 201, 204 ), true ) ) {
		return true;
	}

	$payload = json_decode( wp_remote_retrieve_body( $response ), true );
	$msg     = is_array( $payload ) && ! empty( $payload['message'] ) ? $payload['message'] : "HTTP $code";
	if ( 400 === $code && false !== stripos( $msg, 'already' ) ) {
		return true;
	}

	return new WP_Error( 'brevo_error', $msg );
}

/**
 * @param string $email
 * @param string $api_key  formato us1-xxx
 * @param string $list_id
 * @return bool|WP_Error
 */
function estrato_newsletter_esp_mailchimp_subscribe( $email, $api_key, $list_id ) {
	$dc = 'us1';
	if ( false !== strpos( $api_key, '-' ) ) {
		$parts = explode( '-', $api_key, 2 );
		if ( ! empty( $parts[1] ) ) {
			$dc = $parts[1];
		}
	}

	$hash = md5( strtolower( $email ) );
	$url  = sprintf(
		'https://%s.api.mailchimp.com/3.0/lists/%s/members/%s',
		rawurlencode( $dc ),
		rawurlencode( $list_id ),
		$hash
	);

	$body = array(
		'email_address' => $email,
		'status'        => 'subscribed',
		'merge_fields'  => array(
			'FNAME' => '',
			'LNAME' => '',
		),
	);

	$response = wp_remote_request(
		$url,
		array(
			'method'  => 'PUT',
			'timeout' => 15,
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( 'user:' . $api_key ),
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $body ),
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	if ( in_array( $code, array( 200, 201 ), true ) ) {
		return true;
	}

	$payload = json_decode( wp_remote_retrieve_body( $response ), true );
	$msg     = is_array( $payload ) && ! empty( $payload['detail'] ) ? $payload['detail'] : "HTTP $code";
	if ( 400 === $code && false !== stripos( $msg, 'Member Exists' ) ) {
		return true;
	}

	return new WP_Error( 'mailchimp_error', $msg );
}
