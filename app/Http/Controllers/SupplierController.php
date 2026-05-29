<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:150',
            'contact_name'  => 'nullable|string|max:150',
            'phone'         => 'nullable|string|max:30',
            'email'         => 'nullable|email|max:150',
            'address'       => 'nullable|string|max:255',
            'city'          => 'nullable|string|max:100',
            'country'       => 'nullable|string|max:100',
            'payment_terms' => 'nullable|string|max:255',
            'notes'         => 'nullable|string',
        ]);

        $data['is_active'] = true;
        Supplier::create($data);

        return redirect()->route('profile.edit', ['tab' => 'suppliers'])
            ->with('success', 'Fournisseur ajouté.');
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:150',
            'contact_name'  => 'nullable|string|max:150',
            'phone'         => 'nullable|string|max:30',
            'email'         => 'nullable|email|max:150',
            'address'       => 'nullable|string|max:255',
            'city'          => 'nullable|string|max:100',
            'country'       => 'nullable|string|max:100',
            'payment_terms' => 'nullable|string|max:255',
            'notes'         => 'nullable|string',
            'is_active'     => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $supplier->update($data);

        return redirect()->route('profile.edit', ['tab' => 'suppliers'])
            ->with('success', 'Fournisseur modifié.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $supplier->delete();

        return redirect()->route('profile.edit', ['tab' => 'suppliers'])
            ->with('success', 'Fournisseur supprimé.');
    }
}
