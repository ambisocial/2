<?php
/**
 * Header estilo G1 (g1.globo.com) — barra rede, faixa principal, navegação horizontal.
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Walker: chevron separado do link da editoria (padrão G1).
 */
class Estrato_G1_Nav_Walker extends Walker_Nav_Menu {
	/**
	 * @var string
	 */
	private $estrato_pending_panel_id = '';

	/**
	 * @param string   $output
	 * @param WP_Post  $item
	 * @param int      $depth
	 * @param stdClass $args
	 * @param int      $id
	 */
	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		parent::start_el( $output, $item, $depth, $args, $id );
		if ( 0 !== (int) $depth ) {
			return;
		}
		$classes = is_array( $item->classes ) ? $item->classes : array();
		if ( ! in_array( 'menu-item-has-children', $classes, true ) && ! in_array( 'estrato-mega-parent', $classes, true ) ) {
			return;
		}
		$panel = 'estrato-mega-panel-' . (int) $item->ID;
		$this->estrato_pending_panel_id = $panel;
		$title = wp_strip_all_tags( (string) $item->title );
		$output .= sprintf(
			'<button type="button" class="estrato-mega-toggle" aria-expanded="false" aria-controls="%1$s" aria-label="%2$s"><span class="estrato-mega-toggle__icon" aria-hidden="true"></span></button>',
			esc_attr( $panel ),
			esc_attr( sprintf( 'Abrir submenu de %s', $title ) )
		);
	}

	/**
	 * @param string   $output
	 * @param int      $depth
	 * @param stdClass $args
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ) {
		if ( isset( $args->item_spacing ) && 'discard' === $args->item_spacing ) {
			$n = '';
			$t = '';
		} else {
			$n = "\n";
			$t = "\t";
		}
		$indent = str_repeat( $t, $depth );
		$id_attr = '';
		if ( '' !== $this->estrato_pending_panel_id ) {
			$id_attr = ' id="' . esc_attr( $this->estrato_pending_panel_id ) . '"';
			$this->estrato_pending_panel_id = '';
		}
		$output .= "{$n}{$indent}<ul class=\"sub-menu\"{$id_attr} role=\"list\">{$n}";
	}
}

/**
 * Skip link — primeiro elemento focável (best practice de portais).
 */
function estrato_g1_skip_link() {
	if ( is_admin() || is_feed() ) {
		return;
	}
	echo '<a class="estrato-skip-link" href="#estrato-main">Pular para o conteúdo</a>';
}
add_action( 'wp_body_open', 'estrato_g1_skip_link', 1 );

/**
 * Âncora de conteúdo após o header.
 */
function estrato_g1_main_landmark() {
	if ( is_admin() || is_feed() ) {
		return;
	}
	echo '<div id="estrato-main" tabindex="-1"></div>';
}
add_action( 'wp_body_open', 'estrato_g1_main_landmark', 6 );

/**
 * Cor e rótulo do header principal (home = vermelho G1; seção = branding da editoria).
 *
 * @return array{bg:string,fg:string,label:string,section:bool}
 */
function estrato_g1_header_context() {
	$defaults = array(
		'bg'      => '#C4170C',
		'fg'      => '#ffffff',
		'label'   => '',
		'section' => false,
	);

	if ( function_exists( 'estrato_portal_get_archive_term_id' )
		&& function_exists( 'estrato_portal_resolve_branding_for_term' ) ) {
		$term_id = estrato_portal_get_archive_term_id();
		if ( $term_id ) {
			$branding = estrato_portal_resolve_branding_for_term( $term_id );
			if ( ! empty( $branding['primary_color'] ) ) {
				$defaults['bg']      = (string) $branding['primary_color'];
				$defaults['fg']      = '#ffffff';
				$defaults['label']   = (string) ( $branding['brand_name'] ?? '' );
				$defaults['section'] = true;
				if ( empty( $defaults['label'] ) ) {
					$term = get_term( $term_id, 'category' );
					if ( $term && ! is_wp_error( $term ) ) {
						$defaults['label'] = $term->name;
					}
				}
				return $defaults;
			}
		}
	}

	$config = function_exists( 'estrato_portal_get_config' ) ? estrato_portal_get_config() : array();
	$primary = $config['branding']['primary_color'] ?? '#000000';
	if ( '#000000' === strtolower( $primary ) ) {
		return $defaults;
	}
	$defaults['bg'] = $primary;
	return $defaults;
}

