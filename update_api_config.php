<?php
$pdo = new PDO('mysql:host=localhost;dbname=e_parapheur', 'root', '');

$configs = [
    // MEMFPMA
    '019d9daf-61ed-70d4-8bfd-d65f58ec21c2' => [
        'endpoint' => 'https://uvci.artci-sign.ci',  // SANS /api
        'api_key' => 'act_38Xcy1gjrQ9jTUfozSvpWYMi.3aq7VsWt8GS5ySwBX3Zn4yxF4fS1B1ZACDfE2jzcZzFwixrjokeu6TzrDfq6ivJr',
        'consent_page_id' => 'cop_MFPnJ1A1qj9saiPvbA8stjB2',
        'signature_profile_id' => 'sip_GqGWkYmLrqvSddX6NsxVbEmx',
        'tenant_id' => 'ten_memfpma',  // À confirmer après test
    ],
    // MSHPCMU (DIRECTEUR - a.kamagate)
    '019dbb72-72a9-7184-a134-8f14697fdcc3' => [
        'endpoint' => 'https://uvci.artci-sign.ci',  // SANS /api
        'api_key' => 'act_38Xcy1gjrQ9jTUfozSvpWYMi.3aq7VsWt8GS5ySwBX3Zn4yxF4fS1B1ZACDfE2jzcZzFwixrjokeu6TzrDfq6ivJr',
        'consent_page_id' => 'cop_MFPnJ1A1qj9saiPvbA8stjB2',
        'signature_profile_id' => 'sip_GqGWkYmLrqvSddX6NsxVbEmx',
        'tenant_id' => 'ten_mshpcmu',  // À confirmer après test
    ]
];

echo "\n=== MISE À JOUR CONFIGURATIONS API SIGNATURE ===\n";

foreach($configs as $adminId => $data) {
    echo "\nAdmin ID: " . substr($adminId, 0, 8) . "...\n";

    $stmt = $pdo->prepare("
        UPDATE signature_provider_configs
        SET endpoint = ?,
            api_key = ?,
            consent_page_id = ?,
            signature_profile_id = ?,
            updated_at = NOW()
        WHERE administration_id = ?
    ");

    $updated = $stmt->execute([
        $data['endpoint'],
        $data['api_key'],
        $data['consent_page_id'],
        $data['signature_profile_id'],
        $adminId
    ]);

    if($stmt->rowCount() > 0) {
        echo "✓ Configuration mise à jour\n";
        echo "  Endpoint: " . $data['endpoint'] . "\n";
        echo "  API Key: " . substr($data['api_key'], 0, 20) . "...\n";
        echo "  Consent Page: " . $data['consent_page_id'] . "\n";
        echo "  Signature Profile: " . $data['signature_profile_id'] . "\n";
    } else {
        echo "✗ Aucune ligne mise à jour pour cet admin\n";
    }
}

echo "\n=== CONFIGURATIONS ACTUELLES ===\n";
$res = $pdo->query("SELECT administration_id, endpoint, api_key, consent_page_id, signature_profile_id FROM signature_provider_configs WHERE is_active = 1");
$rows = $res->fetchAll(PDO::FETCH_ASSOC);

foreach($rows as $row) {
    echo "\nAdmin: " . substr($row['administration_id'], 0, 8) . "...\n";
    echo "  Endpoint: " . $row['endpoint'] . "\n";
    echo "  API Key: " . substr($row['api_key'], 0, 30) . "...\n";
    echo "  Consent Page: " . $row['consent_page_id'] . "\n";
    echo "  Signature Profile: " . $row['signature_profile_id'] . "\n";
}

echo "\n✓ Configuration prête. Tu peux maintenant tester depuis l'onglet Signature API.\n";
?>
