# Résumé des Modifications - Janvier 2025

## Problème Analysé

**Symptôme**: Après qu'un document soit signé sur la plateforme SunnyStamp, l'application affiche toujours "En cours" au lieu de "Terminé".

**Racine Probable**: Le `platform_workflow_id` (identifiant du workflow sur la plateforme) n'est pas enregistré en base de données, empêchant le polling de récupérer le statut réel.

---

## Modifications Apportées

### 1. Extraction du Workflow ID Robustifiée
**Fichier**: `app/Http/Controllers/SignatureController.php` (lignes ~1050-1072)

**Changement**:
- **Avant**: `$workflowId = $wflResp->json('id');` — utilise seulement la clé `id`
- **Après**: 
  ```php
  $wflRespJson = $wflResp->json();
  $workflowId = $wflRespJson['id']
      ?? $wflRespJson['workflowId']
      ?? $wflRespJson['workflow_id']
      ?? ($wflRespJson['workflow']['id'] ?? null)
      ?? null;
  ```
  
**Raison**: Les instances SunnyStamp peuvent retourner des clés variées (`id`, `workflowId`, `workflow_id`, ou imbriquées). Avec des fallbacks, on capture tous les cas.

**Bénéfice**: Moins de requêtes API échouées dues à des parsing incorrects.

---

### 2. Validation Stricte du Workflow ID
**Fichier**: `app/Http/Controllers/SignatureController.php` (lignes ~1063-1072)

**Changement**:
```php
if (!is_string($workflowId) || $workflowId === '') {
    $this->lastPlatformError = 'create_workflow: ID du workflow non trouvé...';
    Log::error('SunnyStamp: ID workflow absent', [...]);
    return null;
}
```

**Raison**: Vérifier que l'ID est valide **avant** de l'assigner à `$this->lastPlatformWorkflowId`.

**Bénéfice**: Évite d'enregistrer des valeurs NULL/invalides en DB.

---

### 3. Logging Enrichi lors de l'Enregistrement
**Fichier**: `app/Http/Controllers/SignatureController.php` (lignes ~1722-1738)

**Changement**:
```php
if ($this->lastPlatformWorkflowId) {
    $execution->update([
        'platform_workflow_id' => $this->lastPlatformWorkflowId,
        'platform_status'      => 'started',
    ]);
    Log::info('SunnyStamp: platform_workflow_id enregistré', [
        'execution_id' => $execution->id,
        'platform_workflow_id' => $this->lastPlatformWorkflowId,
    ]);
} else {
    Log::warning('SunnyStamp: platform_workflow_id est NULL/vide, pas enregistré', [
        'execution_id' => $execution->id,
        'lastPlatformWorkflowId' => $this->lastPlatformWorkflowId,
        'action_type' => $request->input('action_type'),
    ]);
}
```

**Raison**: Le logging montre exactement si l'enregistrement a eu lieu et pourquoi pas.

**Bénéfice**: Diagnostic immédiat via les logs — savoir si `platform_workflow_id` a été NULL.

---

### 4. Endpoint de Diagnostic Direct
**Fichier**: `app/Http/Controllers/SignatureController.php` (nouvelles lignes ~1946-1968)

**Ajout**:
```php
public function diagExecution(string $executionId): JsonResponse
{
    $execution = WorkflowExecution::find($executionId);
    if (!$execution) {
        return response()->json(['ok' => false, 'message' => 'Execution introuvable']);
    }

    return response()->json([
        'ok'                  => true,
        'execution_id'        => $execution->id,
        'platform_workflow_id' => $execution->platform_workflow_id,
        'platform_status'     => $execution->platform_status,
        'status'              => $execution->status,
        // ... autres champs
    ]);
}
```

**Raison**: Inspecter directement la BD sans passer par le polling.

**Route**: `GET /api/signature/diag/{executionId}`

**Bénéfice**: Vérifier que `platform_workflow_id` est bien enregistré.

---

### 5. Token CSRF dans le Polling
**Fichier**: `resources/views/signatures/index.blade.php` (ligne ~651)

**Changement**:
```javascript
// Avant
headers: {
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
}

// Après
headers: {
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '',
},
credentials: 'same-origin',
```

**Raison**: Les requêtes de polling échouaient si le CSRF token n'était pas inclus.

**Bénéfice**: Le polling peut maintenant accéder à l'endpoint authentifié.

