<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$recs = App\Models\RecipientAdministration::whereNotNull('logo')->get(['id','name','code','logo']);
foreach ($recs as $r) {
    echo 'RECIPIENT id=' . $r->id . ' code=' . $r->code . ' logo=[' . $r->logo . "]\n";
    // Vérifier si le fichier existe physiquement
    $logo = $r->logo;
    $raw = ltrim($logo, '/');
    if (str_starts_with($raw, 'storage/')) {
        $rel = ltrim(substr($raw, strlen('storage/')), '/');
        $exists = \Illuminate\Support\Facades\Storage::disk('public')->exists($rel);
        echo "  -> storage/public path: $rel | exists=$exists\n";
    } elseif (str_starts_with($raw, 'images/')) {
        $exists = file_exists(public_path($raw));
        echo "  -> public/$raw | exists=$exists\n";
    } else {
        echo "  -> format non reconnu\n";
        // Tenter storage disk
        $rel1 = $raw;
        $exists1 = \Illuminate\Support\Facades\Storage::disk('public')->exists($rel1);
        echo "  -> try storage disk '$rel1': $exists1\n";
    }
}

echo "\n";
$iss = App\Models\IssuingAdministration::whereNotNull('logo')->get(['id','name','code','logo']);
foreach ($iss as $i) {
    echo 'ISSUING id=' . $i->id . ' code=' . $i->code . ' logo=[' . $i->logo . "]\n";
    $logo = $i->logo;
    $raw = ltrim($logo, '/');
    if (str_starts_with($raw, 'storage/')) {
        $rel = ltrim(substr($raw, strlen('storage/')), '/');
        $exists = \Illuminate\Support\Facades\Storage::disk('public')->exists($rel);
        echo "  -> storage/public path: $rel | exists=$exists\n";
    } elseif (str_starts_with($raw, 'images/')) {
        $exists = file_exists(public_path($raw));
        echo "  -> public/$raw | exists=$exists\n";
    } else {
        echo "  -> format non reconnu\n";
        $rel1 = $raw;
        $exists1 = \Illuminate\Support\Facades\Storage::disk('public')->exists($rel1);
        echo "  -> try storage disk '$rel1': $exists1\n";
    }
}
