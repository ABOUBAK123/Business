<?php

namespace App\Services;

class TemplateOfficeTextExtractor
{
    public function extract(string $absFilePath): string
    {
        if (!class_exists('ZipArchive') || !file_exists($absFilePath)) {
            return '';
        }

        $zip = new \ZipArchive();
        if ($zip->open($absFilePath) !== true) {
            return '';
        }

        $chunks = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (!preg_match('/\.xml$/i', $name)) {
                continue;
            }
            if (preg_match('#\[Content_Types\]|_rels/#', $name)) {
                continue;
            }

            $xml = $zip->getFromIndex($i);
            if ($xml === false) {
                continue;
            }

            $isWordContent = preg_match('#word/(document|header|footer|endnote|footnote)#i', $name) === 1;
            $normalizedXml = $isWordContent ? $this->defragmentRuns((string) $xml) : (string) $xml;
            $text = html_entity_decode(strip_tags($normalizedXml), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $text = preg_replace('/\s+/u', ' ', $text ?? '');
            $text = trim((string) $text);

            if ($text !== '') {
                $chunks[] = $text;
            }
        }

        $zip->close();

        $content = trim(implode("\n\n", array_unique($chunks)));

        if (function_exists('mb_substr')) {
            return mb_substr($content, 0, 60000);
        }

        return substr($content, 0, 60000);
    }

    private function defragmentRuns(string $xml): string
    {
        return preg_replace_callback(
            '/<w:p[ >].*?<\/w:p>/s',
            function (array $match) {
                $para = $match[0];

                if (preg_match(
                    '/<(w:(drawing|pict|object|tbl|hyperlink|bookmarkStart|bookmarkEnd|fldSimple|instrText|fldChar|sdt|smartTag|tab|br|cr)|mc:AlternateContent)\b/i',
                    $para
                )) {
                    return $para;
                }

                $skeleton = $para;
                $skeleton = preg_replace('/^<w:p[^>]*>|<\/w:p>$/s', '', $skeleton);
                $skeleton = preg_replace('/<w:pPr>.*?<\/w:pPr>/s', '', $skeleton);
                $skeleton = preg_replace('/<w:r[ >].*?<\/w:r>/s', '', $skeleton);
                $skeleton = preg_replace('/<w:proofErr[^>]*\/>/s', '', $skeleton);
                if ($skeleton === null || trim(strip_tags($skeleton)) !== '') {
                    return $para;
                }

                preg_match_all('/<w:t[^>]*>(.*?)<\/w:t>/s', $para, $texts);
                $fullText = implode('', $texts[1]);

                if (count($texts[0]) < 2) {
                    return $para;
                }

                if (!preg_match('/\[[^\[\]]+\]/', $fullText) && strpos($fullText, '{{') === false) {
                    return $para;
                }

                if (!preg_match('/(\[|\{\{)[\s\S]*?<\/w:t>[\s\S]*?<w:t[^>]*>[\s\S]*?(\]|\}\})/s', $para)) {
                    return $para;
                }

                $firstRpr = '';
                if (preg_match('/<w:r[ >].*?(<w:rPr>.*?<\/w:rPr>)/s', $para, $rprMatch)) {
                    $firstRpr = $rprMatch[1];
                }

                $pPr = '';
                if (preg_match('/<w:pPr>.*?<\/w:pPr>/s', $para, $pPrMatch)) {
                    $pPr = $pPrMatch[0];
                }

                preg_match('/^<w:p[^>]*>/', $para, $openTag);
                $open = $openTag[0] ?? '<w:p>';

                $firstTextAttrs = ' xml:space="preserve"';
                if (preg_match('/<w:t([^>]*)>/', $para, $tAttrMatch)) {
                    $attrs = trim((string) ($tAttrMatch[1] ?? ''));
                    $firstTextAttrs = $attrs !== '' ? ' ' . $attrs : '';
                }

                return $open
                    . $pPr
                    . '<w:r>'
                    . $firstRpr
                    . '<w:t' . $firstTextAttrs . '>' . $fullText . '</w:t>'
                    . '</w:r>'
                    . '</w:p>';
            },
            $xml
        ) ?? $xml;
    }
}
