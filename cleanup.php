<?php
$files_to_delete = [
    'add_missing_columns.php',
    'add_qr_column.php',
    'check_qr_db.php',
    'check_qr_token.php',
    'check_schema.php',
    'check_tokens.php',
    'debug_path.php',
    'test_qr_download.php',
    'view_logs.php',
];

echo "Suppression des fichiers de diagnostic...\n\n";

foreach ($files_to_delete as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        if (unlink($path)) {
            echo "✓ $file supprimé\n";
        } else {
            echo "✗ Erreur lors de la suppression de $file\n";
        }
    } else {
        echo "- $file n'existe pas\n";
    }
}

echo "\nFait!\n";
?>
