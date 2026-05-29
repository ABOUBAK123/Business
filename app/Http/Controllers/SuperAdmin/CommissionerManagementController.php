<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class CommissionerManagementController extends Controller
{
    public function index(): View
    {
        $commissioners = User::role('commissionnaire')
            ->withCount('commissionedTenants as shops_count')
            ->withSum('commissions as total_commissions', 'amount')
            ->latest()
            ->paginate(20);

        return view('super-admin.commissioners.index', compact('commissioners'));
    }

    public function create(): View
    {
        return view('super-admin.commissioners.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'phone'    => 'nullable|string|max:30',
            'password' => ['required', Password::min(8)],
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'phone'    => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'is_active'=> true,
        ]);

        $user->assignRole('commissionnaire');

        return redirect()->route('super-admin.commissioners.index')
            ->with('success', "Commissionnaire {$user->name} créé avec succès.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $user = User::role('commissionnaire')->findOrFail($id);
        $user->delete();

        return redirect()->route('super-admin.commissioners.index')
            ->with('success', 'Commissionnaire supprimé.');
    }
}
