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
	// Fix pós auditoria visual 2026-07-13 (P3 contador "3" solto):
	// `pressgrid_reading_time()` em alguns builds retorna apenas o número (sem
	// "min de leitura"), o que a nossa byline exibia como um "3" órfão ao lado
	// da data. Sempre normalizar para "N min de leitura".
	$post_id = $post_id ? $post_id : get_the_ID();
	$mins    = 0;
	if ( function_exists( 'pressgrid_reading_time' ) ) {
		$raw = trim( (string) pressgrid_reading_time() );
		if ( preg_match( '/(\d+)/', $raw, $m ) ) {
			$mins = (int) $m[1];
		}
	}
	if ( $mins < 1 ) {
		$words = function_exists( 'estrato_content_post_word_count' )
			? estrato_content_post_word_count( $post_id )
			: str_word_count( wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ) );
		$mins  = max( 1, (int) ceil( $words / 200 ) );
	}
	return sprintf( '%d min de leitura', $mins );
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

	// Fix pós auditoria visual 2026-07-13:
	// - Byline duplicada: usar `display_name` cru + `estrato_job_title` uma vez.
	//   (Antes: filter global em `nav-visual.php` já concatenava, e o template
	//   somava de novo.)
	// - Persona placeholder marcada com badge para não passar como jornalista.
	$author_id   = (int) get_the_author_meta( 'ID' );
	$author_data = $author_id ? get_userdata( $author_id ) : null;
	$author_name = $author_data ? html_entity_decode( (string) $author_data->display_name, ENT_QUOTES, 'UTF-8' ) : get_the_author();
	$job         = $author_id ? (string) get_user_meta( $author_id, 'estrato_job_title', true ) : '';
	$job         = html_entity_decode( $job, ENT_QUOTES, 'UTF-8' );
	$is_persona  = $author_data && preg_match( '/-editoria$|-coluna$/', $author_data->user_login );
	$read        = estrato_single_reading_time( $post_id );
	$crumb       = function_exists( 'estrato_archive_render_breadcrumb' )
		? estrato_archive_render_breadcrumb( estrato_archive_breadcrumb_trail( $post_id ) )
		: '';
	ob_start();
	if ( $crumb ) {
		echo '<div class="estrato-single-breadcrumb-wrap">' . $crumb . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	// Fix pós auditoria visual 2026-07-13 (P1 featured hidden):
	// escondemos o header antigo do PressGrid; para não perder o featured
	// (que ficava dentro do wrapper `.pg-featured-image` acima), renderizamos
	// aqui, dentro do nosso header, com fetchpriority=high para o LCP.
	$thumb_html = '';
	if ( has_post_thumbnail( $post_id ) ) {
		$thumb_html = get_the_post_thumbnail(
			$post_id,
			'large',
			array(
				'class'         => 'estrato-single-featured-img',
				'fetchpriority' => 'high',
				'loading'       => 'eager',
			)
		);
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
		<?php if ( $thumb_html ) : ?>
			<figure class="estrato-single-featured"><?php echo $thumb_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></figure>
		<?php endif; ?>
		<div class="estrato-single-byline">
			<span class="estrato-single-author">
				Por <?php echo esc_html( $author_name ); ?><?php if ( $job ) : ?>, <?php echo esc_html( $job ); ?><?php endif; ?><?php if ( $is_persona ) : ?> <span class="estrato-persona-badge" title="Persona editorial coletiva do Estrato">Redação</span><?php endif; ?>
			</span>
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
 * Anterior / Próximo com títulos completos (evita truncamento e traduz os
 * rótulos EN do PressGrid). Substitui o `<nav class="navigation
 * post-navigation">` do tema, escondido via CSS na função de estilos.
 */
function estrato_single_render_prev_next() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}
	$prev = get_previous_post( true );
	$next = get_next_post( true );
	if ( ! $prev && ! $next ) {
		return;
	}
	echo '<nav class="estrato-single-prevnext" aria-label="Navegação de matérias">';
	if ( $prev ) {
		echo '<a class="estrato-single-prev" href="' . esc_url( get_permalink( $prev ) ) . '" rel="prev">'
			. '<span class="estrato-single-prevnext__label">← Anterior</span>'
			. '<span class="estrato-single-prevnext__title">' . esc_html( get_the_title( $prev ) ) . '</span>'
			. '</a>';
	}
	if ( $next ) {
		echo '<a class="estrato-single-next" href="' . esc_url( get_permalink( $next ) ) . '" rel="next">'
			. '<span class="estrato-single-prevnext__label">Próxima →</span>'
			. '<span class="estrato-single-prevnext__title">' . esc_html( get_the_title( $next ) ) . '</span>'
			. '</a>';
	}
	echo '</nav>';
}

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
	estrato_single_render_prev_next();
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
		. '.estrato-single-breadcrumb-wrap ol li:last-child{max-width:60ch;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:inline-block;vertical-align:bottom}'
		. '.estrato-single-header{max-width:680px;margin:0 auto var(--estrato-space-4);padding:0 1rem}'
		. '.estrato-single-dek{font-size:20px;line-height:1.45;color:var(--estrato-muted);margin:.75rem 0 1rem}'
		. '.estrato-single-byline{display:flex;flex-wrap:wrap;gap:.75rem;font-size:14px;color:var(--estrato-muted);align-items:center}'
		. '.estrato-single-byline br{display:none}'
		. '.estrato-persona-badge{display:inline-block;font-size:11px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;background:var(--estrato-line);color:var(--estrato-muted);padding:.1rem .45rem;border-radius:3px;margin-left:.35rem;vertical-align:baseline}'
		. '.estrato-updated-badge{font-size:12px;font-weight:600;letter-spacing:.02em;color:var(--estrato-cat-color,#5B3E96)}'
		. '.entry-content,.post-content{max-width:680px;margin:0 auto;font-size:18px;line-height:1.6}'
		. '.estrato-single-featured{margin:0 0 var(--estrato-space-4);max-width:680px}'
		. '.estrato-single-featured-img,.estrato-single-featured img{width:100%;height:auto;border-radius:4px;display:block}'
		. '.pg-featured-image,.pg-post-thumbnail,.pg-single-thumbnail{display:none!important}'
		. '.estrato-single-caption{display:block;margin-top:.35rem}'
		. '.estrato-single-related{max-width:680px;margin:var(--estrato-space-5) auto;padding:0 1rem}'
		. '.estrato-single-prevnext{max-width:680px;margin:var(--estrato-space-5) auto var(--estrato-space-4);padding:0 1rem;display:grid;gap:1rem;grid-template-columns:1fr}'
		. '@media(min-width:640px){.estrato-single-prevnext{grid-template-columns:1fr 1fr}}'
		. '.estrato-single-prev,.estrato-single-next{display:flex;flex-direction:column;gap:.25rem;padding:.85rem 1rem;border:1px solid var(--estrato-line);border-radius:6px;text-decoration:none;color:inherit;transition:border-color .15s}'
		. '.estrato-single-prev:hover,.estrato-single-next:hover{border-color:var(--estrato-cat-color,#5B3E96)}'
		. '.estrato-single-next{text-align:right}'
		. '.estrato-single-prevnext__label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--estrato-muted)}'
		. '.estrato-single-prevnext__title{font-size:14px;font-weight:600;line-height:1.35;color:var(--estrato-ink)}'
		. '.estrato-share-bar{position:fixed;left:max(1rem,calc(50% - 420px));top:40%;display:flex;flex-direction:column;gap:.5rem;font-size:12px;font-weight:600;z-index:50}'
		. '@media(max-width:1100px){.estrato-share-bar{position:sticky;bottom:0;left:0;flex-direction:row;justify-content:center;background:#fff;border-top:1px solid var(--estrato-line);padding:.5rem;z-index:100}}'
		// PressGrid back-to-top: escapa do overflow do .single-content e não sobe sobre o related grid.
		. '.pg-scroll-top,.pg-back-to-top,#back-to-top{z-index:60!important;bottom:calc(1rem + env(safe-area-inset-bottom,0px))!important;margin-bottom:calc(env(safe-area-inset-bottom,0px) + 3.5rem)!important}'
		// PressGrid navigation post-navigation com Previous/Next crus: fica em CSS oculto até termos a versão PT-BR abaixo.
		. '.navigation.post-navigation{display:none!important}';
	if ( function_exists( 'estrato_perf_style_add' ) ) {
		estrato_perf_style_add( 'estrato-single-css', $css, 'main' );
	} else {
		echo '<style id="estrato-single-css">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_head', 'estrato_single_styles', 20 );
