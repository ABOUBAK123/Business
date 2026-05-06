# Guide de Diagnostic - Suivi Statut Workflow SunnyStamp

## Vue d'ensemble
Ce guide vous aide à vérifier que le système de suivi en temps réel du statut de signature fonctionne correctement.

---

## Problème Identifié
Après que le document est signé sur la plateforme SunnyStamp, l'application n'affiche toujours pas "Terminé" — elle reste sur "En cours".

**Cause potentielle:** Le `platform_workflow_id` n'est pas enregistré en base de données, ou le polling ne récupère pas le statut.

---

## Diagnostics à Exécuter

### 1. Vérifier que les colonnes de la migration existent

**Sur le serveur Ubuntu:**
```bash
# Connexion à MySQL
mysql -u root -p e_parapheur << EOF
DESCRIBE workflow_executions;
EOF
```

**Résultat attendu:** Les colonnes `platform_workflow_id` et `platform_status` doivent être visibles.

**Résultat possible:**
```
| Field | Type | Null | Key | Default | Extra |
...
| platform_workflow_id | varchar(255) | YES | MUL | NULL | |
| platform_status | varchar(255) | YES | | NULL | |
```

---

### 2. Vérifier que platform_workflow_id est enregistré après un test

**Avant de tester:**
Exécuter un test de signature complet (créer workflow, envoyer pour signature).

**Après le test, sur le serveur:**
```bash
# Voir les 5 dernières exécutions
mysql -u root -p e_parapheur << EOF
SELECT id, workflow_id, status, platform_workflow_id, platform_status, started_at
FROM workflow_executions
ORDER BY started_at DESC
LIMIT 5;
EOF
```

**Résultat attendu:** La colonne `platform_workflow_id` doit contenir une valeur UUID/ID (pas NULL).

**Si c'est NULL:**
- Le serveur SunnyStamp n'a pas renvoyé d'ID workflow valide
- Vérifier les logs: `tail -50 storage/logs/laravel.log | grep "platform_workflow_id"`

---

### 3. Vérifier que le webhook est appelé

**Sur le serveur, vérifier les logs:**
```bash
tail -100 storage/logs/laravel.log | grep -i webhook
```

**Résultat attendu:** Des entrées comme:
```
[2025-01-XX XX:XX:XX] webhook: événement reçu workflowFinished
[2025-01-XX XX:XX:XX] webhook: exécution transitioned to completed
```

**Si rien:**
- Le serveur SunnyStamp n'appelle pas le webhook
- Vérifier l'URL du webhook dans la réponse de création de workflow: 
  ```bash
  tail -50 storage/logs/laravel.log | grep -i "notificationUrl\|webhookUrl"
  ```

---

### 4. Tester le endpoint de polling manuellement

**Depuis n'importe quel ordinateur (avec connexion au serveur):**

Remplacer `{EXECUTION_ID}` par un vrai UUID d'exécution trouvé à l'étape 2.

```bash
# Sans authentification (erreur attendue 401)
curl -H "X-Requested-With: XMLHttpRequest" \
  "https://e-administration.gedsante.ci/signatures/platform-status/{EXECUTION_ID}"

# Avec authentification (si vous avez accès)
# Utiliser le même token que votre session Blade
curl -H "X-Requested-With: XMLHttpRequest" \
  -H "X-CSRF-Token: YOUR_CSRF_TOKEN_HERE" \
  -b "LARAVEL_SESSION=YOUR_SESSION_COOKIE" \
  "https://e-administration.gedsante.ci/signatures/platform-status/{EXECUTION_ID}"
```

**Résultat attendu:** JSON avec:
```json
{
  "ok": true,
  "local_status": "signature",
  "platform_status": "STARTED",
  "phase": "signing"
}
```

---

### 5. Endpoint de diagnostic de base de données

**Accédez à:**
```
GET /api/signature/diag/{EXECUTION_ID}
```

Remplacer `{EXECUTION_ID}` par l'ID obtenu à l'étape 2.

**URL complète:**
```
https://e-administration.gedsante.ci/api/signature/diag/{EXECUTION_ID}
```

**Résultat attendu:** JSON avec tous les détails:
```json
{
  "ok": true,
  "execution_id": "...",
  "platform_workflow_id": "...",
  "platform_status": "STARTED",
  "status": "signature",
  "step_data": {...}
}
```

**Si `platform_workflow_id` est null:**
→ L'enregistrement ne s'est pas fait. Passer à étape 6.

---

### 6. Vérifier les logs de création de workflow

**Sur le serveur:**
```bash
# Voir les logs de création
tail -200 storage/logs/laravel.log | grep -A5 "create_workflow"

# Ou plus complet:
tail -200 storage/logs/laravel.log | grep -i "sunnystamp"
```

**Chercher:**
- `"workflowId trouvé dans la réponse"` ✅ = Bon
- `"ID du workflow non trouvé dans la réponse"` ❌ = Mauvais
- `"Échec création workflow"` ❌ = L'API a échoué

**Exemple de logs attendus:**
```
[2025-01-XX] SunnyStamp: platform_workflow_id enregistré {"execution_id":"abc123","platform_workflow_id":"wxyz789"}
```

**Exemple de logs problématiques:**
```
[2025-01-XX] SunnyStamp: platform_workflow_id est NULL/vide, pas enregistré
[2025-01-XX] SunnyStamp: échec création workflow
```

---

## Flux de Diagnostique Recommandé

1. ✅ **Vérifier les colonnes** (étape 1)
2. ✅ **Créer un test de signature**
3. ✅ **Vérifier le platform_workflow_id en DB** (étape 2)
4. ✅ **Vérifier les logs webhook** (étape 3)
5. ✅ **Tester le endpoint polling** (étape 4)
6. ✅ **Vérifier le logs de création** (étape 6)

Si tout passe ✅, alors le système fonctionne. Sinon, examiner quel diagnostic échoue.

---

## Corrections Potentielles

| Diagnostic échoué | Cause probable | Solution |
|---|---|---|
| Colonne n'existe pas | Migration non appliquée | `php artisan migrate --force` |
| platform_workflow_id NULL | Réponse API ne contient pas d'ID | Vérifier réponse brute: `tail logs` → chercher `response` en JSON |
| Pas d'appel webhook | URL webhook incorrecte ou SunnyStamp ne l'appelle pas | Vérifier logs SunnyStamp, tester endpoint webhook manuellement |
| Polling retourne 401 | Authentification échouée | Vérifier que `X-CSRF-Token` et cookies sont envoyés |
| Polling retourne erreur 500 | Exception côté contrôleur | Vérifier `storage/logs/laravel.log` pour l'exception |

---

## Changements Récents (Janvier 2025)

### Code Modifié:
1. **app/Http/Controllers/SignatureController.php**
   - Ligne ~1050-1072: Extraction du workflowId avec fallbacks multiples (id, workflowId, workflow_id, workflow.id)
   - Ligne ~1722-1738: Logging amélioré lors de l'enregistrement du `platform_workflow_id`

2. **routes/web.php**
   - Ajout de `GET /api/signature/diag/{executionId}` pour diagnostic
   - Exemptions CSRF mise à jour pour tous les alias webhook

3. **resources/views/signatures/index.blade.php**
   - Fetch polling inclut maintenant `X-CSRF-Token` header

### Migration:
   - `database/migrations/2026_05_06_002703_add_platform_fields_to_workflow_executions_table.php`

---

## Support

En cas de problème:
1. Exécuter les diagnostics ci-dessus
2. Collecter les logs: `tail -200 storage/logs/laravel.log`
3. Fournir les résultats des tests
