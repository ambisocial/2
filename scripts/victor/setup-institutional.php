<?php
/**
 * Sprint 3 — páginas institucionais, autores E-E-A-T, menu footer.
 *
 * Uso: sudo -u www-data wp --path=/var/www/estrato.cc eval-file setup-institutional.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( "Execute via WP-CLI eval-file.\n" );
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

/**
 * @param string $slug
 * @param string $title
 * @param string $content
 * @return int
 */
function estrato_institutional_upsert_page( $slug, $title, $content ) {
	$existing = get_page_by_path( $slug, OBJECT, 'page' );
	$data     = array(
		'post_title'   => $title,
		'post_name'    => $slug,
		'post_content' => $content,
		'post_status'  => 'publish',
		'post_type'    => 'page',
	);

	if ( $existing ) {
		$data['ID'] = $existing->ID;
		$page_id    = wp_update_post( $data, true );
	} else {
		$page_id = wp_insert_post( $data, true );
	}

	if ( is_wp_error( $page_id ) ) {
		WP_CLI::warning( "Página $slug: " . $page_id->get_error_message() );
		return 0;
	}

	WP_CLI::log( "Página /$slug/ → #$page_id" );
	return (int) $page_id;
}

$sobre = <<<'HTML'
<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Sobre o Estrato</h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>O <strong>Estrato</strong> é um portal de economia, mercados financeiros e finanças pessoais no Brasil. Nossa missão é traduzir dados, políticas e movimentos de mercado em informação clara, verificável e útil para leitores, investidores e profissionais.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">O que fazemos</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul class="wp-block-list"><li>Cobertura diária de macroeconomia, mercados, negócios e finanças pessoais</li><li>Contexto editorial sobre Selic, Ibovespa, dólar, inflação e empresas listadas</li><li>Curadoria de fontes públicas e agências de notícias com atribuição transparente</li><li>Equipe editorial especializada por área de cobertura</li></ul>
<!-- /wp:list -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Metodologia</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Cada matéria passa por triagem editorial, checagem de fonte primária e classificação por categoria. Quando utilizamos dados de terceiros (B3, Banco Central, IBGE, CVM), indicamos a origem e a data de referência. Correções são publicadas com registro visível na <a href="/politica-editorial/">política editorial</a>.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Quem edita</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>O Estrato é operado pela <strong>Estrato Mídia e Conteúdo Ltda.</strong>, com redação em São Paulo. Conheça nossos especialistas nas páginas de autor e entre em contato pelo formulário em <a href="/contato/">/contato/</a>.</p>
<!-- /wp:paragraph -->
HTML;

$politica_editorial = <<<'HTML'
<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Política editorial</h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>O Estrato segue padrões de transparência para conteúdo financeiro (YMYL). Esta política descreve como selecionamos, produzimos e corrigimos informações.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Fontes e atribuição</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul class="wp-block-list"><li>Priorizamos fontes primárias: comunicados oficiais, dados de reguladores e demonstrações publicadas</li><li>Agências e portais parceiros são citados com link para a matéria original</li><li>Não reproduzimos integralmente conteúdo protegido sem permissão</li><li>Opinião de analistas é identificada como tal, separada de notícia factual</li></ul>
<!-- /wp:list -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Correções</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Erros factuais são corrigidos o mais rápido possível, com indicação de atualização no topo ou rodapé da matéria. Para solicitar correção, use o <a href="/contato/">formulário de contato</a> informando o link da matéria.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Conflitos de interesse</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Autores não cobrem empresas nas quais possuem posição relevante não divulgada. Patrocínios, branded content e publicidade são claramente identificados e não influenciam a linha editorial de notícias.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Independência</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>A redação mantém autonomia sobre manchetes, classificação e destaques. Nenhum anunciante ou parceiro comercial altera o conteúdo informativo após publicação.</p>
<!-- /wp:paragraph -->
HTML;

$contato = <<<'HTML'
<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Contato</h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Envie sugestões de pauta, pedidos de correção ou propostas comerciais. Respondemos em até 2 dias úteis.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><strong>E-mail:</strong> <a href="mailto:contato@estrato.cc">contato@estrato.cc</a><br><strong>Redação:</strong> <a href="mailto:redacao@estrato.cc">redacao@estrato.cc</a></p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[contact-form-7 id="4" title="Contact form 1"]
<!-- /wp:shortcode -->
HTML;

