<?php
/**
 * Página de notícia — linha-fina, legenda, related e share (Sprint 4).
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @param int $post_id
 * @return string
 */
function estrato_single_dek( $post_id ) {
	$dek = get_post_meta( $post_id, '_estrato_dek', true );
	if ( $dek ) {
		return (string) $dek;
	}
	$excerpt = get_the_excerpt( $post_id );
	if ( $excerpt ) {
		return wp_strip_all_tags( $excerpt );
	}
	$content = get_post_field( 'post_content', $post_id );
	return wp_trim_words( wp_strip_all_tags( $content ), 28, '…' );
}

/**
 * @param int $post_id
 */
function estrato_single_ensure_dek_meta( $post_id ) {
	if ( get_post_meta( $post_id, '_estrato_dek', true ) ) {
		return;
	}
	$dek = estrato_single_dek( $post_id );
	if ( $dek ) {
		update_post_meta( $post_id, '_estrato_dek', $dek );
	}
}
add_action( 'save_post_post', 'estrato_single_ensure_dek_meta', 25 );

/**
 * @param int $post_id
 * @return array<int, WP_Post>
 */
function estrato_single_related_same_subcategory( $post_id, $limit = 3 ) {
	$cats = get_the_category( $post_id );
	if ( ! $cats ) {
		return array();
	}
	$deepest = $cats[0];
	foreach ( $cats as $cat ) {
		if ( $cat->parent > $deepest->parent ) {
			$deepest = $cat;
		}
	}
	return get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'post__not_in'   => array( $post_id ),
			'cat'            => (int) $deepest->term_id,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);
}

/**
 * Tempo de leitura (fallback quando PressGrid ausente).
 *
 * @param int $post_id
 * @return string
 */
function estrato_single_reading_time( $post_id = 0 ) {
	if ( function_exists( 'pressgrid_reading_time' ) ) {
		$read = pressgrid_reading_time();
		if ( $read ) {
			return $read;
		}
	}
	$post_id = $post_id ? $post_id : get_the_ID();
	$words   = function_exists( 'estrato_content_post_word_count' )
		? estrato_content_post_word_count( $post_id )
		: str_word_count( wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ) );
	$mins    = max( 1, (int) ceil( $words / 200 ) );
	return $mins . ' min de leitura';
}

/**
 * V5 (auditoria visual 2026-07-13) — Fallback do kicker quando o post está
 * apenas na editoria root (sem subcategoria) ou sem categorias válidas.
 *
 * @param string $kicker
 * @param int    $post_id
 * @return string
 */
function estrato_single_kicker_or_fallback( $kicker, $post_id ) {
	if ( $kicker ) {
		return $kicker;
	}
	$cats = get_the_category( $post_id );
	if ( is_array( $cats ) && ! empty( $cats ) ) {
		$root = $cats[0];
		while ( $root->parent ) {
			$parent = get_term( $root->parent, 'category' );
			if ( ! $parent || is_wp_error( $parent ) ) {
				break;
			}
			$root = $parent;
		}
		if ( ! empty( $root->name ) ) {
			$decoded = html_entity_decode( $root->name, ENT_QUOTES, 'UTF-8' );
			return function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $decoded, 'UTF-8' ) : strtoupper( $decoded );
		}
	}
	$site = get_bloginfo( 'name' );
	if ( $site ) {
		return function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $site, 'UTF-8' ) : strtoupper( $site );
	}
	return 'EDITORIAL';
}

/**
 * Cabeçalho editorial do single (via the_content).
 *
 * V5: substituído `static $done` por meta transiente por post_id, para
 * evitar cenário em que outra chamada de the_content (ex.: preview, excerpt)
 * consumia a única execução e o template principal ficava sem header.
 */
function estrato_single_render_header_fallback( $content ) {
	if ( ! is_singular( 'post' ) || is_admin() || is_feed() ) {
		return $content;
	}
	if ( ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$post_id = get_the_ID();
	if ( ! $post_id ) {
		return $content;
	}
	static $rendered = array();
	if ( isset( $rendered[ $post_id ] ) ) {
		return $content;
	}
	$rendered[ $post_id ] = true;
	$slug    = estrato_ds_post_editoria_slug( $post_id );
	$color   = estrato_ds_editoria_color( $slug );
	$kicker  = estrato_home_post_kicker( get_post( $post_id ) );
	$kicker  = estrato_single_kicker_or_fallback( $kicker, $post_id );
	$dek     = estrato_single_dek( $post_id );
	$author  = get_the_author();
	$job     = get_the_author_meta( 'estrato_job_title' );
	$read    = estrato_single_reading_time( $post_id );
	$crumb   = function_exists( 'estrato_archive_render_breadcrumb' )
		? estrato_archive_render_breadcrumb( estrato_archive_breadcrumb_trail( $post_id ) )
		: '';
	ob_start();
	if ( $crumb ) {
		echo '<div class="estrato-single-breadcrumb-wrap">' . $crumb . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	?>
	<div class="estrato-single-header" style="--estrato-cat-color:<?php echo esc_attr( $color ); ?>">
		<?php if ( $kicker ) : ?>
			<p class="estrato-kicker"><?php echo esc_html( $kicker ); ?></p>
		<?php endif; ?>
		<h1 class="estrato-display estrato-h1 entry-title"><?php the_title(); ?></h1>
		<?php if ( $dek ) : ?>
			<p class="estrato-single-dek"><?php echo esc_html( $dek ); ?></p>
		<?php endif; ?>
		<div class="estrato-single-byline">
			<span class="estrato-single-author">Por <?php echo esc_html( $job ? $author . ', ' . $job : $author ); ?></span>
			<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'd/m/Y H:i' ) ); ?></time>
			<span class="estrato-single-read"><?php echo esc_html( $read ); ?></span>
			<?php
			$post = get_post( $post_id );
			if ( $post && get_post_time( 'U', true, $post ) < get_post_modified_time( 'U', true, $post ) ) :
				?>
				<span class="estrato-updated-badge">ATUALIZADO às <?php echo esc_html( get_the_modified_time( 'H:i' ) ); ?></span>
			<?php endif; ?>
		</div>
	</div>
	<?php
	$header = ob_get_clean();
	return $header . $content;
}
add_filter( 'the_content', 'estrato_single_render_header_fallback', 3 );

