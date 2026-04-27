# ⚠️ Diagnostic: Pas de Changement de Langue

Si le changement de langue ne fonctionne pas, suivez ces étapes:

## 🔍 Diagnostic Rapide

### 1. Tester via Artisan
```bash
php artisan test:locale en
```
Cela devrait afficher:
```
✓ SetLocale.php exists
✓ fr: 4 translation files
✓ en: 4 translation files
...
✓ Successfully set locale to: en
✓ messages.welcome = 'Welcome'
```

### 2. Vérifier via HTTP
Accédez à: **`http://localhost/debug/locale`**

Cherchez la clé `"app_locale"` - elle devrait être `"fr"` au départ.

### 3. Tester le changement
1. Dans la barre de navigation: Cliquez sur votre profil (coin haut droit)
2. Sélectionnez **English**
3. Cliquez **OK**
4. Retournez à `http://localhost/debug/locale`
5. `"app_locale"` devrait maintenant être `"en"`

---

## ❌ Problème: Rien n'a changé

Vérifiez les logs:
```bash
# Voir les erreurs Laravel
tail -f storage/logs/laravel.log

# Vérifier les erreurs PHP
tail -f storage/logs/php_errors.log
```

### Actions rapides:
```bash
# Vider le cache
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Régénérer le config
php artisan config:cache
```

---

## 📄 Fichiers Impliqués

✅ **Créés/Modifiés** :
- `app/Http/Middleware/SetLocale.php` — Applique la locale chaque requête
- `app/Http/Controllers/DebugController.php` — Endpoint de test
- `app/Console/Commands/TestLocale.php` — Commande Artisan de test
- `bootstrap/app.php` — Enregistre le middleware
- `resources/lang/{fr,en,es,pt,ar}/*.php` — Fichiers de traduction
- `routes/web.php` — Route `/profile/language` + route debug

---

## 🎯 Résolution des Problèmes

| Problème | Cause | Solution |
|----------|-------|----------|
| `"session_locale": null` | Formulaire non soumis | Vérifier DevTools Console pour les erreurs JS |
| `"app_locale": "fr"` malgré changement | Middleware ne s'exécute pas | Vérifier `bootstrap/app.php` ligne avec `SetLocale` |
| Texte reste en français | Fichiers traduction vides | Vérifier `resources/lang/en/messages.php` has content |
| Error 500 | Erreur PHP | Vérifier `storage/logs/laravel.log` |

---

## 🧹 Nettoyage si Erreurs

Si vous avez des erreurs, essayez:
```bash
# Régénérer composer
composer dump-autoload

# Réinitialiser cache
php artisan optimize:clear

# Retester
php artisan test:locale
```

---

## ✨ Si Ça Marche!

Une fois que le changement de langue fonctionne:
1. Naviguez dans l'app avec différentes langues
2. Testez que le texte change bien
3. Vérifiez que la direction RTL s'applique pour l'arabe
4. Continuez en traduisant les autres fichiers Blade!