$privacidade = <<<'HTML'
<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Política de privacidade</h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Esta política descreve como o <strong>Estrato</strong> (estrato.cc), operado pela Estrato Mídia e Conteúdo Ltda., coleta, usa e protege dados pessoais em conformidade com a Lei Geral de Proteção de Dados (LGPD — Lei 13.709/2018).</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Dados que coletamos</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul class="wp-block-list"><li><strong>Formulário de contato:</strong> nome, e-mail e mensagem que você enviar voluntariamente</li><li><strong>Navegação:</strong> endereço IP, tipo de navegador, páginas visitadas e cookies técnicos</li><li><strong>Newsletter:</strong> e-mail, quando você optar por receber comunicações</li></ul>
<!-- /wp:list -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Finalidade</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Utilizamos os dados para responder contatos, melhorar o site, medir audiência (analytics) e, com seu consentimento, enviar comunicações. Não vendemos dados pessoais a terceiros.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Cookies</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Utilizamos cookies essenciais para funcionamento do site e, quando aplicável, cookies de medição (ex.: Google Analytics). Você pode gerenciar cookies nas configurações do navegador.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Seus direitos</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Você pode solicitar acesso, correção, exclusão ou portabilidade dos seus dados pelo e-mail <a href="mailto:privacidade@estrato.cc">privacidade@estrato.cc</a>. O encarregado de dados (DPO) responde em até 15 dias.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Alterações</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Esta política pode ser atualizada. A data da última revisão consta no rodapé desta página. O endereço do site é: https://estrato.cc.</p>
<!-- /wp:paragraph -->
HTML;

estrato_institutional_upsert_page( 'sobre', 'Sobre', $sobre );
estrato_institutional_upsert_page( 'politica-editorial', 'Política editorial', $politica_editorial );
estrato_institutional_upsert_page( 'contato', 'Contato', $contato );

$privacy_page = get_post( 3 );
if ( $privacy_page && 'page' === $privacy_page->post_type ) {
	wp_update_post(
		array(
			'ID'           => 3,
			'post_title'   => 'Política de privacidade',
			'post_name'    => 'politica-de-privacidade',
			'post_content' => $privacidade,
			'post_status'  => 'publish',
		)
	);
	WP_CLI::log( 'Política de privacidade #3 publicada' );
} else {
	estrato_institutional_upsert_page( 'politica-de-privacidade', 'Política de privacidade', $privacidade );
}

// Remover página de exemplo.
$example = get_page_by_path( 'pagina-exemplo', OBJECT, 'page' );
if ( $example ) {
	wp_delete_post( $example->ID, true );
	WP_CLI::log( 'Página de exemplo removida' );
}

// Atualizar admin principal.
wp_update_user(
	array(
		'ID'           => 1,
		'display_name' => 'Redação Estrato',
		'description'  => 'Equipe editorial do Estrato. Coordenação de pautas, revisão e publicação.',
	)
);
update_user_meta( 1, 'estrato_job_title', 'Editor-chefe' );
update_user_meta( 1, 'wpseo_job_title', 'Editor-chefe' );

/**
 * @param string $name
 * @return string
 */
function estrato_author_avatar_url( $name ) {
	return 'https://ui-avatars.com/api/?name=' . rawurlencode( $name ) . '&size=256&background=000000&color=9AFF33&format=png';
}

