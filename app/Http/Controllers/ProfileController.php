<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $tab = in_array($request->tab, ['profil', 'password', 'categories', 'suppliers'])
            ? $request->tab
            : 'profil';

        $categories = $tab === 'categories'
            ? Category::orderBy('sort_order')->orderBy('name')->get()
            : collect();

        $suppliers = $tab === 'suppliers'
            ? Supplier::orderBy('name')->get()
            : collect();

        return view('profile.edit', [
            'user'       => $request->user(),
            'tab'        => $tab,
            'categories' => $categories,
            'suppliers'  => $suppliers,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name'   => ['required', 'string', 'max:191'],
            'email'  => ['required', 'email', 'max:191', 'unique:users,email,' . $user->id],
            'phone'  => ['nullable', 'string', 'max:20'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        if ($user->email !== $data['email']) {
            $data['email_verified_at'] = null;
        }

        $user->update($data);

        return back()->with('success', 'Profil mis à jour avec succès.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password'      => ['required', 'current_password'],
            'password'              => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
            'password.confirmed'                => 'Les mots de passe ne correspondent pas.',
            'password.min'                      => 'Le mot de passe doit contenir au moins 8 caractères.',
        ]);

        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success_password', 'Mot de passe modifié avec succès.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);

        $user = $request->user();
        Auth::logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
