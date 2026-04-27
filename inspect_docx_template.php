<?php
$path = __DIR__ . '/public/images/templates/tpl_019dc927-0835-72b8-8e0a-9645ab6da5ef_1777197801.docx';
if (!file_exists($path)) { echo "not found\n"; exit(1); }

$z = new ZipArchive();
if ($z->open($path) !== true) { echo "zip fail\n"; exit(1); }
$xml = $z->getFromName('word/document.xml');
$z->close();
if ($xml === false) { echo "no document.xml\n"; exit(1); }

echo 'has_hint=' . (strpos($xml, 'NOUVEAU TEMPLATE') !== false ? 'YES' : 'NO') . PHP_EOL;
preg_match_all('/<w:t[^>]*>(.*?)<\/w:t>/s', $xml, $m);
$texts = array_map(function ($t) {
    return trim(html_entity_decode(strip_tags($t), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}, $m[1]);
$texts = array_values(array_filter($texts, fn($v) => $v !== ''));

echo 'text_nodes=' . count($texts) . PHP_EOL;
for ($i = 0; $i < min(15, count($texts)); $i++) {
    echo ($i + 1) . ': ' . $texts[$i] . PHP_EOL;
}
