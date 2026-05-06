<?php
$pdo = new PDO('mysql:host=localhost;dbname=e_parapheur', 'root', '');

// Vérifier si la table existe
$checkTable = $pdo->query("SHOW TABLES LIKE 'signature_provider_configs'");
if($checkTable->rowCount() === 0) {
    echo "ERREUR: Table signature_provider_configs n'existe pas!\n";
    exit(1);
}

echo "\n=== VÉRIFIER LES ADMINISTRATIONS ===\n";
$adRes = $pdo->query("SELECT id, name, code FROM issuing_administrations LIMIT 3");
$ads = $adRes->fetchAll(PDO::FETCH_ASSOC);
foreach($ads as $ad) {
    echo 'Emitter: ' . $ad['name'] . ' (' . $ad['code'] . ') - ID: ' . substr($ad['id'], 0, 8) . '...' . PHP_EOL;
}

echo "\n=== CRÉER CONFIG POUR DIRECTEUR (MSHPCMU) ===\n";

$mshRes = $pdo->query("SELECT id FROM issuing_administrations WHERE code = 'MSHPCMU' LIMIT 1");
$mshRow = $mshRes->fetch(PDO::FETCH_ASSOC);
if($mshRow) {
    $mshId = $mshRow['id'];
    echo "MSHPCMU ID: $mshId\n";

    // Vérifier si elle existe déjà
    $chk = $pdo->prepare("SELECT id FROM signature_provider_configs WHERE administration_id = ?");
    $chk->execute([$mshId]);
    if($chk->rowCount() === 0) {
        $stmt = $pdo->prepare("
            INSERT INTO signature_provider_configs
            (administration_id, administration_type, is_active, endpoint, sign_path, api_key, tenant_id,
             consent_page_id, consent_page_id_approval, signature_profile_id, verify_ssl, timeout_ms, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $inserted = $stmt->execute([
            $mshId,
            'emitter',
            1,
            'https://uvci.artci-sign.ci',  // Endpoint CORRECT (sans /api)
            '/v1/sign',
            'dummy_key_test',
            'test_tenant',
            'cop_test',
            'cop_test_approval',
            'sip_test',
            1,
            30000
        ]);
        echo "Config MSHPCMU créée: " . ($inserted ? "OK ✓" : "ERREUR") . "\n";
    } else {
        echo "Config MSHPCMU existe déjà.\n";
    }
} else {
    echo "MSHPCMU non trouvé.\n";
}

// Corriger les endpoints ayant un double /api
$updateRes = $pdo->exec("UPDATE signature_provider_configs SET endpoint = REPLACE(endpoint, '/api/api', '/api') WHERE is_active = 1");
echo "\n=== CORRECTION ===\n";
echo "Lignes mises à jour: " . $updateRes . PHP_EOL;

// Corriger spécifiquement les endpoints terminant par /api
$updateRes2 = $pdo->exec("UPDATE signature_provider_configs SET endpoint = REPLACE(endpoint, '/api', '') WHERE endpoint LIKE '%/api' AND endpoint NOT LIKE '%/v1%' AND is_active = 1");
echo "Endpoints nettoyés: " . $updateRes2 . PHP_EOL;

echo "\n=== APRÈS CORRECTION ===\n";
$res = $pdo->query("SELECT administration_id, endpoint, sign_path, is_active FROM signature_provider_configs WHERE is_active = 1");
$rows = $res->fetchAll(PDO::FETCH_ASSOC);
foreach($rows as $row) {
    echo 'Admin ID: ' . substr($row['administration_id'], 0, 8) . '...' . PHP_EOL;
    echo '  Endpoint: ' . $row['endpoint'] . PHP_EOL;
    echo '  Sign Path: ' . $row['sign_path'] . PHP_EOL;
    echo '  Active: ' . $row['is_active'] . PHP_EOL . PHP_EOL;
}
?>
