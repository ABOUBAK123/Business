# Guide de Configuration de la Traduction Multilingue

## 🎯 Vue d'ensemble
Le système de traduction est maintenant configuré pour supporter **5 langues** :
- 🇫🇷 Français (FR)
- 🇬🇧 Anglais (EN)
- 🇪🇸 Espagnol (ES)
- 🇵🇹 Portugais (PT)
- 🇸🇦 Arabe (AR)

## ✅ Étapes déjà complétées

1. **Dossiers de traduction** : `resources/lang/{fr,en,es,pt,ar}/`
2. **Fichiers de traduction de base** :
   - `messages.php` - Messages généraux
   - `auth.php` - Authentification
   - `navigation.php` - Navigation
   - `buttons.php` - Boutons
   - `documents.php` - Documents
   - `validation.php` - Validations

3. **Middleware SetLocale** : `app/Http/Middleware/SetLocale.php`
   - Applique la locale de session à chaque requête
   - Enregistré dans `bootstrap/app.php`

4. **Fichiers Blade modifiés** :
   - Tous les layouts maintenant utilisent `lang="{{ ... }}"` dynamique
   - Textes principaux convertis en traductions

## 📝 Comment ajouter des traductions aux Blade existants

### Étape 1 : Identifier les textes à traduire

Cherchez les textes figés en français (et non des clés de traduction) dans les fichiers `.blade.php`

```blade
<!-- ❌ Avant (texte figé) -->
<button>Valider</button>

<!-- ✅ Après (avec traduction) -->
<button>{{ __('buttons.submit') }}</button>
```

### Étape 2 : Ajouter les clés de traduction dans tous les fichiers `.php`

Si vous créez une nouvelle clé, ajoutez-la dans **tous les fichiers** correspondants :

```php
// resources/lang/fr/messages.php
'user_created' => 'Utilisateur créé avec succès',

// resources/lang/en/messages.php
'user_created' => 'User created successfully',

// resources/lang/es/messages.php
'user_created' => 'Usuario creado exitosamente',

// resources/lang/pt/messages.php
'user_created' => 'Usuário criado com sucesso',

// resources/lang/ar/messages.php
'user_created' => 'تم إنشاء المستخدم بنجاح',
```

### Étape 3 : Utiliser la traduction dans la Blade

```blade
<p>{{ __('messages.user_created') }}</p>
```

## 🗂️ Organisation des traductions

- **messages.php** → Textes généraux (bienvenue, labels génériques)
- **auth.php** → Authentification (login, register, password)
- **navigation.php** → Menu, profil, liens
- **buttons.php** → Tous les boutons
- **documents.php** → Documents, workflows
- **validation.php** → Messages d'erreur de validation

## 🔧 Fichiers Blade à traduire

### Fichiers AUTH (traduction en cours ✅)
- ✅ `layouts/auth.blade.php`
- ✅ `auth/login.blade.php`
- ✅ `auth/register.blade.php`
- ✅ `auth/forgot-password.blade.php`

### Fichiers PUBLICS (à compléter)
- ⚠️ `public-act-requests/index.blade.php`
- ⚠️ `public-act-requests/create.blade.php`
- ⚠️ `courrier/visualiser.blade.php`

### Fichiers PRIVÉS/ADMIN (à compléter)
- ⚠️ `admin/index.blade.php`
- ⚠️ `workflows/**/*.blade.php`
- ⚠️ `act-requests/**/*.blade.php`
- Et tous les autres fichiers...

## 📋 Exemple d'implémentation rapide

### Option 1 : Utiliser un script PHP pour automatiser

```php
// Créer un artisan command pour chercher et convertir les textes
php artisan make:command TranslateBladeStrings
```

### Option 2 : Faire la traduction manuellement par fichier

Pour chaque fichier Blade :
1. Ouvrir le fichier
2. Chercher les textes en français non traduits
3. Ajouter les clés dans tous les fichiers `resources/lang/*/`
4. Remplacer les textes par `{{ __('namespace.key') }}`

## 🧪 Test du système

```php
// Tester dans la route
Route::get('/test-locale', function () {
    return [
        'current_locale' => app()->getLocale(),
        'messages' => __('messages'),
        'auth' => __('auth'),
    ];
});
```

## 📌 Notes importants

1. **Arabe (RTL)** : La page applique automatiquement `dir="rtl"` pour l'arabe
2. **Session** : La locale est stockée dans la session utilisateur
3. **Fallback** : Si une traduction manque, Laravel retourne la clé (ex: `auth.login`)
4. **Format HTML** : Préservez toujours le HTML (`&nbsp;`, `<i>`, etc.)

## 🚀 Prochaines étapes

1. Compléter les traductions pour les 3-4 fichiers Blade les plus importants
2. Tester chaque traduction en changeant la langue dans le profil utilisateur
3. Ajouter progressivement d'autres fichiers au fur et à mesure
4. Considérer un service de traduction automatique pour accélérer le processus

