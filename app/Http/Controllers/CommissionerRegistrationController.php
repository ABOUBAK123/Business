<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class CommissionerRegistrationController extends Controller
{
    public function create(): View
    {
        return view('register.commissioner');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'email'            => 'required|email|unique:users,email',
            'phone'            => 'nullable|string|max:30',
            'password'         => ['required', 'confirmed', Password::min(8)],
            'id_document_type' => 'required|in:cni,passeport',
            'id_document'      => 'required|file|mimes:pdf,jpg,jpeg,png|max:4096',
        ]);

        $documentPath = $request->file('id_document')->store('commissioner-documents', 'local');

        $user = User::create([
            'name'             => $validated['name'],
            'email'            => $validated['email'],
            'phone'            => $validated['phone'] ?? null,
            'password'         => Hash::make($validated['password']),
            'is_active'        => false,
            'id_document_type' => $validated['id_document_type'],
            'id_document_path' => $documentPath,
        ]);

        $user->assignRole('commissionnaire');

        return redirect()->route('login')->with('success',
            'Votre compte a été créé et est en attente de validation par notre équipe. '
            . 'Vous recevrez un accès dès que votre pièce d\'identité aura été vérifiée.');
    }
}
