<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestLocale extends Command
{
    protected $signature = 'test:locale {locale?}';
    protected $description = 'Test the locale system';

    public function handle()
    {
        $this->line('🧪 Testing Locale System');
        $this->line('=======================');
        $this->newLine();

        // Test 1: Check config
        $this->info('Test 1: Configuration');
        $this->line('  Config locale: ' . config('app.locale'));

        // Test 2: Check middleware
        $this->info('Test 2: Middleware');
        $middlewarePath = app_path('Http/Middleware/SetLocale.php');
        if (file_exists($middlewarePath)) {
            $this->line('  ✓ SetLocale.php exists');
        } else {
            $this->error('  ✗ SetLocale.php NOT found');
        }

        // Test 3: Check language files
        $this->info('Test 3: Language Files');
        $locales = ['fr', 'en', 'es', 'pt', 'ar'];
        foreach ($locales as $locale) {
            $path = resource_path("lang/$locale");
            if (is_dir($path)) {
                $files = scandir($path);
                $count = count(array_filter($files, fn($f) => pathinfo($f, PATHINFO_EXTENSION) === 'php'));
                $this->line("  ✓ $locale: $count translation files");
            } else {
                $this->error("  ✗ $locale: directory NOT found");
            }
        }

        // Test 4: Check if locale can be set
        $this->info('Test 4: Setting Locale');
        $testLocale = $this->argument('locale') ?? 'en';

        try {
            app()->setLocale($testLocale);
            $currentLocale = app()->getLocale();
            if ($currentLocale === $testLocale) {
                $this->line("  ✓ Successfully set locale to: $testLocale");
            } else {
                $this->error("  ✗ Failed to set locale. Current: $currentLocale");
            }
        } catch (\Exception $e) {
            $this->error('  ✗ Error: ' . $e->getMessage());
        }

        // Test 5: Try a translation
        $this->info('Test 5: Translation Test');
        $testKey = 'messages.welcome';
        try {
            $translation = __($testKey);
            $this->line("  ✓ $testKey = '$translation'");
        } catch (\Exception $e) {
            $this->error("  ✗ Error translating: " . $e->getMessage());
        }

        $this->newLine();
        $this->info('✨ Test complete!');
    }
}
