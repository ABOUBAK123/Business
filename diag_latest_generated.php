<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Document;

header('Content-Type: text/plain; charset=UTF-8');

$doc = Document::query()
    ->where('description', 'like', 'Généré depuis :%')
    ->orderByDesc('created_at')
    ->first();

if (!$doc) {
    echo "Aucun document généré depuis template trouvé.\n";
    exit;
}

$rel = ltrim(str_replace('/storage/', '', (string) $doc->file_path), '/');
$abs = storage_path('app/public/' . $rel);

printf("id=%s\n", (string) $doc->id);
printf("title=%s\n", (string) $doc->title);
printf("file_path=%s\n", (string) $doc->file_path);
printf("mime_type=%s\n", (string) $doc->mime_type);
printf("file_size_db=%d\n", (int) $doc->file_size);
printf("created_at=%s\n", (string) $doc->created_at);
printf("exists=%s\n", file_exists($abs) ? 'YES' : 'NO');
printf("abs=%s\n", $abs);

if (!file_exists($abs)) {
    exit;
}

printf("file_size_fs=%d\n", (int) filesize($abs));
$ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
printf("ext=%s\n", $ext);

if ($ext === 'pdf') {
    $head = @file_get_contents($abs, false, null, 0, 12);
    printf("pdf_header=%s\n", is_string($head) ? str_replace("\n", ' ', $head) : 'NULL');
}

if (in_array($ext, ['docx', 'xlsx', 'pptx'], true) && class_exists('ZipArchive')) {
    $zip = new ZipArchive();
    if ($zip->open($abs) === true) {
        $xmlCount = 0;
        $textLenTotal = 0;
        $placeholders = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (!preg_match('/\\.xml$/i', $name)) {
                continue;
            }

            $xml = $zip->getFromIndex($i);
            if (!is_string($xml)) {
                continue;
            }

            $xmlCount++;
            $text = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $textLen = mb_strlen($text, 'UTF-8');
            $textLenTotal += $textLen;

            preg_match_all('/\{\{[^}]{1,120}\}\}|\[[^\[\]]{1,120}\]/u', $text, $m);
            $placeholders += count($m[0]);

            if (preg_match('#word/(document|header|footer)#i', $name)) {
                printf("xml=%s text_len=%d placeholders=%d\n", $name, $textLen, count($m[0]));

                if ($name === 'word/document.xml') {
                    preg_match_all('/<w:t[^>]*>(.*?)<\/w:t>/s', $xml, $tm);
                    $wtCount = count($tm[0]);
                    $nonEmptyWt = 0;
                    foreach ($tm[1] as $t) {
                        if (trim(html_entity_decode((string) $t, ENT_QUOTES | ENT_HTML5, 'UTF-8')) !== '') {
                            $nonEmptyWt++;
                        }
                    }

                    preg_match_all('/<w:p[ >].*?<\/w:p>/s', $xml, $pm);
                    $wpCount = count($pm[0]);

                    printf("document_xml_wt_count=%d\n", $wtCount);
                    printf("document_xml_wt_non_empty=%d\n", $nonEmptyWt);
                    printf("document_xml_wp_count=%d\n", $wpCount);

                    $snippet = substr($xml, 0, 1000);
                    $snippet = preg_replace('/\s+/', ' ', (string) $snippet);
                    printf("document_xml_head=%s\n", $snippet);
                }
            }
        }

        $zip->close();

        printf("office_xml_count=%d\n", $xmlCount);
        printf("office_text_len_total=%d\n", $textLenTotal);
        printf("office_placeholder_count=%d\n", $placeholders);
        printf("looks_blank=%s\n", $textLenTotal < 300 ? 'YES' : 'NO');
    }
}
