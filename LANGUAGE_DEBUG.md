# 🔧 Guide de Diagnostique - Changement de Langue Non Fonctionnel

## Étapes de Test

### 1️⃣ Vérifier que tout est installé
Accédez à: `http://localhost/debug/locale` 

Vous devriez voir un JSON affichant:
```json
{
  "app_locale": "fr",
  "session_locale": null,
  "config_locale": "fr",
  "test_translation": "Bienvenue"
}
```

### 2️⃣ Test de changement de langue

1. Connectez-vous à l'application
2. Cliquez sur votre profil (coin haut droit)
3. Dans le menu, sélectionnez une autre langue (par ex: English)
4. Cliquez sur le bouton **OK**
5. La page doit actualiser

### 3️⃣ Vérifier si ça a changé

Après étape 2, accédez de nouveau à: `http://localhost/debug/locale`

- ✅ **Correct** : `"session_locale": "en"` ET `"app_locale": "en"` ET `"test_translation": "Welcome"`
- ❌ **Problème** : `"session_locale": null` OU `"app_locale": "fr"` OU `"test_translation": "Bienvenue"`

---

## 🐛 Dépannage selon le symptôme

### Symptôme 1 : `"session_locale": null`
**Cause** : Le formulaire de changement de langue n'est pas soumis

**Solutions** :
```bash
# Vérifier la console navigateur pour les erreurs JavaScript
# Ouvrir DevTools → Console → Chercher les erreurs rouges

# Vérifier que la route exists
# Chercher dans routes/web.php la ligne: Route::post('/profile/language', ...)
```

### Symptôme 2 : `"session_locale": "en"` mais `"app_locale": "fr"`
**Cause** : Le middleware ne s'exécute pas correctement

**Solutions** :
1. Vérifier que `SetLocale.php` existe
2. Vérifier que `bootstrap/app.php` inclut le middleware:
   ```php
   $middleware->append(\App\Http\Middleware\SetLocale::class);
   ```

### Symptôme 3 : Traduction reste en français même après changement
**Cause** : Les fichiers de traduction sont vides ou mal structurés

**Vérifier** :
- Tous les fichiers existent dans `resources/lang/{locale}/`
- Chaque fichier PHP retourne un array avec des clés

```php
// resources/lang/en/messages.php
<?php
return [
    'welcome' => 'Welcome',
    // ... autres clés
];
```

---

## 🛠️ Solutions Rapides

### Solution 1 : Clear cache Laravel
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### Solution 2 : Vérifier les fichiers de langue
```bash
# Lister tous les fichiers
ls -la resources/lang/

# Vérifier qu'un fichier retourne un array
php -l resources/lang/fr/messages.php
php -l resources/lang/en/messages.php
```

### Solution 3 : Test manuel en routes
Ajouter dans `routes/web.php`:
```php
Route::get('/test-locale/{locale}', function ($locale) {
    session(['locale' => $locale]);
    app()->setLocale($locale);
    return redirect('/debug/locale');
});
```

Puis accédez à:
- `http://localhost/test-locale/en` → Doit passer à English
- `http://localhost/test-locale/fr` → Doit revenir en Français

---

## 📋 Checklist Complète

- [ ] Middleware `SetLocale.php` existe dans `app/Http/Middleware/`
- [ ] Middleware enregistré dans `bootstrap/app.php`
- [ ] Fichiers de langue existent: `resources/lang/{fr,en,es,pt,ar}/`
- [ ] Chaque fichier `.php` dans `lang/` retourne un array PHP
- [ ] Route `/profile/language` existe dans `routes/web.php`
- [ ] Contrôleur `ProfileController::updateLanguage()` valide les locales: `in:fr,en,es,pt,ar`
- [ ] Cache Laravel est vidé (voir Solution 1)

---

## 📞 Besoin d'aide?

1. Accédez à `/debug/locale` et envoyez le JSON
2. Vérifiez les erreurs dans la console navigateur (DevTools)
3. Vérifiez les logs Laravel: `storage/logs/laravel.log`