/**
 * Renderiza o header G1.
 */
function estrato_g1_render_header() {
	if ( is_admin() || is_feed() ) {
		return;
	}

	$ctx       = estrato_g1_header_context();
	$current   = function_exists( 'estrato_nav_current_portal_id' ) ? estrato_nav_current_portal_id() : '';
	$site_name = get_bloginfo( 'name' );
	$search    = home_url( '/' );
	$logo_id   = (int) get_theme_mod( 'custom_logo' );
	$logo_html = '';
	if ( $logo_id ) {
		$logo_html = wp_get_attachment_image(
			$logo_id,
			'medium',
			false,
			array(
				'class'    => 'estrato-g1-header__logo-img',
				'alt'      => $site_name,
				'decoding' => 'async',
			)
		);
	}

	$style_attr = sprintf(
		' style="--estrato-g1-bar-bg:%1$s;--estrato-g1-bar-fg:%2$s"',
		esc_attr( $ctx['bg'] ),
		esc_attr( $ctx['fg'] )
	);

	?>
	<header id="estrato-g1-header" class="estrato-g1-header<?php echo $ctx['section'] ? ' estrato-g1-header--section' : ' estrato-g1-header--home'; ?>" role="banner"<?php echo $style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<div class="estrato-g1-header__rede" aria-label="Rede Estrato">
			<div class="estrato-g1-header__inner">
				<span class="estrato-g1-header__rede-label">Rede Estrato</span>
				<ul class="estrato-g1-header__rede-list">
					<?php if ( function_exists( 'estrato_nav_network_catalog' ) ) : ?>
						<?php foreach ( estrato_nav_network_catalog() as $node ) : ?>
							<?php
							$is_current = ( $node['id'] === $current );
							?>
							<li class="<?php echo $is_current ? 'is-current' : ''; ?>">
								<?php if ( $is_current ) : ?>
									<span aria-current="page"><?php echo esc_html( $node['name'] ); ?></span>
								<?php else : ?>
									<a href="<?php echo esc_url( $node['url'] ); ?>"><?php echo esc_html( $node['name'] ); ?></a>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					<?php endif; ?>
				</ul>
			</div>
		</div>

		<div class="estrato-g1-header__principal">
			<div class="estrato-g1-header__inner estrato-g1-header__principal-row">
				<button type="button" class="estrato-g1-header__menu-btn" id="estrato-g1-menu-btn" aria-controls="estrato-g1-nav" aria-expanded="false" aria-label="Abrir menu">
					<span class="estrato-g1-header__menu-icon" aria-hidden="true"></span>
				</button>
				<div class="estrato-g1-header__logo-wrap">
					<a class="estrato-g1-header__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php echo esc_attr( $site_name ); ?>">
						<?php if ( $logo_html ) : ?>
							<?php echo $logo_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php else : ?>
							<span class="estrato-g1-header__logo-text"><?php echo esc_html( $site_name ); ?></span>
						<?php endif; ?>
					</a>
				</div>
				<div class="estrato-g1-header__actions">
					<button type="button" class="estrato-g1-header__search-btn" id="estrato-g1-search-btn" aria-controls="estrato-g1-search" aria-expanded="false" aria-label="Buscar">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
					</button>
				</div>
			</div>
		</div>

		<?php if ( $ctx['label'] ) : ?>
			<div class="estrato-g1-header__editoria">
				<div class="estrato-g1-header__inner">
					<span class="estrato-g1-header__editoria-label"><?php echo esc_html( $ctx['label'] ); ?></span>
				</div>
			</div>
		<?php endif; ?>

		<nav id="estrato-g1-nav" class="estrato-g1-header__nav" aria-label="Editorias">
			<div class="estrato-g1-header__inner">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'menu_class'     => 'estrato-g1-menu estrato-mega-nav',
						'container'      => false,
						'fallback_cb'    => false,
						'depth'          => 3,
						'walker'         => new Estrato_G1_Nav_Walker(),
					)
				);
				?>
			</div>
		</nav>

		<div id="estrato-g1-search" class="estrato-g1-header__search-panel" hidden>
			<div class="estrato-g1-header__inner">
				<form class="estrato-g1-header__search-form" role="search" method="get" action="<?php echo esc_url( $search ); ?>">
					<label class="screen-reader-text" for="estrato-g1-search-input">Buscar</label>
					<input id="estrato-g1-search-input" type="search" name="s" placeholder="Buscar notícias" autocomplete="off" />
					<button type="submit">Buscar</button>
				</form>
			</div>
		</div>
	</header>
	<?php
}
add_action( 'wp_body_open', 'estrato_g1_render_header', 5 );

