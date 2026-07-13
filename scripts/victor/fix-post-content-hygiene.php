<?php
/**
 * Higiene de conteúdo em posts publicados:
 *
 *  1) Remove boilerplate "The post ... appeared first on ..." embutido dentro
 *     do post_content (originado do RSS do "Seu Dinheiro" e afins).
 *  2) Reconstrói títulos + deks que foram truncados em 70/148 chars pelo
 *     pipeline externo, buscando `<title>` e `og:description` da URL original
 *     armazenada em `_estrato_source_url` / `_estrato_rss_source_url`.
 *
 * Uso:
 *   sudo -u www-data wp --path=<web_root> eval-file <this> --url=<site>
 *   ESTRATO_HYGIENE_DRY_RUN=1 sudo -u www-data wp ... eval-file <this>
 *
 * Idempotente: skip se `_estrato_content_hygiene_v1_at` já existe.
 */

$dry_run = ( ! empty( getenv( 'ESTRATO_HYGIENE_DRY_RUN' ) ) && '0' !== getenv( 'ESTRATO_HYGIENE_DRY_RUN' ) );

/**
 * @param int $post_id
 * @return string
 */
function estrato_hygiene_source_url( $post_id ) {
	$src = (string) get_post_meta( $post_id, '_estrato_source_url', true );
	if ( '' === $src ) {
		$src = (string) get_post_meta( $post_id, '_estrato_rss_source_url', true );
	}
	return $src;
}

/**
 * Remove padrões WordPress-syndication conhecidos do corpo.
 *
 * @param string $html
 * @return string
 */
function estrato_hygiene_strip_boilerplate( $html ) {
	$patterns = array(
		// "The post <título> appeared first on <site>."
		'/<[^>]*>\s*The\s+post\s+.*?appeared\s+first\s+on\s+[^<]{1,120}\.\s*<\/[^>]+>/isu',
		'/The\s+post\s+.*?appeared\s+first\s+on\s+[^\.<]{1,120}\.\s*/isu',
		// "Continue lendo em <site>" / "Leia mais em <site>"
		'/(?:Continue\s+lendo|Leia\s+mais)\s+em\s+<a[^>]*>[^<]+<\/a>\.?/isu',
		// Trailing "Fonte: <a>...</a>" quando duplicado no fim do primeiro parágrafo
		'/Fonte:\s*<a[^>]*>[^<]{1,80}<\/a>(?=\s*<\/(?:strong|em|p)>)/isu',
	);
	foreach ( $patterns as $rx ) {
		$html = preg_replace( $rx, '', $html );
	}
	$html = preg_replace( '/(<p>[^<]*)<strong>\s*<\/strong>/isu', '$1', $html );
	$html = preg_replace( '/<p>\s*<\/p>/isu', '', $html );
	$html = preg_replace( '/[\p{Zs}\s]+/u', ' ', $html );
	return trim( $html );
}

/**
 * Extrai título + descrição da URL original.
 *
 * @param string $url
 * @return array{title:string,description:string}
 */
function estrato_hygiene_fetch_original_meta( $url ) {
	$out = array(
		'title'       => '',
		'description' => '',
	);
	if ( '' === $url ) {
		return $out;
	}
	$res = wp_remote_get(
		$url,
		array(
			'timeout'     => 15,
			'redirection' => 3,
			'user-agent'  => 'EstratoBackfill/1.0 (+https://estrato.cc)',
			'headers'     => array(
				'Accept-Language' => 'pt-BR,pt;q=0.9,en;q=0.5',
			),
		)
	);
	if ( is_wp_error( $res ) ) {
		return $out;
	}
	$body = (string) wp_remote_retrieve_body( $res );
	if ( '' === $body ) {
		return $out;
	}
	if ( preg_match( '/<meta[^>]+property=["\']og:title["\'][^>]*content=["\']([^"\']+)["\']/iu', $body, $m ) ) {
		$out['title'] = html_entity_decode( trim( $m[1] ), ENT_QUOTES, 'UTF-8' );
	} elseif ( preg_match( '/<title[^>]*>([^<]+)<\/title>/iu', $body, $m ) ) {
		$out['title'] = html_entity_decode( trim( $m[1] ), ENT_QUOTES, 'UTF-8' );
	}
	if ( preg_match( '/<meta[^>]+property=["\']og:description["\'][^>]*content=["\']([^"\']+)["\']/iu', $body, $m ) ) {
		$out['description'] = html_entity_decode( trim( $m[1] ), ENT_QUOTES, 'UTF-8' );
	} elseif ( preg_match( '/<meta[^>]+name=["\']description["\'][^>]*content=["\']([^"\']+)["\']/iu', $body, $m ) ) {
		$out['description'] = html_entity_decode( trim( $m[1] ), ENT_QUOTES, 'UTF-8' );
	}
	// Site names comuns anexados ao <title>
	$out['title'] = preg_replace( '/\s+[\|\-–—]\s+(?:Seu\s+Dinheiro|InfoMoney|Money\s+Times|G1|Exame|Folha\s+de\s+S\.?Paulo|Olhar\s+Digital|Agência\s+Brasil).*$/iu', '', $out['title'] );
	return $out;
}

