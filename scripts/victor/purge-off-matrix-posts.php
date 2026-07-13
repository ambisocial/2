<?php
/**
 * Purga posts publicados cuja fonte RSS (`_estrato_rss_source_url`) está fora
 * da `estrato_rss_import_matrix` do portal atual.
 *
 * Uso:
 *   wp --path=/var/www/estrato/wp eval-file scripts/victor/purge-off-matrix-posts.php --url=https://culture.estrato.cc
 *   wp ... eval-file scripts/victor/purge-off-matrix-posts.php --url=... -- --dry-run
 *
 * Bug de origem: auditoria visual 2026-07-13 (B2). Um snapshot antigo do RSS
 * pipeline importou preset `brasil-financeiro` em cada satélite, criando 37
 * posts órfãos que ocupam ~70-82% do conteúdo publicado em cada portal.
 *
 * Estratégia: mover para `trash` (não deletar) para permitir undo em 30 dias.
 */

$dry_run = ( ! empty( getenv( 'ESTRATO_PURGE_DRY_RUN' ) ) && '0' !== getenv( 'ESTRATO_PURGE_DRY_RUN' ) )
	|| ( ! empty( $GLOBALS['argv'] ) && in_array( '--dry-run', $GLOBALS['argv'], true ) );

$matrix_raw = get_option( 'estrato_rss_import_matrix' );
if ( ! is_array( $matrix_raw ) ) {
	echo "SKIP: sem estrato_rss_import_matrix neste portal\n";
	return;
}

$allowed_hosts = array();
foreach ( $matrix_raw as $entry ) {
	if ( ! is_array( $entry ) ) {
		continue;
	}
	$node = $entry['node'] ?? null;
	if ( is_string( $node ) ) {
		$decoded = json_decode( $node, true );
		if ( is_array( $decoded ) ) {
			$node = $decoded;
		}
	}
	$feeds = ( is_array( $node ) && ! empty( $node['feeds'] ) ) ? $node['feeds'] : array();
	if ( ! is_array( $feeds ) && ! empty( $entry['feeds'] ) && is_array( $entry['feeds'] ) ) {
		$feeds = $entry['feeds'];
	}
	foreach ( (array) $feeds as $feed ) {
		$url = is_string( $feed ) ? $feed : ( isset( $feed['url'] ) ? (string) $feed['url'] : '' );
		if ( ! $url ) {
			continue;
		}
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		if ( $host ) {
			$allowed_hosts[ $host ] = true;
		}
	}
}

if ( empty( $allowed_hosts ) ) {
	echo "SKIP: matriz sem feeds\n";
	return;
}

echo 'Portal: ' . home_url() . "\n";
echo 'Hosts permitidos: ' . count( $allowed_hosts ) . "\n";

$q = new WP_Query(
	array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	)
);

$off_matrix    = array();
$total_checked = 0;
foreach ( $q->posts as $post_id ) {
	$src = get_post_meta( $post_id, '_estrato_rss_source_url', true );
	if ( ! $src ) {
		continue;
	}
	++$total_checked;
	$host = strtolower( (string) wp_parse_url( $src, PHP_URL_HOST ) );
	if ( ! $host ) {
		continue;
	}
	if ( ! isset( $allowed_hosts[ $host ] ) ) {
		$off_matrix[] = array(
			'id'    => $post_id,
			'host'  => $host,
			'title' => get_the_title( $post_id ),
		);
	}
}

echo 'Posts com _estrato_rss_source_url: ' . $total_checked . "\n";
echo 'Off-matriz detectados: ' . count( $off_matrix ) . "\n";

$mode = $dry_run ? 'DRY-RUN' : 'TRASH';
echo "Modo: $mode\n---\n";
$moved = 0;
foreach ( $off_matrix as $row ) {
	echo sprintf( "%-10s ID=%d host=%s title=%s\n", $mode, $row['id'], $row['host'], substr( $row['title'], 0, 60 ) );
	if ( ! $dry_run ) {
		$res = wp_trash_post( $row['id'] );
		if ( $res ) {
			update_post_meta( $row['id'], '_estrato_off_matrix_purged_at', current_time( 'mysql', true ) );
			++$moved;
		}
	}
}

echo "---\nMoved: $moved / " . count( $off_matrix ) . "\n";
