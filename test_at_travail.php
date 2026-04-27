<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$template = \App\Models\DocumentTemplate::where('name', 'AT TRAVAIL')->first();
if ($template) {
    echo "=== Template: AT TRAVAIL ===\n";
    echo "ID: " . $template->id . "\n";
    echo "File Type: " . $template->file_type . "\n";
    echo "Storage Path: " . ($template->storage_path ?: '[EMPTY]') . "\n";
    echo "Variables: " . $template->variables->count() . "\n";
    
    // Check if file exists
    if ($template->storage_path) {
        if (str_starts_with($template->storage_path, 'images/')) {
            $fullPath = public_path($template->storage_path);
        } else {
            $fullPath = \Illuminate\Support\Facades\Storage::disk('public')->path($template->storage_path);
        }
        echo "Full Path: " . $fullPath . "\n";
        echo "File Exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "\n";
        if (file_exists($fullPath)) {
            echo "File Size: " . filesize($fullPath) . " bytes\n";
        }
    }
    
    // Check recovery button eligibility
    if ($template->storage_path && $template->variables->count() === 0 && in_array($template->file_type, ['docx','xlsx','pptx'])) {
        echo "\n✅ Recovery button SHOULD APPEAR\n";
    } else {
        echo "\n❌ Recovery button will NOT appear\n";
        echo "   - storage_path: " . ($template->storage_path ? 'OK' : 'EMPTY') . "\n";
        echo "   - var_count=0: " . ($template->variables->count() === 0 ? 'OK' : 'NO (' . $template->variables->count() . ')') . "\n";
        echo "   - file_type Office: " . (in_array($template->file_type, ['docx','xlsx','pptx']) ? 'OK' : 'NO') . "\n";
    }
} else {
    echo "Template not found!\n";
}
?>