$q = new WP_Query(
	array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	)
);

echo 'Portal: ' . home_url() . "\n";
echo 'Modo:   ' . ( $dry_run ? 'DRY-RUN' : 'APPLY' ) . "\n";
echo 'Total publish: ' . count( $q->posts ) . "\n---\n";

$stripped_body   = 0;
$backfilled_ttl  = 0;
$backfilled_dek  = 0;
$skipped         = 0;
$errors          = 0;

foreach ( $q->posts as $post_id ) {
	if ( get_post_meta( $post_id, '_estrato_content_hygiene_v1_at', true ) ) {
		++$skipped;
		continue;
	}
	$post = get_post( $post_id );
	if ( ! $post ) {
		++$errors;
		continue;
	}
	$did_change = false;
	$changes    = array();

	$body    = (string) $post->post_content;
	$new_body = estrato_hygiene_strip_boilerplate( $body );
	if ( $new_body !== $body && '' !== $new_body ) {
		$did_change = true;
		$changes[]  = 'body';
	}

	$title       = (string) $post->post_title;
	$excerpt     = (string) $post->post_excerpt;
	$dek         = (string) get_post_meta( $post_id, '_estrato_dek', true );
	$title_needs = ( mb_strlen( $title ) === 70 || ( mb_strlen( $title ) === 148 ) );
	$dek_needs   = ( mb_strlen( $dek ) === 148 || mb_strlen( $excerpt ) === 148 );

	$new_title = $title;
	$new_dek   = $dek;
	$new_excerpt = $excerpt;

	if ( $title_needs || $dek_needs ) {
		$src = estrato_hygiene_source_url( $post_id );
		if ( $src ) {
			$meta = estrato_hygiene_fetch_original_meta( $src );
			if ( $title_needs && '' !== $meta['title'] && mb_strlen( $meta['title'] ) > mb_strlen( $title ) ) {
				$new_title  = $meta['title'];
				$did_change = true;
				$changes[]  = 'title';
			}
			if ( $dek_needs && '' !== $meta['description'] ) {
				if ( mb_strlen( $meta['description'] ) > mb_strlen( $dek ) ) {
					$new_dek    = $meta['description'];
					$did_change = true;
					$changes[]  = 'dek';
				}
				if ( mb_strlen( $meta['description'] ) > mb_strlen( $excerpt ) ) {
					$new_excerpt = $meta['description'];
					$did_change  = true;
				}
			}
		}
	}

	if ( ! $did_change ) {
		++$skipped;
		continue;
	}

	echo sprintf( "ID=%d changes=%s src=%s\n", $post_id, implode( ',', $changes ), estrato_hygiene_source_url( $post_id ) );

	if ( ! $dry_run ) {
		$upd = array( 'ID' => $post_id );
		if ( in_array( 'body', $changes, true ) ) {
			$upd['post_content'] = $new_body;
			++$stripped_body;
		}
		if ( in_array( 'title', $changes, true ) ) {
			$upd['post_title'] = $new_title;
			$upd['post_name']  = sanitize_title( $new_title );
			++$backfilled_ttl;
		}
		if ( $new_excerpt !== $excerpt ) {
			$upd['post_excerpt'] = wp_strip_all_tags( $new_excerpt );
		}
		$res = wp_update_post( $upd, true );
		if ( is_wp_error( $res ) ) {
			++$errors;
			continue;
		}
		if ( in_array( 'dek', $changes, true ) ) {
			update_post_meta( $post_id, '_estrato_dek', wp_strip_all_tags( $new_dek ) );
			++$backfilled_dek;
		}
		update_post_meta( $post_id, '_estrato_content_hygiene_v1_at', current_time( 'mysql', true ) );
	}
}

echo "---\n";
echo "stripped_body: $stripped_body\n";
echo "backfilled_title: $backfilled_ttl\n";
echo "backfilled_dek: $backfilled_dek\n";
echo "skipped: $skipped\n";
echo "errors: $errors\n";
