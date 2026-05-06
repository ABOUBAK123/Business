# État Actuel du Debug - Statut Workflow

## 🔍 Diagnostic Effectué

### ✅ État Serveur (May 6, 2026)

1. **Git Pull**: Réussi ✅
2. **Migration**: Appliquée ✅  
   - Colonnes `platform_workflow_id` et `platform_status` existent
3. **Route Cache**: Réussi ✅
4. **Config Cache**: Réussi ✅

### ❌ Problème Identifié

```
AVANT: platform_workflow_id est NULL en BD
ATTENDU: Devrait être rempli après appel /signatures/get-invite-url
```

**Résultat diagnostic BD:**
```sql
SELECT id, platform_workflow_id, platform_status FROM workflow_executions LIMIT 1;
→ platform_workflow_id: NULL
→ platform_status: NULL
```

**Logs:** Aucune entrée `platform_workflow` trouvée dans `laravel.log`

---

## 🎯 Hypothèses

| # | Hypothèse | Probabilité | Vérification |
|---|---|---|---|
| 1 | `getSignatureInviteUrl()` ne s'exécute jamais | HAUTE | Logs au démarrage: "getSignatureInviteUrl début" |
| 2 | `buildPlatformInviteUrl()` retourne NULL | HAUTE | Logs: "ID workflow absent" |
| 3 | `$this->lastPlatformWorkflowId` reste vide après extraction | HAUTE | Logs: "workflowId extrait" vs "NULL/vide" |
| 4 | La condition `if ($this->lastPlatformWorkflowId)` est FALSE | TRÈS HAUTE | Logs: "condition: TRUE" vs "FALSE" |
| 5 | Réponse SunnyStamp ne contient pas d'ID | HAUTE | Logs: "response" → structure JSON |

---

## 📋 Logging Ajouté (Commit 7b3b2c5)

### Points de Trace

1. **Début de méthode** (ligne ~1646):
   ```
   Log::info('SunnyStamp: getSignatureInviteUrl début', [...])
   ```
   → Confirme que la méthode est appelée

2. **Assignation workflowId** (ligne ~1078):
   ```
   Log::info('SunnyStamp: workflowId extrait et assigné', [...])
   ```
   → Montre que l'extraction réussit

3. **Avant enregistrement** (ligne ~1726):
   ```
   Log::info('SunnyStamp: avant enregistrement platform_workflow_id', [
       'lastPlatformWorkflowId' => $this->lastPlatformWorkflowId,
       'condition' => $this->lastPlatformWorkflowId ? 'TRUE' : 'FALSE',
   ])
   ```
   → Montre la valeur et le résultat de la condition IF

4. **Succès** (ligne ~1731):
   ```
   Log::info('SunnyStamp: platform_workflow_id enregistré ✅', [...])
   ```
   → IF condition était TRUE

5. **Échec** (ligne ~1739):
   ```
   Log::error('SunnyStamp: platform_workflow_id est NULL/vide ❌, pas enregistré', [...])
   ```
   → IF condition était FALSE

---

## 🚀 Prochaines Actions

### Phase 1: Déploiement Serveur (5 min)

```bash
cd /var/www/html/e-administration
git pull origin main
php artisan migrate --force
php artisan route:cache && php artisan config:cache
```

Ou utiliser le script:
```bash
bash test_signature_flow.sh
```

### Phase 2: Créer Test Complet (10 min)

1. Accédez à: `https://e-administration.gedsante.ci/signatures`
2. Lancez un workflow de signature complet
3. Cliquez sur "Signer"
4. Laissez la page ouverte

### Phase 3: Vérifier Logs en Temps Réel (2 min)

Sur le serveur:
```bash
cd /var/www/html/e-administration

# Option A: Afficher les logs en continu
tail -f storage/logs/laravel.log | grep -i 'platform\|sunnystamp'

# Option B: Après le test, voir les dernières entrées
tail -50 storage/logs/laravel.log | grep -i 'platform'
```

**Chercher absolument:**
- `"getSignatureInviteUrl début"` → méthode appelée ✅
- `"avant enregistrement"` → exécution continue ✅
- `"condition: TRUE"` vs `"condition: FALSE"` → valeur clé
- `"enregistré ✅"` vs `"est NULL/vide ❌"` → résultat final

### Phase 4: Vérifier BD Après Test (2 min)

```bash
mysql e_parapheur -e "SELECT id, platform_workflow_id, platform_status, created_at FROM workflow_executions ORDER BY created_at DESC LIMIT 3;"
```

**Résultat attendu:** 
- Dernière exécution doit avoir `platform_workflow_id` rempli (NOT NULL)

### Phase 5: Diagnostiquer Selon Logs (variable)

| Log Absent | Cause | Solution |
|---|---|---|
| "getSignatureInviteUrl début" | Endpoint pas appelé ou erreur 422 avant log | Vérifier console navigateur pour erreurs réseau |
| "avant enregistrement" | Exception levée dans buildPlatformInviteUrl | Chercher `Exception` dans logs |
| "condition: FALSE" | `$this->lastPlatformWorkflowId` est vide | Chercher "ID workflow absent" ou "Échec création" |
| "enregistré ✅" mais BD still NULL | Bug dans update() | Vérifier exception Eloquent dans logs |

---

## 📊 Arbre de Diagnostique

```
Logs "getSignatureInviteUrl début" ?
├─ NON → Endpoint pas appelé
│        └─ Vérifier: JS déclenche fetch? Code HTTP 422?
└─ OUI → Logs "avant enregistrement" ?
         ├─ NON → Exception dans buildPlatformInviteUrl()
         │        └─ Chercher: "Créer workflow", "Upload doc", "Start", "Invite"
         └─ OUI → Logs "condition: TRUE" ou "FALSE" ?
                  ├─ TRUE + "enregistré ✅" → SUCCESS! 🎉
                  ├─ TRUE + pas "enregistré" → Bug Eloquent update()
                  └─ FALSE → ID extraction échoue
                             └─ Vérifier: Réponse API structure?
```

---

## 📝 Commit & Code

**Commit Récent**: 7b3b2c5
**Messages de Log Ajoutés**: 5 points de trace critiques
**Fichiers Modifiés**: `app/Http/Controllers/SignatureController.php`

---

## ⏱️ Timeline

- **May 5, 2026**: Commits de debug initial (3 commits)
- **May 6, 2026**: 
  - ✅ Déploiement sur serveur
  - ✅ Vérification colonnes BD existent
  - ❌ platform_workflow_id reste NULL
  - ✅ Ajout logging trace complet (Commit 7b3b2c5)
- **Now**: Attendre résultat du test avec nouveaux logs

---

## 📞 Informations de Contact

Si résultats des logs ne montrent pas la cause:

Préparer:
1. **Logs complets** (30 lignes avant/après le test):
   ```bash
   grep -A30 "getSignatureInviteUrl début" storage/logs/laravel.log
   ```

2. **Réponse API SunnyStamp brute**:
   ```bash
   grep "response" storage/logs/laravel.log | head -3
   ```

3. **État BD complètement**:
   ```bash
   mysql e_parapheur -e "SELECT * FROM workflow_executions ORDER BY created_at DESC LIMIT 1\G"
   ```

4. **Console du navigateur** (F12 Network tab) montrant:
   - Request: POST `/signatures/get-invite-url`
   - Response: JSON avec `url` et `ok: true`?