---

### 6. Exemptions CSRF Complètes pour le Webhook
**Fichier**: `bootstrap/app.php`

**Changement**:
```php
$middleware->validateCsrfTokens(except: [
    'api/oo-callback/*',
    'public/api/oo-callback/*',
    'e-administration_laravel/api/oo-callback/*',
    'e-administration_laravel/public/api/oo-callback/*',
    'api/signature/platform-webhook',
    'e-administration_laravel/api/signature/platform-webhook',
    'e-administration_laravel/public/api/signature/platform-webhook',
    'public/api/signature/platform-webhook',
]);
```

**Raison**: Le webhook SunnyStamp n'envoie pas de token CSRF (il appelle l'app externe). Tous les chemins de webhook doivent être exemptés.

**Bénéfice**: Le webhook peut être reçu sans erreur 419.

---

### 7. Routes Webhook avec Alias
**Fichier**: `routes/web.php` (lignes ~90-108)

**Changement**: Ajout des routes webhook avec alias pour supports sous-dossiers:
```php
Route::post('/api/signature/platform-webhook', [SignatureController::class, 'platformWebhook'])->name('signature.platform-webhook');
Route::post('/e-administration_laravel/api/signature/platform-webhook', [SignatureController::class, 'platformWebhook'])->name('signature.platform-webhook.subdir');
Route::post('/e-administration_laravel/public/api/signature/platform-webhook', [SignatureController::class, 'platformWebhook'])->name('signature.platform-webhook.subdir.public');
Route::post('/public/api/signature/platform-webhook', [SignatureController::class, 'platformWebhook'])->name('signature.platform-webhook.public');
```

**Raison**: Support de tous les chemins possibles (WAMP, ngrok, production).

**Bénéfice**: Webhook reçu quel que soit le chemin de l'app.

---

## Guide de Diagnostic

Voir **DIAGNOSTIC_WORKFLOW_STATUS.md** pour les étapes détaillées.

### Résumé Rapide:
1. ✅ Vérifier que les colonnes `platform_workflow_id` et `platform_status` existent
2. ✅ Créer un test de signature complet
3. ✅ Vérifier en BD: `SELECT platform_workflow_id FROM workflow_executions LIMIT 1;`
4. ✅ Vérifier les logs: `tail storage/logs/laravel.log | grep platform_workflow`
5. ✅ Tester le diagnostic: `curl https://e-administration.gedsante.ci/api/signature/diag/{exec_id}`

---

## Changements Fichiers

```
Modified:
  app/Http/Controllers/SignatureController.php       (+~85 lines)
  routes/web.php                                     (+~20 lines)
  bootstrap/app.php                                  (updated CSRF except)
  resources/views/signatures/index.blade.php         (+X-CSRF-Token header)

Created:
  DIAGNOSTIC_WORKFLOW_STATUS.md                      (guide complet)
  deploy_server.sh                                   (script de déploiement)
```

---

## Déploiement

Sur le serveur Ubuntu:
```bash
cd /var/www/html/e-administration
git pull origin main
php artisan migrate --force
php artisan route:cache
php artisan config:cache
```

Ou utiliser le script:
```bash
bash deploy_server.sh
```

---

## Validation

Après déploiement, vérifier:

1. **Colonnes existent**:
   ```bash
   mysql e_parapheur -e "DESCRIBE workflow_executions;" | grep platform
   ```

2. **Diagnostic endpoint marche**:
   ```bash
   curl https://e-administration.gedsante.ci/api/signature/diag/{exec_id}
   ```
   Doit retourner JSON avec `platform_workflow_id`.

3. **Logs clairs**:
   ```bash
   tail storage/logs/laravel.log | grep -i "platform_workflow"
   ```

---

## Prochaines Actions pour l'Utilisateur

1. **Déployer** le code sur le serveur Ubuntu
2. **Exécuter un test complet** de signature
3. **Lancer les diagnostics** du fichier `DIAGNOSTIC_WORKFLOW_STATUS.md`
4. **Reporter les résultats** (surtout les logs et sorties DB)

Si `platform_workflow_id` reste NULL après test:
- Les fallbacks d'extraction n'ont pas trouvé d'ID
- Vérifier la structure exacte de la réponse SunnyStamp:
  ```bash
  tail storage/logs/laravel.log | grep "response" | head -1
  ```
