<?php
$path = $argv[1] ?? '';
if ($path === '' || !file_exists($path)) { echo "missing\n"; exit(1); }
$z = new ZipArchive();
if ($z->open($path) !== true) { echo "zip fail\n"; exit(1); }
$xml = $z->getFromName('word/document.xml');
$z->close();
if ($xml === false) { echo "no xml\n"; exit(1); }
echo 'has_hint=' . (strpos($xml, 'NOUVEAU TEMPLATE') !== false ? 'YES' : 'NO') . PHP_EOL;
preg_match_all('/<w:t[^>]*>(.*?)<\/w:t>/s', $xml, $m);
$texts = array_values(array_filter(array_map(function($t){
  return trim(html_entity_decode(strip_tags($t), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}, $m[1]), fn($v)=>$v!==''));
echo 'text_nodes=' . count($texts) . PHP_EOL;
for($i=0;$i<min(8,count($texts));$i++) echo ($i+1).': '.$texts[$i].PHP_EOL;
