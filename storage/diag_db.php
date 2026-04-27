<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=e_parapheur;charset=utf8', 'root', '');
$rows = $pdo->query('SELECT id, name, storage_path, file_type, variables, created_at FROM document_templates ORDER BY id DESC LIMIT 8')->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo '--- ID:'.$r['id'].' | '.$r['name']."\n";
    echo '    storage_path: '.($r['storage_path'] ?? 'NULL')."\n";
    echo '    file_type: '.($r['file_type'] ?? 'NULL')."\n";
    echo '    variables: '.($r['variables'] ? substr($r['variables'],0,120) : 'NULL')."\n";
    if ($r['storage_path']) {
        if (str_starts_with($r['storage_path'], 'images/')) {
            $abs = realpath(__DIR__.'/..') . '/public/' . $r['storage_path'];
        } else {
            $abs = realpath(__DIR__.'/..') . '/storage/app/public/' . $r['storage_path'];
        }
        echo '    fichier: '.(file_exists($abs) ? 'OUI '.filesize($abs).'o' : 'NON')." path=$abs\n";
    }
    echo "\n";
}
