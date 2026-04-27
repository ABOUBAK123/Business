<?php
$file = __DIR__ . '/resources/views/admin/index.blade.php';
$content = file_get_contents($file);

$marker = '{{-- ── Colonne droite : variables + partage ── --}}';
$pos1 = strpos($content, $marker);
$pos2 = strpos($content, $marker, $pos1 + 1);

if ($pos2 === false) {
    echo "No duplicate found.\n";
    exit(0);
}

$line1 = substr_count(substr($content, 0, $pos1), "\n") + 1;
$line2 = substr_count(substr($content, 0, $pos2), "\n") + 1;
echo "First at line $line1, second at line $line2\n";

// Find end of duplicate section: </section>\n</div>\n\n{{-- ╔╔╔ MODAL PARTAGE
// Search for "@push('scripts')" after the second marker
$endMarker = "@push('scripts')";
$endPos = strpos($content, $endMarker, $pos2);
echo "End marker at byte: $endPos\n";
$endLine = substr_count(substr($content, 0, $endPos), "\n") + 1;
echo "End marker at line: $endLine\n";

// Extract the duplicate section to see what we're removing
$duplicate = substr($content, $pos2, $endPos - $pos2);
echo "--- DUPLICATE SECTION STARTS ---\n";
echo substr($duplicate, 0, 200) . "\n...\n";
echo substr($duplicate, -200) . "\n";
echo "--- DUPLICATE SECTION ENDS ---\n";

// Remove the duplicate section (from pos2 up to but NOT including @push('scripts'))
$newContent = substr($content, 0, $pos2) . substr($content, $endPos);
file_put_contents($file, $newContent);
echo "Done. Removed " . strlen($duplicate) . " bytes.\n";
