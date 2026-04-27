<?php
/**
 * Script de débogage pour tester le système de traduction
 * Accès: http://localhost/debug_translation.php
 */

session_start();

// Afficher la session actuelle
echo "=== SESSION DEBUG ===\n\n";
echo "Session ID: " . session_id() . "\n";
echo "Session content: " . json_encode($_SESSION, JSON_PRETTY_PRINT) . "\n\n";

// Vérifier le fichier de configuration
echo "=== CONFIG DEBUG ===\n\n";
$configPath = __DIR__ . '/config/app.php';
if (file_exists($configPath)) {
    $config = require $configPath;
    echo "Config locale: " . ($config['locale'] ?? 'NOT SET') . "\n";
} else {
    echo "Config not found at: $configPath\n";
}

// Vérifier les fichiers de langue
echo "\n=== LANGUAGE FILES DEBUG ===\n\n";
$langPath = __DIR__ . '/resources/lang';
if (is_dir($langPath)) {
    $locales = scandir($langPath);
    foreach ($locales as $locale) {
        if ($locale === '.' || $locale === '..') continue;
        $localePath = $langPath . '/' . $locale;
        if (is_dir($localePath)) {
            $files = scandir($localePath);
            echo "Locale: $locale\n";
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                    echo "  - $file\n";
                }
            }
        }
    }
} else {
    echo "Lang path not found at: $langPath\n";
}

// Vérifier le middleware
echo "\n=== MIDDLEWARE DEBUG ===\n\n";
$middlewarePath = __DIR__ . '/app/Http/Middleware/SetLocale.php';
if (file_exists($middlewarePath)) {
    echo "✓ SetLocale middleware found\n";
    $content = file_get_contents($middlewarePath);
    if (strpos($content, 'session(\'locale\')') !== false) {
        echo "✓ Middleware checks session('locale')\n";
    }
    if (strpos($content, 'app()->setLocale') !== false) {
        echo "✓ Middleware calls app()->setLocale()\n";
    }
} else {
    echo "✗ SetLocale middleware NOT found at: $middlewarePath\n";
}

// Vérifier le bootstrap
echo "\n=== BOOTSTRAP DEBUG ===\n\n";
$bootstrapPath = __DIR__ . '/bootstrap/app.php';
if (file_exists($bootstrapPath)) {
    $content = file_get_contents($bootstrapPath);
    if (strpos($content, 'SetLocale') !== false) {
        echo "✓ SetLocale is registered in bootstrap/app.php\n";
    } else {
        echo "✗ SetLocale is NOT registered in bootstrap/app.php\n";
    }
}

// Vérifier le ProfileController
echo "\n=== PROFILE CONTROLLER DEBUG ===\n\n";
$controllerPath = __DIR__ . '/app/Http/Controllers/ProfileController.php';
if (file_exists($controllerPath)) {
    $content = file_get_contents($controllerPath);
    if (strpos($content, 'updateLanguage') !== false) {
        echo "✓ updateLanguage method found\n";
        if (strpos($content, 'session([\'locale\'') !== false) {
            echo "✓ Method saves to session\n";
        }
        if (strpos($content, 'in:fr,en,es,pt,ar') !== false) {
            echo "✓ All 5 locales are validated (fr, en, es, pt, ar)\n";
        }
    }
}

echo "\n=== STATUS ===\n";
echo "Setup appears to be: ";
if (file_exists($bootstrapPath) && file_exists($middlewarePath) && file_exists($controllerPath)) {
    echo "✓ COMPLETE\n";
    echo "If locale is not changing, check browser console and server logs for errors.\n";
} else {
    echo "✗ INCOMPLETE\n";
}
?>
