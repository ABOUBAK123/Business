<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['roles', 'branch'])->where('is_super_admin', false);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        $users = $query->latest()->paginate(20)->withQueryString();
        return view('users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::all();
        $branches = Branch::where('is_active', true)->get();
        return view('users.form', ['user' => new User(), 'roles' => $roles, 'branches' => $branches]);
    }

    public function store(Request $request)
    {
        $tenant = app('currentTenant');
        if (!$tenant->canAddUser()) {
            return back()->with('error', 'Limite d\'utilisateurs atteinte pour votre plan.');
        }

        $data = $request->validate([
            'name' => 'required|string|max:191',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|exists:roles,name',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'],
            'branch_id' => $data['branch_id'],
            'is_active' => true,
        ]);

        $user->assignRole($data['role']);
        return redirect()->route('users.index')->with('success', 'Utilisateur créé avec succès.');
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        $branches = Branch::where('is_active', true)->get();
        return view('users.form', compact('user', 'roles', 'branches'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|exists:roles,name',
            'branch_id' => 'nullable|exists:branches,id',
            'is_active' => 'boolean',
        ]);

        $user->update([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'branch_id' => $data['branch_id'],
            'is_active' => $data['is_active'] ?? true,
        ]);

        $user->syncRoles([$data['role']]);
        return redirect()->route('users.index')->with('success', 'Utilisateur modifié.');
    }

    public function toggleActive(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);
        return back()->with('success', 'Statut de l\'utilisateur modifié.');
    }
}
