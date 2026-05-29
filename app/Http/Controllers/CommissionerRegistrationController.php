<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|unique:users,email',
            'phone'                 => 'nullable|string|max:30',
            'password'              => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'phone'     => $validated['phone'] ?? null,
            'password'  => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        $user->assignRole('commissionnaire');

        Auth::login($user);

        return redirect()->route('commissioner.dashboard');
    }
}