/**
 * Legenda após primeira imagem do conteúdo.
 *
 * @param string $content
 * @return string
 */
function estrato_single_thumbnail_caption_filter( $content ) {
	if ( ! is_singular( 'post' ) || is_admin() || is_feed() || ! has_post_thumbnail() ) {
		return $content;
	}
	$caption = wp_get_attachment_caption( get_post_thumbnail_id() );
	$credit  = get_post_meta( get_the_ID(), '_estrato_image_credit', true );
	if ( ! $caption && ! $credit ) {
		$caption = 'Ilustração da matéria';
	}
	$fig = '<figcaption class="estrato-single-caption estrato-caption">';
	if ( $caption ) {
		$fig .= esc_html( $caption );
	}
	if ( $credit ) {
		$fig .= ' <span class="estrato-single-credit">(' . esc_html( $credit ) . ')</span>';
	}
	$fig .= '</figcaption>';
	return preg_replace( '/(<img[^>]+>)/i', '$1' . $fig, $content, 1 );
}
add_filter( 'the_content', 'estrato_single_thumbnail_caption_filter', 12 );

/**
 * Related da mesma subcategoria.
 */
function estrato_single_render_related() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}
	$related = estrato_single_related_same_subcategory( get_the_ID(), 3 );
	if ( ! $related ) {
		return;
	}
	echo '<aside class="estrato-single-related" aria-label="Leia também"><h2>Leia também</h2><ul>';
	foreach ( $related as $post ) {
		echo '<li><a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a></li>';
	}
	echo '</ul></aside>';
}

/**
 * Botões de compartilhar.
 */
function estrato_single_share_bar() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}
	$url   = rawurlencode( get_permalink() );
	$title = rawurlencode( get_the_title() );
	?>
	<div class="estrato-share-bar" aria-label="Compartilhar">
		<a href="https://wa.me/?text=<?php echo esc_attr( $title . '%20' . $url ); ?>" rel="noopener" target="_blank">WhatsApp</a>
		<a href="https://twitter.com/intent/tweet?url=<?php echo esc_attr( $url ); ?>&text=<?php echo esc_attr( $title ); ?>" rel="noopener" target="_blank">X</a>
		<a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo esc_attr( $url ); ?>" rel="noopener" target="_blank">LinkedIn</a>
	</div>
	<?php
}

/**
 * Fallback footer do single (related + share).
 */
function estrato_single_footer_fallback( $content ) {
	if ( ! is_singular( 'post' ) || is_admin() || is_feed() ) {
		return $content;
	}
	static $done = false;
	if ( $done ) {
		return $content;
	}
	$done = true;
	ob_start();
	estrato_single_share_bar();
	estrato_single_render_related();
	return $content . ob_get_clean();
}
add_filter( 'the_content', 'estrato_single_footer_fallback', 99 );

/**
 * CSS single.
 */
function estrato_single_styles() {
	if ( ! is_singular( 'post' ) || is_admin() || is_feed() ) {
		return;
	}
	$css = '.estrato-single-breadcrumb-wrap{max-width:680px;margin:0 auto;padding:0 1rem}'
		. '.estrato-single-header{max-width:680px;margin:0 auto var(--estrato-space-4);padding:0 1rem}'
		. '.estrato-single-dek{font-size:20px;line-height:1.45;color:var(--estrato-muted);margin:.75rem 0 1rem}'
		. '.estrato-single-byline{display:flex;flex-wrap:wrap;gap:.75rem;font-size:14px;color:var(--estrato-muted)}'
		. '.entry-content,.post-content{max-width:680px;margin:0 auto;font-size:18px;line-height:1.6}'
		. '.estrato-single-caption{display:block;margin-top:.35rem}'
		. '.estrato-single-related{max-width:680px;margin:var(--estrato-space-5) auto;padding:0 1rem}'
		. '.estrato-share-bar{position:fixed;left:max(1rem,calc(50% - 420px));top:40%;display:flex;flex-direction:column;gap:.5rem;font-size:12px;font-weight:600}'
		. '@media(max-width:1100px){.estrato-share-bar{position:sticky;bottom:0;left:0;flex-direction:row;justify-content:center;background:#fff;border-top:1px solid var(--estrato-line);padding:.5rem;z-index:100}}';
	if ( function_exists( 'estrato_perf_style_add' ) ) {
		estrato_perf_style_add( 'estrato-single-css', $css, 'main' );
	} else {
		echo '<style id="estrato-single-css">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_head', 'estrato_single_styles', 20 );
