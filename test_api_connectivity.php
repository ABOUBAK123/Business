<?php
// Test simple de connectivité à l'endpoint
echo "\n=== TEST CONNECTIVITÉ ENDPOINT ===\n";

$endpoint = 'https://uvci.artci-sign.ci';

// Test 1: HEAD request
echo "\n1. Vérifier accessibilité du serveur (HEAD)...\n";
$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "HTTP Status: $httpCode\n";
if($httpCode >= 200 && $httpCode < 500) {
    echo "✓ Serveur accessible\n";
} else {
    echo "✗ Serveur non accessible\n";
}
curl_close($ch);

// Test 2: Test /api/users/me sans authentification (pour voir le type d'erreur)
echo "\n2. Test GET /api/users/me (sans auth)...\n";
$ch = curl_init($endpoint . '/api/users/me');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
echo "HTTP Status: $httpCode\n";
echo "Content-Type: $contentType\n";
echo "Response (first 200 chars): " . substr($response, 0, 200) . "\n";
curl_close($ch);

// Test 3: Essayer avec une clé API vide
echo "\n3. Test /api/users/me (avec Authorization header vide)...\n";
$ch = curl_init($endpoint . '/api/users/me');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer test']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "HTTP Status: $httpCode\n";
echo "Response (first 300 chars): " . substr($response, 0, 300) . "\n";
curl_close($ch);
?>
