<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::with(['manager'])->get();
        $tenant = app('currentTenant');
        return view('branches.index', compact('branches', 'tenant'));
    }

    public function create()
    {
        $managers = User::where('is_active', true)->get();
        return view('branches.form', ['branch' => new Branch(), 'managers' => $managers]);
    }

    public function store(Request $request)
    {
        $tenant = app('currentTenant');
        if (!$tenant->canAddBranch()) {
            return back()->with('error', 'Limite de succursales atteinte pour votre plan.');
        }

        $data = $request->validate([
            'name' => 'required|string|max:191',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'manager_id' => 'nullable|exists:users,id',
        ]);

        Branch::create($data);
        return redirect()->route('branches.index')->with('success', 'Succursale créée avec succès.');
    }

    public function edit(Branch $branch)
    {
        $managers = User::where('is_active', true)->get();
        return view('branches.form', compact('branch', 'managers'));
    }

    public function update(Request $request, Branch $branch)
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'manager_id' => 'nullable|exists:users,id',
            'is_active' => 'boolean',
        ]);

        $branch->update($data);
        return redirect()->route('branches.index')->with('success', 'Succursale modifiée.');
    }
}
