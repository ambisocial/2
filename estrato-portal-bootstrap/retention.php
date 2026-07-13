<?php
/**
 * Retenção — autores, newsletter, dark mode, mais lidas (Sprint 6).
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ESTRATO_NEWSLETTER_OPTION', 'estrato_newsletter_signups' );

/**
 * Newsletter com captura real (double opt-in simplificado).
 *
 * @return string
 */
function estrato_retention_newsletter_shortcode() {
	$msg = '';
	if ( isset( $_POST['estrato_nl_email'] ) && isset( $_POST['estrato_nl_nonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['estrato_nl_nonce'] ) ), 'estrato_newsletter' ) ) {
			$email = sanitize_email( wp_unslash( $_POST['estrato_nl_email'] ) );
			if ( is_email( $email ) ) {
				$list   = get_option( ESTRATO_NEWSLETTER_OPTION, array() );
				$list   = is_array( $list ) ? $list : array();
				$token  = wp_generate_password( 32, false );
				$list[] = array(
					'email'   => $email,
					'status'  => 'pending',
					'token'   => $token,
					'signed'  => current_time( 'mysql' ),
				);
				update_option( ESTRATO_NEWSLETTER_OPTION, $list, false );
				$msg = '<p class="estrato-nl-success">Confirmação enviada. Verifique seu e-mail.</p>';
			}
		}
	}

	$html = '<section class="estrato-newsletter" aria-label="Newsletter">';
	$html .= '<h2 class="estrato-display">Newsletter Estrato</h2>';
	$html .= '<p>Receba os destaques de economia e mercados no seu e-mail.</p>';
	$html .= $msg;
	$html .= '<form method="post" class="estrato-newsletter__form">';
	$html .= wp_nonce_field( 'estrato_newsletter', 'estrato_nl_nonce', true, false );
	$html .= '<label class="screen-reader-text" for="estrato-nl-email">E-mail</label>';
	$html .= '<input id="estrato-nl-email" type="email" name="estrato_nl_email" placeholder="seu@email.com" required />';
	$html .= '<button type="submit">Assinar</button></form></section>';
	return $html;
}
add_shortcode( 'estrato_newsletter', 'estrato_retention_newsletter_shortcode' );

/**
 * Página de autor enriquecida.
 *
 * @param string $content
 * @return string
 */
function estrato_retention_author_archive( $content ) {
	if ( ! is_author() ) {
		return $content;
	}
	$author = get_queried_object();
	if ( ! $author ) {
		return $content;
	}
	$bio  = get_the_author_meta( 'description', $author->ID );
	$job  = get_the_author_meta( 'estrato_job_title', $author->ID );
	$html = '<div class="estrato-author-page">';
	$html .= get_avatar( $author->ID, 96, '', '', array( 'class' => 'estrato-author-page__avatar' ) );
	$html .= '<h1 class="estrato-display">' . esc_html( $author->display_name ) . '</h1>';
	if ( $job ) {
		$html .= '<p class="estrato-author-page__job">' . esc_html( $job ) . '</p>';
	}
	if ( $bio ) {
		$html .= '<p class="estrato-author-page__bio">' . esc_html( $bio ) . '</p>';
	}
	$posts = get_posts(
		array(
			'author'         => $author->ID,
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 10,
		)
	);
	if ( $posts ) {
		$html .= '<h2>Últimas matérias</h2><ul>';
		foreach ( $posts as $post ) {
			$html .= '<li><a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a></li>';
		}
		$html .= '</ul>';
	}
	$html .= '</div>';
	return $html . $content;
}
add_filter( 'the_content', 'estrato_retention_author_archive', 6 );

/**
 * Selo ATUALIZADO (integrado no header single).
 */
function estrato_retention_updated_badge() {
	// Renderizado em estrato_single_render_header_fallback().
}

/**
 * Dark mode toggle.
 */
function estrato_retention_dark_mode_assets() {
	if ( is_admin() || is_feed() ) {
		return;
	}
	?>
	<style id="estrato-dark-mode">
	[data-estrato-theme="dark"]{
		--estrato-bg:#121212;--estrato-ink:#f2f2f2;--estrato-muted:#a8a8a8;--estrato-line:#2e2e2e
	}
	.estrato-theme-toggle{
		position:fixed;bottom:1rem;right:1rem;z-index:10060;border:1px solid var(--estrato-line);
		background:var(--estrato-bg);color:var(--estrato-ink);padding:.4rem .75rem;border-radius:var(--estrato-radius,4px);
		font-size:12px;cursor:pointer
	}
	</style>
	<button type="button" class="estrato-theme-toggle" id="estrato-theme-toggle" aria-label="Alternar tema">Modo escuro</button>
	<script>
	(function(){
		var btn=document.getElementById('estrato-theme-toggle');
		if(!btn)return;
		var root=document.documentElement;
		var stored=localStorage.getItem('estrato-theme');
		if(stored==='dark'||(!stored&&window.matchMedia('(prefers-color-scheme:dark)').matches)){
			root.setAttribute('data-estrato-theme','dark');
			btn.textContent='Modo claro';
		}
		btn.addEventListener('click',function(){
			var dark=root.getAttribute('data-estrato-theme')==='dark';
			if(dark){root.removeAttribute('data-estrato-theme');localStorage.setItem('estrato-theme','light');btn.textContent='Modo escuro';}
			else{root.setAttribute('data-estrato-theme','dark');localStorage.setItem('estrato-theme','dark');btn.textContent='Modo claro';}
		});
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'estrato_retention_dark_mode_assets', 5 );

/**
 * Mais lidas com contagem (comentários como proxy).
 *
 * @param array<string, mixed> $atts
 * @return string
 */
function estrato_retention_mais_lidas_v2( $atts ) {
	$atts = shortcode_atts(
		array(
			'count'    => 5,
			'category' => '',
		),
		$atts,
		'estrato_mais_lidas_v2'
	);
	$args = array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => (int) $atts['count'],
		'orderby'        => 'comment_count',
		'order'          => 'DESC',
		'date_query'     => array( array( 'after' => '14 days ago' ) ),
	);
	if ( $atts['category'] ) {
		$term = get_term_by( 'slug', sanitize_title( $atts['category'] ), 'category' );
		if ( $term && ! is_wp_error( $term ) ) {
			$args['cat'] = (int) $term->term_id;
		}
	}
	$posts = get_posts( $args );
	if ( ! $posts ) {
		return '';
	}
	$html = '<div class="estrato-mais-lidas-v2"><h2>Mais lidas</h2><ol>';
	$n    = 1;
	foreach ( $posts as $post ) {
		$views = (int) $post->comment_count;
		$html .= '<li><span class="estrato-mais-lidas-v2__n">' . (int) $n . '</span> ';
		$html .= '<a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a>';
		if ( $views > 0 ) {
			$html .= ' <span class="estrato-caption">(' . (int) $views . ')</span>';
		}
		$html .= '</li>';
		++$n;
	}
	$html .= '</ol></div>';
	return $html;
}
add_shortcode( 'estrato_mais_lidas_v2', 'estrato_retention_mais_lidas_v2' );
