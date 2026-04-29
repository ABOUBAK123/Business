<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', ['user' => Auth::user()]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->name  = $request->name;
        $user->email = $request->email;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return back()->with('success', 'Profil mis à jour.');
    }

    public function updateAvatar(Request $request)
    {
        $request->validate(['avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048']]);

        $user = Auth::user();

        // Supprimer l'ancienne photo si elle est dans public/images/avatars/
        if ($user->avatar && str_starts_with($user->avatar, 'images/avatars/')) {
            $oldPath = public_path($user->avatar);
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }
        }

        $file = $request->file('avatar');

        // Forcer l'extension depuis le MIME réel (jamais depuis le nom client)
        $mimeToExt = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
        ];
        $safExt   = $mimeToExt[$file->getMimeType()] ?? 'jpg';
        $filename = uniqid('avatar_') . '.' . $safExt;

        $file->move(public_path('images/avatars'), $filename);
        $user->avatar = 'images/avatars/' . $filename;
        $user->save();

        return back()->with('success', 'Photo de profil mise à jour.');
    }

    public function updateDisplayName(Request $request)
    {
        $request->validate(['name' => ['required', 'string', 'max:255']]);

        $user = Auth::user();
        $user->name = $request->name;
        $user->save();

        return back()->with('success', 'Nom mis à jour.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Le mot de passe actuel est incorrect.']);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return back()->with('success', 'Mot de passe mis à jour.');
    }

    public function updateLanguage(Request $request)
    {
        $validated = $request->validate(['locale' => ['required', 'string', 'in:fr,en,es,pt,ar']]);

        $locale = $validated['locale'];

        $user = Auth::user();
        if ($user) {
            $user->locale = $locale;
            $user->save();
        }

        // Sauvegarder en session
        $request->session()->put('locale', $locale);
        $request->session()->save();

        // Appliquer immédiatement
        app()->setLocale($locale);

        return redirect()->back()->with('success', __('messages.language_updated'));
    }
}

