<?php
require 'vendor/autoload.php';
$c = new App\Http\Controllers\SharedTemplateController();
$r = new ReflectionClass($c);
$m = $r->getMethod('defragmentRuns');
$m->setAccessible(true);
$xml = '<w:document><w:body><w:p><w:r><w:t>{{ NOM</w:t></w:r><w:r><w:tab/></w:r><w:r><w:t xml:space="preserve"> DU DEMANDEUR}}</w:t></w:r></w:p></w:body></w:document>';
$out = $m->invoke($c, $xml);
echo $out, PHP_EOL;
