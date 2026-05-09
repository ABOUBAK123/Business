<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('template_variables', function (Blueprint $table) {
            $table->string('key', 500)->change();
        });

        $this->resyncMissingTemplateVariables();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Evite un echec SQL lors du retour a 150 caracteres.
        $rows = DB::table('template_variables')
            ->select('id', 'key')
            ->get();

        foreach ($rows as $row) {
            $key = (string) ($row->key ?? '');
            if (strlen($key) <= 150) {
                continue;
            }

            $hash = substr(sha1($key), 0, 10);
            $base = substr($key, 0, 139);
            $shortKey = rtrim($base, '_') . '_' . $hash;

            DB::table('template_variables')
                ->where('id', $row->id)
                ->update(['key' => $shortKey]);
        }

        Schema::table('template_variables', function (Blueprint $table) {
            $table->string('key', 150)->change();
        });
    }

    private function resyncMissingTemplateVariables(): void
    {
        $templates = DB::table('document_templates')
            ->select('id', 'storage_path')
            ->whereNotNull('storage_path')
            ->get();

        $now = now();

        foreach ($templates as $template) {
            $storagePath = trim((string) ($template->storage_path ?? ''));
            if ($storagePath === '') {
                continue;
            }

            $ext = strtolower((string) pathinfo($storagePath, PATHINFO_EXTENSION));
            if (!in_array($ext, ['docx', 'xlsx', 'pptx'], true)) {
                continue;
            }

            $absPath = $this->resolveTemplateAbsolutePath($storagePath);
            if ($absPath === null || !file_exists($absPath)) {
                continue;
            }

            $detected = $this->extractVarsFromOfficeFile($absPath);
            if (empty($detected)) {
                continue;
            }

            $existingKeys = DB::table('template_variables')
                ->where('template_id', $template->id)
                ->pluck('key')
                ->all();
            $existingSet = array_fill_keys(array_map('strval', $existingKeys), true);

            $toInsert = [];
            foreach ($detected as $key => $label) {
                if (isset($existingSet[$key])) {
                    continue;
                }

                $toInsert[] = [
                    'id' => (string) Str::uuid(),
                    'template_id' => (string) $template->id,
                    'key' => $key,
                    'label' => $label,
                    'field_type' => 'text',
                    'required' => false,
                    'placeholder' => '',
                    'default_value' => '',
                    'options' => json_encode([]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (!empty($toInsert)) {
                DB::table('template_variables')->insert($toInsert);
            }
        }
    }

    private function resolveTemplateAbsolutePath(string $storagePath): ?string
    {
        $path = str_replace('\\', '/', trim($storagePath));
        if ($path === '') {
            return null;
        }

        $path = ltrim($path, '/');

        if (str_starts_with($path, 'images/')) {
            return public_path($path);
        }

        if (str_starts_with($path, 'storage/')) {
            return storage_path('app/public/' . ltrim(substr($path, 8), '/'));
        }

        if (str_starts_with($path, 'templates/')) {
            return storage_path('app/public/' . $path);
        }

        $storageCandidate = storage_path('app/public/' . $path);
        if (file_exists($storageCandidate)) {
            return $storageCandidate;
        }

        $publicCandidate = public_path($path);
        if (file_exists($publicCandidate)) {
            return $publicCandidate;
        }

        return null;
    }

    private function extractVarsFromOfficeFile(string $absFilePath): array
    {
        if (!class_exists('ZipArchive') || !file_exists($absFilePath)) {
            return [];
        }

        $zip = new \ZipArchive();
        if ($zip->open($absFilePath) !== true) {
            return [];
        }

        $found = []; // key => label

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (!is_string($name) || !preg_match('/\\.xml$/i', $name)) {
                continue;
            }
            if (preg_match('#\\[Content_Types\\]|_rels/#', $name)) {
                continue;
            }

            $xml = $zip->getFromIndex($i);
            if (!is_string($xml) || $xml === '') {
                continue;
            }

            $isWordContent = preg_match('#word/(document|header|footer|endnote|footnote)#i', $name) === 1;
            $normalizedXml = $isWordContent ? $this->defragmentRuns($xml) : $xml;
            $text = html_entity_decode(strip_tags($normalizedXml), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            preg_match_all('/(?:\\{\\s*\\{)\\s*([^{}]+?)\\s*(?:\\}\\s*\\})/u', $text, $m1);
            preg_match_all('/\\[([^\\[\\]]+?)\\]/u', $text, $m2);

            foreach (array_merge($m1[1], $m2[1]) as $original) {
                $original = trim((string) $original);
                if ($original === '') {
                    continue;
                }

                $key = $this->makeVariableSlug($original);
                if ($key !== '' && !isset($found[$key])) {
                    $label = function_exists('mb_substr') ? mb_substr($original, 0, 255) : substr($original, 0, 255);
                    $found[$key] = $label;
                }
            }
        }

        $zip->close();
        return $found;
    }

    private function defragmentRuns(string $xml): string
    {
        return preg_replace_callback(
            '/<w:p[ >].*?<\\/w:p>/s',
            function (array $match): string {
                $para = $match[0];

                preg_match_all('/<w:t[^>]*>(.*?)<\\/w:t>/s', $para, $texts);
                if (empty($texts[1])) {
                    return $para;
                }

                $fullText = implode('', $texts[1]);
                if (!preg_match('/\\[[^\\[\\]]+\\]/', $fullText) && strpos($fullText, '{{') === false) {
                    return $para;
                }

                $firstRpr = '';
                if (preg_match('/<w:r[ >].*?(<w:rPr>.*?<\\/w:rPr>)/s', $para, $rprMatch) === 1) {
                    $firstRpr = $rprMatch[1];
                }

                $pPr = '';
                if (preg_match('/<w:pPr>.*?<\\/w:pPr>/s', $para, $pPrMatch) === 1) {
                    $pPr = $pPrMatch[0];
                }

                $newRun = '<w:r>' . $firstRpr . '<w:t xml:space="preserve">' . $fullText . '</w:t></w:r>';
                return '<w:p>' . $pPr . $newRun . '</w:p>';
            },
            $xml
        ) ?? $xml;
    }

    private function makeVariableSlug(string $orig): string
    {
        $source = $orig;
        if (function_exists('iconv')) {
            $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $orig);
            if ($ascii !== false && $ascii !== '') {
                $source = $ascii;
            }
        }

        $slug = strtolower($source);
        $slug = str_replace("'", '_', $slug);
        $slug = preg_replace('/[^a-z0-9]+/', '_', $slug);
        $slug = trim((string) $slug, '_');
        if ($slug === '') {
            $slug = 'var';
        }

        $max = 500;
        if (strlen($slug) > $max) {
            $hash = substr(sha1($orig), 0, 10);
            $base = substr($slug, 0, $max - 11);
            $slug = rtrim($base, '_') . '_' . $hash;
        }

        return $slug;
    }
};
