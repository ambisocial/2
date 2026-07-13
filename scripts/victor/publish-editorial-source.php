<?php
/**
 * Publicação editorial com atribuição de fonte + artefatos Google News.
 *
 * Uso:
 *   wp eval-file publish-editorial-source.php
 *
 * Variáveis de ambiente opcionais:
 *   ESTRATO_SOURCE_URL, ESTRATO_SOURCE_NAME, ESTRATO_CATEGORY, ESTRATO_EXTERNAL_ID
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$source_url  = getenv( 'ESTRATO_SOURCE_URL' ) ?: 'https://www.lrcadefenseconsulting.com/2026/07/exercito-recebe-robo-nacional-de.html';
$source_name = getenv( 'ESTRATO_SOURCE_NAME' ) ?: 'LRCA Defense Consulting';
$category    = getenv( 'ESTRATO_CATEGORY' ) ?: 'negocios';
$external_id = getenv( 'ESTRATO_EXTERNAL_ID' ) ?: $source_url;

$title = 'Exército incorpora robô nacional de desativação de explosivos da Ambipar Robotics';

$excerpt = 'Força Terrestre formaliza aquisição de plataforma EOD produzida em Jacareí (SP) para reduzir risco de militares e cumprir requisitos de prontidão da ONU em missões de paz.';

$image_url = 'https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEiADTenFdnSYv65_zlcqFOGePRMxjK_72C6qFIE23IscCPeTpQkLAajGJcX7Zcnc58dWqgMsek0ianpKK0Ypbq4ePFWr3p2YF1r37Osbnf7NDHdAw-LRl0wk156THuKuT9ZJjI_9Gy_nbHgybkkVs5NkTTWFsicmKJwqFYaZizka3VLtA7y3qHECnvh8fHY/w1200-h630-p-k-no-nu/robo%20eod%20ambipar%20011%20(1).jpeg';

$content = <<<'HTML'
<p class="estrato-editorial-lead"><strong>Negócios —</strong> O Exército Brasileiro formalizou a aquisição de um robô de neutralização de artefatos explosivos (EOD) de fabricação nacional, segundo informações compiladas pela <em>LRCA Defense Consulting</em> a partir de nota oficial da Força. O equipamento foi produzido pela <strong>Ambipar Robotics</strong>, em Jacareí (SP), e deve reforçar operações de engenharia com menor exposição de militares em campo.</p>

<figure class="wp-block-image size-large"><img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEiADTenFdnSYv65_zlcqFOGePRMxjK_72C6qFIE23IscCPeTpQkLAajGJcX7Zcnc58dWqgMsek0ianpKK0Ypbq4ePFWr3p2YF1r37Osbnf7NDHdAw-LRl0wk156THuKuT9ZJjI_9Gy_nbHgybkkVs5NkTTWFsicmKJwqFYaZizka3VLtA7y3qHECnvh8fHY/w1200-h494/robo%20eod%20ambipar%20011%20(1).jpeg" alt="Robô EOD Ambipar Robotics para o Exército Brasileiro" width="1200" height="630" loading="eager"/></figure>

<h2>Contrato e finalidade operacional</h2>
<p>Pelo Departamento de Engenharia e Construção (DEC), a Força Terrestre incorpora plataforma remota para identificar, manipular e neutralizar explosivos sem aproximação direta em etapas críticas. A aquisição integra o planejamento logístico da engenharia militar e amplia a oferta de soluções robóticas nacionais no setor de defesa.</p>

<h2>Requisitos da ONU e certificação de tropas</h2>
<p>Segundo a publicação da LRCA com base na nota do Exército, o robô também atende ao <em>Peacekeeping Capability Readiness System</em> (UNPCRS), mecanismo da ONU que define prontidão de tropas para missões internacionais. Reportagens do setor citadas pela fonte indicam entrega anterior de unidade similar ao 6º Batalhão de Engenharia de Combate (São Gabriel, RS), em treinamento para certificação de paz — a nota desta sexta-feira (10/07/2026), contudo, não esclarece se se trata do mesmo lote ou de nova aquisição.</p>

<figure class="wp-block-image size-large"><img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEiEODtjg_kzAr01vVwwTo4eMs4QLgtvDWq7kmT6L1RUKT6e50eH7hpDbmWofpnzAI4nVlKPOrZacp7cYgkxNsUpVbcsSYSMZgfTNtnE8JXVOJfqhjQgMYSExOs6BTzQrUUhMaBRoxzBWCCShn4eFWi5Y8Adfly0G6PsG7dDDLhlxjArV_DNOs8HzyxjFdXa/w1200-h900/robo%20eod%20ambipar%20011%20(2).jpeg" alt="Robô de neutralização de explosivos em operação" width="1200" loading="lazy"/></figure>

<h2>Indústria nacional e ecossistema Defesa–Indústria–Academia</h2>
<p>A obtenção do equipamento foi conduzida pela Diretoria de Material de Engenharia (DME), com apoio do Sistema Defesa, Indústria e Academia. Para o mercado, o movimento reforça a tese de localização de cadeias críticas e contratos públicos direcionados a fornecedores brasileiros de tecnologia — tema relevante para empresas de capital aberto e cadeias industriais ligadas a segurança, engenharia e robótica.</p>

<h2>Leia na fonte</h2>
<p>Esta reportagem do Estrato foi elaborada em formato editorial com base em material da LRCA Defense Consulting. Para o texto original, imagens adicionais e contexto do setor de defesa, consulte a publicação na fonte abaixo.</p>
HTML;

$footer = sprintf(
	'<div class="estrato-fonte" style="margin-top:1.5rem;padding:1rem;border-left:4px solid #9AFF33;background:#111;color:#eee"><p><strong>Fonte:</strong> <a href="%1$s" target="_blank" rel="nofollow noopener">%2$s</a></p><p><em>Conteúdo editorial Estrato. Não substitui a reportagem original.</em></p></div>',
	esc_url( $source_url ),
	esc_html( $source_name )
);

$content .= $footer;

// Dedupe por external id.
$existing = get_posts(
	array(
		'post_type'      => 'post',
		'post_status'    => 'any',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_key'       => '_estrato_pipeline_id',
		'meta_value'     => $external_id,
	)
);
if ( ! empty( $existing[0] ) ) {
	WP_CLI::warning( 'Post já existe: #' . $existing[0] );
	$post_id = (int) $existing[0];
} else {
	$term = get_term_by( 'slug', sanitize_title( $category ), 'category' );
	if ( ! $term ) {
		WP_CLI::error( "Categoria ausente: $category" );
	}
	$author_id = function_exists( 'estrato_eeat_resolve_author_id' )
		? estrato_eeat_resolve_author_id( $category )
		: 1;

	$post_id = wp_insert_post(
		array(
			'post_title'    => $title,
			'post_content'  => $content,
			'post_excerpt'  => $excerpt,
			'post_status'   => 'publish',
			'post_author'   => $author_id,
			'post_category' => array( (int) $term->term_id ),
			'post_date'     => current_time( 'mysql' ),
		),
		true
	);
	if ( is_wp_error( $post_id ) ) {
		WP_CLI::error( $post_id->get_error_message() );
	}
	update_post_meta( $post_id, '_estrato_pipeline_id', sanitize_text_field( $external_id ) );
	WP_CLI::log( "Post criado #$post_id" );
}

update_post_meta( $post_id, '_estrato_source_url', esc_url_raw( $source_url ) );
update_post_meta( $post_id, '_estrato_source_name', sanitize_text_field( $source_name ) );
update_post_meta( $post_id, '_estrato_editorial_source', '1' );

// Yoast / Google News meta.
update_post_meta( $post_id, '_yoast_wpseo_title', $title . ' | Estrato' );
update_post_meta( $post_id, '_yoast_wpseo_metadesc', $excerpt );
update_post_meta( $post_id, '_yoast_wpseo_focuskw', 'Ambipar Robotics Exército' );
delete_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex' );

if ( function_exists( 'estrato_bridge_set_featured_image_from_url' ) ) {
	estrato_bridge_set_featured_image_from_url( $post_id, $image_url, true );
	update_post_meta( $post_id, '_estrato_original_image_url', esc_url_raw( $image_url ) );
}

if ( function_exists( 'estrato_content_enrich_post' ) ) {
	$enrich = estrato_content_enrich_post( $post_id );
	WP_CLI::log( 'Enrich: ' . wp_json_encode( $enrich ) );
} elseif ( function_exists( 'estrato_content_sync_yoast_index' ) && function_exists( 'estrato_content_post_word_count' ) ) {
	estrato_content_sync_yoast_index( $post_id, estrato_content_post_word_count( $post_id ) );
}

if ( function_exists( 'estrato_aeo_ping_indexnow' ) ) {
	estrato_aeo_ping_indexnow( $post_id );
	WP_CLI::log( 'IndexNow disparado' );
}

if ( function_exists( 'estrato_aeo_ping_sitemaps_cron' ) ) {
	estrato_aeo_ping_sitemaps_cron();
	WP_CLI::log( 'Ping sitemaps (news + index) tentado' );
}

$url = get_permalink( $post_id );
WP_CLI::success( "Publicado: $url" );
