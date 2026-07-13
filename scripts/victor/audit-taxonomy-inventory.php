<?php
$root = '/workspace';
$portals = glob($root . '/portals/estrato-*-taxonomy.php');
sort($portals);
$summary = [];
foreach ($portals as $path) {
    $t = include $path;
    $portal = $t['portal_id'] ?? basename($path);
    $row = ['portal' => $portal, 'file' => basename($path), 'editorias' => [], 'total_subs' => 0, 'total_feeds' => 0];
    foreach (($t['categories'] ?? []) as $slug => $cat) {
        $ed = ['slug' => $slug, 'name' => $cat['name'] ?? $slug, 'subs' => []];
        foreach (($cat['subcategories'] ?? []) as $ss => $sub) {
            $fc = count($sub['feeds'] ?? []);
            $kc = count($sub['keywords']['include'] ?? []);
            $row['total_subs']++;
            $row['total_feeds'] += $fc;
            $ed['subs'][] = ['slug' => $ss, 'name' => $sub['name'] ?? $ss, 'feeds' => $fc, 'keywords' => $kc];
        }
        $row['total_feeds'] += count($cat['feeds'] ?? []);
        $row['editorias'][] = $ed;
    }
    $summary[] = $row;
}
echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
