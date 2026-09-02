<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CommissionerManagementController extends Controller
{
    public function index(): View
    {
        $commissioners = User::role('commissionnaire')
            ->withCount('commissionedTenants as shops_count')
            ->withSum('commissions as total_commissions', 'amount')
            ->latest()
            ->paginate(20);

        $pendingCount = User::role('commissionnaire')->where('is_active', false)->count();

        return view('super-admin.commissioners.index', compact('commissioners', 'pendingCount'));
    }

    public function create(): View
    {
        return view('super-admin.commissioners.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'email'            => 'required|email|unique:users,email',
            'phone'            => 'nullable|string|max:30',
            'password'         => ['required', Password::min(8)],
            'id_document_type' => 'nullable|in:cni,passeport',
            'id_document'      => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',
        ]);

        $documentPath = $request->hasFile('id_document')
            ? $request->file('id_document')->store('commissioner-documents', 'local')
            : null;

        $user = User::create([
            'name'             => $validated['name'],
            'email'            => $validated['email'],
            'phone'            => $validated['phone'] ?? null,
            'password'         => Hash::make($validated['password']),
            'is_active'        => $request->boolean('is_active', true),
            'id_document_type' => $documentPath ? ($validated['id_document_type'] ?? null) : null,
            'id_document_path' => $documentPath,
        ]);

        $user->assignRole('commissionnaire');

        return redirect()->route('super-admin.commissioners.index')
            ->with('success', "Commissionnaire {$user->name} créé avec succès.");
    }

    public function toggleStatus(int $id): RedirectResponse
    {
        $user = User::role('commissionnaire')->findOrFail($id);
        $user->update(['is_active' => ! $user->is_active]);

        $msg = $user->is_active ? 'activé' : 'désactivé';
        return back()->with('success', "Commissionnaire {$msg} avec succès.");
    }

    public function showDocument(int $id): StreamedResponse
    {
        $user = User::role('commissionnaire')->findOrFail($id);
        abort_unless($user->id_document_path, 404);
        abort_unless(Storage::disk('local')->exists($user->id_document_path), 404);

        return Storage::disk('local')->response($user->id_document_path);
    }

    public function destroy(int $id): RedirectResponse
    {
        $user = User::role('commissionnaire')->findOrFail($id);

        if ($user->id_document_path) {
            Storage::disk('local')->delete($user->id_document_path);
        }

        $user->delete();

        return redirect()->route('super-admin.commissioners.index')
            ->with('success', 'Commissionnaire supprimé.');
    }
}
