<?php
/**
 * Schema & meta — Sprint 2 (NewsMediaOrganization, anti-dup, Person, OG).
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove JSON-LD e meta social duplicados do PressGrid (mantém Yoast).
 */
function estrato_seo_disable_pressgrid_schema() {
	remove_action( 'wp_head', 'pressgrid_schema_output' );
	remove_action( 'wp_head', 'pressgrid_social_meta' );
}
add_action( 'after_setup_theme', 'estrato_seo_disable_pressgrid_schema', 20 );

/**
 * Organization → NewsMediaOrganization (Metrópoles / ND Mais).
 *
 * @param array<string, mixed> $data
 * @return array<string, mixed>
 */
function estrato_seo_schema_organization( $data ) {
	if ( ! is_array( $data ) ) {
		return $data;
	}

	$data['@type'] = 'NewsMediaOrganization';
	$data['name']  = ! empty( $data['name'] ) ? $data['name'] : get_bloginfo( 'name' );

	$same_as = estrato_seo_get_same_as();
	if ( $same_as ) {
		$data['sameAs'] = $same_as;
	}

	$data['url'] = home_url( '/' );

	return $data;
}
add_filter( 'wpseo_schema_organization', 'estrato_seo_schema_organization' );

/**
 * Article → NewsArticle em posts (InfoMoney / Money Times).
 *
 * @param array<string, mixed> $data
 * @return array<string, mixed>
 */
function estrato_seo_schema_article( $data ) {
	if ( ! is_array( $data ) || ! is_singular( 'post' ) ) {
		return $data;
	}

	$data['@type'] = 'NewsArticle';

	return $data;
}
add_filter( 'wpseo_schema_article', 'estrato_seo_schema_article' );

/**
 * Person com jobTitle (Valor / InfoMoney).
 *
 * @param array<string, mixed> $data
 * @return array<string, mixed>
 */
function estrato_seo_schema_person( $data ) {
	if ( ! is_array( $data ) ) {
		return $data;
	}

	$author_id = get_post_field( 'post_author', get_queried_object_id() );
	if ( ! $author_id ) {
		return $data;
	}

	$job = get_user_meta( (int) $author_id, 'estrato_job_title', true );
	if ( ! $job ) {
		$job = get_user_meta( (int) $author_id, 'wpseo_job_title', true );
	}
	if ( $job ) {
		$data['jobTitle'] = sanitize_text_field( $job );
	}

	$same_as = get_user_meta( (int) $author_id, 'estrato_same_as', true );
	if ( is_string( $same_as ) && str_starts_with( $same_as, 'http' ) ) {
		$data['sameAs'] = array( esc_url_raw( $same_as ) );
	} elseif ( is_array( $same_as ) ) {
		$urls = array();
		foreach ( $same_as as $url ) {
			$url = trim( (string) $url );
			if ( str_starts_with( $url, 'http' ) ) {
				$urls[] = esc_url_raw( $url );
			}
		}
		if ( $urls ) {
			$data['sameAs'] = array_values( array_unique( $urls ) );
		}
	}

	$works = get_user_meta( (int) $author_id, 'estrato_works_for', true );
	if ( ! $works ) {
		$works = get_bloginfo( 'name' );
	}
	$data['worksFor'] = array(
		'@type' => 'NewsMediaOrganization',
		'name'  => $works,
		'url'   => home_url( '/' ),
	);

	return $data;
}
add_filter( 'wpseo_schema_person', 'estrato_seo_schema_person' );

/**
 * @return array<int, string>
 */
function estrato_seo_get_same_as() {
	$urls = array();

	$social = get_option( 'wpseo_social', array() );
	if ( ! is_array( $social ) ) {
		$social = array();
	}

	$map = array(
		'linkedin_url'  => 'linkedin_url',
		'instagram_url' => 'instagram_url',
		'twitter_site'  => 'twitter_site',
		'facebook_site' => 'facebook_site',
		'youtube_url'   => 'youtube_url',
	);

	foreach ( $map as $key => $field ) {
		if ( empty( $social[ $field ] ) ) {
			continue;
		}
		$url = trim( (string) $social[ $field ] );
		if ( str_starts_with( $url, 'http' ) ) {
			$urls[] = esc_url_raw( $url );
			continue;
		}
		if ( '@' === $url[0] && 'twitter_site' === $field ) {
			$urls[] = 'https://x.com/' . ltrim( $url, '@' );
		}
	}

	if ( ! empty( $social['other_social_urls'] ) && is_array( $social['other_social_urls'] ) ) {
		foreach ( $social['other_social_urls'] as $url ) {
			$url = trim( (string) $url );
			if ( str_starts_with( $url, 'http' ) ) {
				$urls[] = esc_url_raw( $url );
			}
		}
	}

	return array_values( array_unique( $urls ) );
}

/**
 * article:modified_time (ND Mais).
 */
function estrato_seo_article_modified_meta() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}

	$post = get_post();
	if ( ! $post ) {
		return;
	}

	$modified = get_post_modified_time( 'c', true, $post );
	if ( ! $modified ) {
		return;
	}

	printf(
		'<meta property="article:modified_time" content="%s" />' . "\n",
		esc_attr( $modified )
	);
}
add_action( 'wp_head', 'estrato_seo_article_modified_meta', 6 );

/**
 * Garante breadcrumbs visíveis no single (PressGrid já renderiza; fallback).
 */
function estrato_seo_ensure_breadcrumbs() {
	if ( ! is_singular( 'post' ) || ! function_exists( 'pressgrid_breadcrumbs' ) ) {
		return;
	}

	// PressGrid single.php já chama pressgrid_breadcrumbs().
}
add_action( 'wp', 'estrato_seo_ensure_breadcrumbs' );
