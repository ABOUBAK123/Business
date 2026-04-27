<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$template = \App\Models\DocumentTemplate::where('name', 'like', '%AT%')
    ->orWhere('name', 'like', '%TRAVAIL%')
    ->orderBy('created_at', 'desc')
    ->first();

if ($template) {
    echo "=== Template Found ===\n";
    echo "ID: " . $template->id . "\n";
    echo "Name: " . $template->name . "\n";
    echo "File Name: " . $template->file_name . "\n";
    echo "File Type: " . $template->file_type . "\n";
    echo "Storage Path: " . ($template->storage_path ?: 'EMPTY') . "\n";
    echo "Created: " . $template->created_at . "\n";
    
    $vars = $template->variables;
    echo "\n=== Variables (" . $vars->count() . ") ===\n";
    foreach ($vars as $var) {
        echo "- " . $var->variable_name . "\n";
    }
    
    if ($template->storage_path) {
        $fullPath = storage_path($template->storage_path);
        echo "\n=== File Check ===\n";
        echo "Full Path: " . $fullPath . "\n";
        echo "File Exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "\n";
        if (file_exists($fullPath)) {
            echo "File Size: " . filesize($fullPath) . " bytes\n";
            echo "Modified: " . date('Y-m-d H:i:s', filemtime($fullPath)) . "\n";
        }
    }
} else {
    echo "No template found matching 'AT' or 'TRAVAIL'\n";
}
?>