/**
 * Oculta header legado PressGrid (substituído pelo G1).
 */
function estrato_g1_header_hide_pressgrid() {
	if ( is_admin() || is_feed() ) {
		return;
	}
	$css = '.pg-topbar,.pg-masthead,.pg-nav-wrap{display:none!important}'
		. '.estrato-columns-ribbon{display:none!important}'
		// Fix pós auditoria visual 2026-07-13 (P1 featured hidden + P1 kicker dup):
		// esconde SÓ o cabeçalho antigo do PressGrid; o wrapper do featured
		// (`.pg-featured-image`, `.pg-single-thumbnail`) continua visível para
		// o nosso featured render logo abaixo do header. Também esconde
		// `.pg-single-breadcrumbs` que duplica o breadcrumb do template.
		. 'header.pg-single-header,.pg-single-header .pg-single-title,.pg-single-header .pg-single-meta,.pg-single-header .pg-post-category,.pg-single-breadcrumbs,.pg-single-breadcrumb{display:none!important}'
		// Aumenta z-index e adiciona safe-area no back-to-top do PressGrid.
		. '.pg-back-to-top,.pg-scroll-top,#back-to-top{bottom:calc(1rem + env(safe-area-inset-bottom,0px))!important}'
		. '.pg-breaking-bar{background:#fff1e5!important;border-top:1px solid #e8d5c4;border-bottom:1px solid #e8d5c4;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;font-size:.8125rem;color:#333}'
		. '.pg-breaking-label{background:#C4170C!important;color:#fff!important;font-weight:700;padding:.35rem .75rem}';
	if ( function_exists( 'estrato_perf_style_add' ) ) {
		estrato_perf_style_add( 'estrato-g1-header-hide-pressgrid', $css, 'critical' );
	} else {
		echo '<style id="estrato-g1-header-hide-pressgrid">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_head', 'estrato_g1_header_hide_pressgrid', 2 );

/**
 * CSS header G1.
 */
function estrato_g1_header_styles() {
	if ( is_admin() || is_feed() ) {
		return;
	}
	ob_start();
	?>
	.estrato-skip-link{
		position:absolute;left:1rem;top:0;transform:translateY(-120%);
		background:#111;color:#fff;padding:.65rem 1rem;z-index:100000;font-weight:700;text-decoration:none;border-radius:0 0 4px 4px
	}
	.estrato-skip-link:focus,.estrato-skip-link:focus-visible{transform:translateY(0);outline:2px solid #9AFF33;outline-offset:2px}
	.estrato-g1-header{
		--estrato-g1-bar-bg:#C4170C;--estrato-g1-bar-fg:#fff;
		--estrato-g1-rede-bg:#1e1e1e;--estrato-g1-nav-bg:#fff;--estrato-g1-nav-fg:#1e1e1e;
		font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
		position:sticky;top:0;z-index:10050;background:#fff;box-shadow:0 1px 0 rgba(0,0,0,.06)
	}
	.estrato-g1-header a:focus-visible,.estrato-g1-header button:focus-visible,.estrato-g1-header input:focus-visible{
		outline:2px solid #9AFF33;outline-offset:2px
	}
	.estrato-g1-header__inner{max-width:1200px;margin:0 auto;padding:0 1rem}
	.estrato-g1-header__rede{background:var(--estrato-g1-rede-bg);color:#ccc;font-size:.75rem}
	.estrato-g1-header__rede .estrato-g1-header__inner{display:flex;align-items:center;gap:1rem;min-height:32px;overflow-x:auto}
	.estrato-g1-header__rede-label{font-weight:700;color:#fff;white-space:nowrap;text-transform:uppercase;letter-spacing:.04em}
	.estrato-g1-header__rede-list{display:flex;gap:.85rem;list-style:none;margin:0;padding:0;white-space:nowrap}
	.estrato-g1-header__rede-list a,.estrato-g1-header__rede-list span{color:#ccc;text-decoration:none}
	.estrato-g1-header__rede-list a:hover{color:#fff;text-decoration:underline}
	.estrato-g1-header__rede-list .is-current span{color:#fff;font-weight:700}
	.estrato-g1-header__principal{background:var(--estrato-g1-bar-bg);color:var(--estrato-g1-bar-fg)}
	.estrato-g1-header__principal-row{display:grid;grid-template-columns:48px 1fr 48px;align-items:center;min-height:56px}
	.estrato-g1-header__menu-btn,.estrato-g1-header__search-btn{
		background:transparent;border:0;color:inherit;cursor:pointer;padding:.5rem;
		display:flex;align-items:center;justify-content:center;width:44px;height:44px
	}
	.estrato-g1-header__menu-icon{
		display:block;width:18px;height:2px;background:currentColor;position:relative
	}
	.estrato-g1-header__menu-icon::before,.estrato-g1-header__menu-icon::after{
		content:"";position:absolute;left:0;width:18px;height:2px;background:currentColor
	}
	.estrato-g1-header__menu-icon::before{top:-6px}
	.estrato-g1-header__menu-icon::after{top:6px}
	.estrato-g1-header__menu-btn[aria-expanded="true"] .estrato-g1-header__menu-icon{background:transparent}
	.estrato-g1-header__menu-btn[aria-expanded="true"] .estrato-g1-header__menu-icon::before{top:0;transform:rotate(45deg)}
	.estrato-g1-header__menu-btn[aria-expanded="true"] .estrato-g1-header__menu-icon::after{top:0;transform:rotate(-45deg)}
	.estrato-g1-header__logo-wrap{display:flex;justify-content:center;align-items:center}
	.estrato-g1-header__logo{display:inline-flex;align-items:center;text-decoration:none;color:inherit}
	.estrato-g1-header__logo-img{max-height:36px;width:auto;filter:brightness(0) invert(1)}
	.estrato-g1-header__logo-text{font-size:1.75rem;font-weight:800;letter-spacing:-.03em;line-height:1}
	.estrato-g1-header__actions{display:flex;justify-content:flex-end}
	.estrato-g1-header__editoria{background:var(--estrato-g1-bar-bg);color:var(--estrato-g1-bar-fg);border-top:1px solid rgba(255,255,255,.15)}
	.estrato-g1-header__editoria .estrato-g1-header__inner{padding:.4rem 1rem}
	.estrato-g1-header__editoria-label{font-size:.8125rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em}
	.estrato-g1-header__nav{background:var(--estrato-g1-nav-bg);border-bottom:1px solid #e5e5e5}
	.estrato-g1-header__nav .estrato-g1-header__inner{overflow-x:auto;-webkit-overflow-scrolling:touch}
	.estrato-g1-menu{display:flex;flex-wrap:nowrap;gap:0;list-style:none;margin:0;padding:0;min-height:44px;align-items:stretch}
	.estrato-g1-menu>li{position:relative;flex:0 0 auto}
	.estrato-g1-menu>li>a{
		display:flex;align-items:center;padding:.65rem .9rem;color:var(--estrato-g1-nav-fg);
		font-size:.875rem;font-weight:600;text-decoration:none;white-space:nowrap;border-bottom:3px solid transparent
	}
	.estrato-g1-menu>li>a:hover,.estrato-g1-menu>li.current-menu-item>a,.estrato-g1-menu>li.current-menu-ancestor>a{
		color:#C4170C;border-bottom-color:#C4170C
	}
	.estrato-mega-toggle{
		display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;
		border:0;background:transparent;color:inherit;cursor:pointer;flex:0 0 auto;padding:0
	}
	.estrato-mega-toggle__icon{
		width:.55rem;height:.55rem;border-right:2px solid currentColor;border-bottom:2px solid currentColor;
		transform:rotate(45deg);margin-top:-.2rem;transition:transform .15s
	}
	.estrato-mega-parent.estrato-mega-open > .estrato-mega-toggle .estrato-mega-toggle__icon{transform:rotate(-135deg);margin-top:.2rem}
	/* Base = mobile: nav fechada, hamburger, submenu em fluxo. */
	.estrato-g1-header__nav{display:none;max-height:min(70vh,520px);overflow-y:auto}
	.estrato-g1-header__nav.estrato-g1-header__nav--open{display:block;border-top:1px solid rgba(255,255,255,.2);background:var(--estrato-g1-bar-bg)}
	.estrato-g1-header__nav--open .estrato-g1-menu{flex-direction:column;min-height:0}
	.estrato-g1-header__nav--open .estrato-g1-menu>li{display:flex;flex-wrap:wrap;align-items:center;border-bottom:1px solid rgba(255,255,255,.12)}
	.estrato-g1-header__nav--open .estrato-g1-menu>li>a{color:#fff;border-bottom-color:transparent;flex:1 1 auto}
	.estrato-g1-header__nav--open .estrato-mega-toggle{color:#fff}
	.estrato-g1-menu>li.estrato-mega-parent{position:relative}
	.estrato-g1-menu>li.estrato-mega-parent>.sub-menu{
		background:transparent;border:0;border-top:1px solid rgba(255,255,255,.15);border-radius:0;box-shadow:none;
		display:none;left:auto;list-style:none;margin:0;min-width:0;padding:.5rem 0 .5rem 1rem;position:relative;right:auto;top:auto;z-index:1;
		grid-template-columns:1fr;gap:0;flex:1 0 100%
	}
	.estrato-g1-menu>li.estrato-mega-parent.estrato-mega-open>.sub-menu{display:block}
	.estrato-g1-header__nav--open .estrato-g1-menu .sub-menu a{color:#fff}
	.estrato-g1-menu .sub-menu .menu-item{margin:0;padding:0}
	.estrato-g1-menu .sub-menu a{color:#1e293b;display:block;font-size:.875rem;font-weight:500;line-height:1.35;padding:.45rem 0;text-decoration:none;min-height:44px}
	.estrato-g1-menu .sub-menu a:hover{color:#C4170C}
	.estrato-g1-menu .estrato-mega-column>a{font-weight:700;border-left:3px solid #9aff33;padding-left:.5rem}
	.estrato-g1-header__search-panel{background:#f5f5f5;border-bottom:1px solid #e5e5e5;padding:.75rem 0}
	.estrato-g1-header__search-form{display:flex;gap:.5rem}
	.estrato-g1-header__search-form input{flex:1;border:1px solid #ccc;border-radius:4px;padding:.55rem .75rem;font-size:1rem}
	.estrato-g1-header__search-form button{background:#C4170C;color:#fff;border:0;border-radius:4px;padding:.55rem 1rem;font-weight:600;cursor:pointer;min-height:44px}
	body.estrato-nav-lock{overflow:hidden;touch-action:none}
	@media(min-width:960px){
		.estrato-g1-header__menu-btn{display:none}
		.estrato-mega-toggle{display:none}
		.estrato-g1-header__principal-row{grid-template-columns:1fr auto 1fr}
		.estrato-g1-header__logo-wrap{grid-column:2}
		.estrato-g1-header__actions{grid-column:3}
		.estrato-g1-header__nav{display:block;border-top:0;background:var(--estrato-g1-nav-bg);max-height:none;overflow:visible}
		.estrato-g1-header__nav.estrato-g1-header__nav--open{border-top:0;background:var(--estrato-g1-nav-bg)}
		.estrato-g1-header__nav--open .estrato-g1-menu,
		.estrato-g1-menu{flex-direction:row;min-height:44px}
		.estrato-g1-header__nav--open .estrato-g1-menu>li{display:block;border:0}
		.estrato-g1-header__nav--open .estrato-g1-menu>li>a,
		.estrato-g1-menu>li>a{color:var(--estrato-g1-nav-fg)}
		.estrato-g1-menu>li.estrato-mega-parent{position:relative}
		.estrato-g1-menu>li.estrato-mega-parent>.sub-menu{
			background:#fff;border:1px solid #e2e8f0;border-radius:0 0 6px 6px;box-shadow:0 12px 32px rgba(15,23,42,.12);
			display:none;left:0;min-width:220px;padding:1rem 1.25rem;position:absolute;right:auto;top:100%;z-index:9999;
			grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.35rem 1.5rem;flex:none
		}
		.estrato-g1-menu>li.estrato-mega-parent:hover>.sub-menu,
		.estrato-g1-menu>li.estrato-mega-parent:focus-within>.sub-menu,
		.estrato-g1-menu>li.estrato-mega-parent.estrato-mega-open>.sub-menu{display:grid}
		.estrato-g1-menu .sub-menu a{color:#1e293b;min-height:0;padding:.35rem 0}
		body.estrato-nav-lock{overflow:auto;touch-action:auto}
	}
	<?php
	$css = trim( ob_get_clean() );
	if ( function_exists( 'estrato_perf_style_add' ) ) {
		estrato_perf_style_add( 'estrato-g1-header-css', $css, 'main' );
	} else {
		echo '<style id="estrato-g1-header-css">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_head', 'estrato_g1_header_styles', 20 );

/**
 * JS — menu mobile, busca, teclado, Escape, focus trap (best practices portais).
 */
function estrato_g1_header_scripts() {
	if ( is_admin() || is_feed() ) {
		return;
	}
	?>
	<script id="estrato-g1-header-js">
	(function(){
		var root=document.getElementById('estrato-g1-header');
		if(!root)return;
		var menuBtn=document.getElementById('estrato-g1-menu-btn');
		var nav=document.getElementById('estrato-g1-nav');
		var searchBtn=document.getElementById('estrato-g1-search-btn');
		var searchPanel=document.getElementById('estrato-g1-search');
		var menu=root.querySelector('.estrato-g1-menu');
		var mq=window.matchMedia('(min-width:960px)');
		var lastFocus=null;

		function isDesktop(){return mq.matches;}
		function focusables(el){
			return Array.prototype.slice.call(el.querySelectorAll('a[href],button:not([disabled]),input:not([disabled]),[tabindex]:not([tabindex="-1"])'))
				.filter(function(n){return !n.hasAttribute('disabled') && n.getAttribute('aria-hidden')!=='true';});
		}
		function setMenuOpen(open){
			if(!nav||!menuBtn)return;
			nav.classList.toggle('estrato-g1-header__nav--open', open);
			menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
			menuBtn.setAttribute('aria-label', open ? 'Fechar menu' : 'Abrir menu');
			document.body.classList.toggle('estrato-nav-lock', open && !isDesktop());
			if(open && !isDesktop()){
				lastFocus=document.activeElement;
				var f=focusables(nav);
				if(f[0])f[0].focus();
			}else if(!open && lastFocus && lastFocus.focus){
				lastFocus.focus();
				lastFocus=null;
			}
		}
		function closeAllMegas(){
			root.querySelectorAll('.estrato-mega-open').forEach(function(li){
				li.classList.remove('estrato-mega-open');
				var t=li.querySelector('.estrato-mega-toggle');
				if(t)t.setAttribute('aria-expanded','false');
			});
		}
		function setMegaOpen(li, open){
			if(!li)return;
			if(open){
				root.querySelectorAll('.estrato-mega-open').forEach(function(other){
					if(other!==li){
						other.classList.remove('estrato-mega-open');
						var ot=other.querySelector('.estrato-mega-toggle');
						if(ot)ot.setAttribute('aria-expanded','false');
					}
				});
			}
			li.classList.toggle('estrato-mega-open', open);
			var t=li.querySelector('.estrato-mega-toggle');
			if(t)t.setAttribute('aria-expanded', open ? 'true' : 'false');
		}
		function closeSearch(){
			if(!searchPanel||!searchBtn)return;
			searchPanel.hidden=true;
			searchBtn.setAttribute('aria-expanded','false');
		}

		if(menuBtn&&nav){
			menuBtn.addEventListener('click',function(){
				setMenuOpen(!nav.classList.contains('estrato-g1-header__nav--open'));
			});
		}
		if(searchBtn&&searchPanel){
			searchBtn.addEventListener('click',function(){
				var willOpen=searchPanel.hidden;
				searchPanel.hidden=!willOpen;
				searchBtn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
				if(willOpen){
					var i=document.getElementById('estrato-g1-search-input');
					if(i)i.focus();
				}
			});
		}

		/* Chevron: abre/fecha submenu sem bloquear o link da editoria */
		root.addEventListener('click',function(e){
			var btn=e.target.closest('.estrato-mega-toggle');
			if(!btn||!root.contains(btn))return;
			e.preventDefault();
			e.stopPropagation();
			var li=btn.closest('li');
			setMegaOpen(li, !li.classList.contains('estrato-mega-open'));
		});

		/* Desktop: Enter/Espaço no link-pai abre mega; Escape fecha */
		if(menu){
			menu.addEventListener('keydown',function(e){
				var li=e.target.closest('li.estrato-mega-parent, li.menu-item-has-children');
				if(!li||!menu.contains(li))return;
				var isTop=(li.parentElement===menu);
				if(!isTop)return;
				if(e.key==='Enter' || e.key===' ' || e.key==='ArrowDown'){
					if(e.target.classList.contains('estrato-mega-toggle') || e.target.closest('.sub-menu'))return;
					if(isDesktop()){
						e.preventDefault();
						setMegaOpen(li, true);
						var first=li.querySelector('.sub-menu a');
						if(first)first.focus();
					}
				}
			});
		}

		document.addEventListener('keydown',function(e){
			if(e.key==='Escape'){
				var changed=false;
				if(nav&&nav.classList.contains('estrato-g1-header__nav--open')){setMenuOpen(false);changed=true;}
				if(searchPanel&&!searchPanel.hidden){closeSearch();changed=true;}
				if(root.querySelector('.estrato-mega-open')){closeAllMegas();changed=true;}
				if(changed)e.preventDefault();
				return;
			}
			/* Focus trap no drawer mobile */
			if(e.key!=='Tab')return;
			if(!nav||!nav.classList.contains('estrato-g1-header__nav--open')||isDesktop())return;
			var f=focusables(nav);
			if(!f.length)return;
			var first=f[0], last=f[f.length-1];
			if(e.shiftKey && document.activeElement===first){e.preventDefault();last.focus();}
			else if(!e.shiftKey && document.activeElement===last){e.preventDefault();first.focus();}
		});

		document.addEventListener('click',function(e){
			if(root.contains(e.target))return;
			closeAllMegas();
			if(nav&&nav.classList.contains('estrato-g1-header__nav--open')&&!isDesktop()){
				setMenuOpen(false);
			}
			closeSearch();
		});

		if(typeof mq.addEventListener==='function'){
			mq.addEventListener('change',function(){
				if(isDesktop()){
					setMenuOpen(false);
					document.body.classList.remove('estrato-nav-lock');
				}
			});
		}
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'estrato_g1_header_scripts', 25 );

/**
 * Remove header/nav legado PressGrid do DOM (menu único — Sprint 1 / B5).
 */
function estrato_g1_strip_pressgrid_nav_start() {
	if ( is_admin() || is_feed() || wp_doing_ajax() || wp_is_json_request() ) {
		return;
	}
	ob_start( 'estrato_g1_strip_pressgrid_nav_callback' );
}
add_action( 'template_redirect', 'estrato_g1_strip_pressgrid_nav_start', 0 );

/**
 * @param string $html
 * @param string $class
 * @return string
 */
function estrato_g1_remove_block_by_class( $html, $class ) {
	$pattern = '/<(div|header|nav)\b[^>]*\b' . preg_quote( $class, '/' ) . '\b[^>]*>/iu';
	if ( ! preg_match( $pattern, $html, $match, PREG_OFFSET_CAPTURE ) ) {
		return $html;
	}
	$tag   = strtolower( $match[1][0] );
	$start = (int) $match[0][1];
	$pos   = $start + strlen( $match[0][0] );
	$depth = 1;
	$len   = strlen( $html );
	while ( $pos < $len && $depth > 0 ) {
		if ( preg_match( '/<\/?' . $tag . '\b[^>]*>/iu', $html, $token, PREG_OFFSET_CAPTURE, $pos ) ) {
			$token_str = $token[0][0];
			$pos       = (int) $token[0][1] + strlen( $token_str );
			if ( '/' === $token_str[1] ) {
				--$depth;
			} elseif ( ! str_ends_with( rtrim( $token_str ), '/>' ) ) {
				++$depth;
			}
			continue;
		}
		break;
	}
	if ( $depth > 0 ) {
		return $html;
	}
	return substr( $html, 0, $start ) . substr( $html, $pos );
}

/**
 * @param string $html
 * @return string
 */
function estrato_g1_strip_pressgrid_nav_callback( $html ) {
	if ( ! is_string( $html ) || '' === $html ) {
		return $html;
	}
	foreach ( array( 'pg-topbar', 'pg-masthead', 'pg-nav-wrap' ) as $class ) {
		$prev = '';
		while ( $prev !== $html ) {
			$prev = $html;
			$html = estrato_g1_remove_block_by_class( $html, $class );
		}
	}
	return $html;
}