$authors = array(
	array(
		'login'       => 'ana-economia',
		'email'       => 'ana.ribeiro@estrato.cc',
		'display'     => 'Ana Ribeiro',
		'first'       => 'Ana',
		'last'        => 'Ribeiro',
		'category'    => 'economia',
		'job'         => 'Editora de macroeconomia',
		'bio'         => 'Jornalista especializada em macroeconomia, política monetária e indicadores do Brasil. Acompanha Copom, PIB e emprego.',
	),
	array(
		'login'       => 'marcos-mercados',
		'email'       => 'marcos.vieira@estrato.cc',
		'display'     => 'Marcos Vieira',
		'first'       => 'Marcos',
		'last'        => 'Vieira',
		'category'    => 'mercados',
		'job'         => 'Editor de mercados',
		'bio'         => 'Cobre Ibovespa, renda fixa, commodities e fluxo estrangeiro. Experiência em mesas de operação e redações financeiras.',
	),
	array(
		'login'       => 'lucia-negocios',
		'email'       => 'lucia.mendes@estrato.cc',
		'display'     => 'Lúcia Mendes',
		'first'       => 'Lúcia',
		'last'        => 'Mendes',
		'category'    => 'negocios',
		'job'         => 'Editora de negócios',
		'bio'         => 'Reporta fusões, resultados corporativos e estratégia de empresas listadas e startups.',
	),
	array(
		'login'       => 'pedro-financas',
		'email'       => 'pedro.alves@estrato.cc',
		'display'     => 'Pedro Alves',
		'first'       => 'Pedro',
		'last'        => 'Alves',
		'category'    => 'financas-pessoais',
		'job'         => 'Editor de finanças pessoais',
		'bio'         => 'Escreve sobre orçamento, crédito, investimentos para iniciantes e planejamento financeiro.',
	),
	array(
		'login'       => 'rafa-cripto',
		'email'       => 'rafa.costa@estrato.cc',
		'display'     => 'Rafa Costa',
		'first'       => 'Rafa',
		'last'        => 'Costa',
		'category'    => 'criptomoedas',
		'job'         => 'Editor de criptoativos',
		'bio'         => 'Acompanha Bitcoin, Ethereum, regulação de ativos digitais e infraestrutura blockchain.',
	),
	array(
		'login'       => 'julia-agro',
		'email'       => 'julia.santos@estrato.cc',
		'display'     => 'Júlia Santos',
		'first'       => 'Júlia',
		'last'        => 'Santos',
		'category'    => 'agronegocio',
		'job'         => 'Editora de agronegócio',
		'bio'         => 'Cobre safras, exportações, commodities agrícolas e políticas do setor no Brasil.',
	),
	array(
		'login'       => 'henrique-mundo',
		'email'       => 'henrique.lima@estrato.cc',
		'display'     => 'Henrique Lima',
		'first'       => 'Henrique',
		'last'        => 'Lima',
		'category'    => 'mundo',
		'job'         => 'Editor de economia internacional',
		'bio'         => 'Analisa Fed, BCE, China e geopolítica com impacto nos mercados brasileiros.',
	),
);

$category_author_map = array();

foreach ( $authors as $author ) {
	$user_id = username_exists( $author['login'] );
	if ( ! $user_id ) {
		$user_id = wp_insert_user(
			array(
				'user_login'   => $author['login'],
				'user_email'   => $author['email'],
				'user_pass'    => wp_generate_password( 24, true, true ),
				'display_name' => $author['display'],
				'first_name'   => $author['first'],
				'last_name'    => $author['last'],
				'description'  => $author['bio'],
				'role'         => 'author',
			)
		);
	} else {
		wp_update_user(
			array(
				'ID'           => $user_id,
				'display_name' => $author['display'],
				'first_name'   => $author['first'],
				'last_name'    => $author['last'],
				'description'  => $author['bio'],
				'role'         => 'author',
			)
		);
	}

	if ( is_wp_error( $user_id ) ) {
		WP_CLI::warning( 'Autor ' . $author['login'] . ': ' . $user_id->get_error_message() );
		continue;
	}

	update_user_meta( $user_id, 'estrato_job_title', $author['job'] );
	update_user_meta( $user_id, 'wpseo_job_title', $author['job'] );
	update_user_meta( $user_id, 'estrato_avatar_url', estrato_author_avatar_url( $author['display'] ) );

	$category_author_map[ $author['category'] ] = (int) $user_id;
	WP_CLI::log( 'Autor ' . $author['display'] . " (#$user_id) → {$author['category']}" );
}

update_option( 'estrato_category_author_map', $category_author_map, false );

// Menu footer institucional (PressGrid location: footer).
$menu_name = 'Estrato Institucional';
$menu      = wp_get_nav_menu_object( $menu_name );
if ( ! $menu ) {
	$menu_id = wp_create_nav_menu( $menu_name );
} else {
	$menu_id = (int) $menu->term_id;
	$items   = wp_get_nav_menu_items( $menu_id );
	if ( $items ) {
		foreach ( $items as $item ) {
			wp_delete_post( $item->ID, true );
		}
	}
}

$footer_links = array(
	'Sobre'                   => '/sobre/',
	'Política editorial'      => '/politica-editorial/',
	'Contato'                 => '/contato/',
	'Política de privacidade' => '/politica-de-privacidade/',
);

foreach ( $footer_links as $title => $path ) {
	wp_update_nav_menu_item(
		$menu_id,
		0,
		array(
			'menu-item-title'  => $title,
			'menu-item-url'    => home_url( $path ),
			'menu-item-status' => 'publish',
		)
	);
}

$locations         = get_theme_mod( 'nav_menu_locations', array() );
$locations['footer'] = $menu_id;
set_theme_mod( 'nav_menu_locations', $locations );

set_theme_mod( 'pressgrid_show_footer_credit', false );

WP_CLI::success( 'Sprint 3 institucional configurado.' );
