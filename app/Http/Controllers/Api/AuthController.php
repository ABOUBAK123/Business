<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ArticleBranchStock;
use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'shop_name' => 'required|string|max:191',
            'name'      => 'required|string|max:191',
            'email'     => 'required|email|unique:users,email',
            'phone'     => 'nullable|string|max:30',
            'password'  => 'required|string|min:8|confirmed',
        ]);

        DB::transaction(function () use ($data, &$token, &$user) {
            $plan = \App\Models\SubscriptionPlan::where('is_active', true)
                ->orderBy('price')
                ->first();

            $tenant = Tenant::create([
                'shop_name'      => $data['shop_name'],
                'email'          => $data['email'],
                'phone'          => $data['phone'] ?? null,
                'plan_id'        => $plan?->id,
                'status'         => 'trial',
                'trial_ends_at'  => now()->addDays(14),
            ]);

            $branch = Branch::create([
                'tenant_id' => $tenant->id,
                'name'      => $data['shop_name'],
                'is_main'   => true,
                'is_active' => true,
            ]);

            $user = User::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'name'      => $data['name'],
                'email'     => $data['email'],
                'phone'     => $data['phone'] ?? null,
                'password'  => Hash::make($data['password']),
                'is_active' => true,
            ]);

            $user->assignRole('proprietaire');

            $token = $user->createToken('mobile-app', ['*'], now()->addDays(30))->plainTextToken;
        });

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'        => $user->id,
                'name'      => $user->name,
                'email'     => $user->email,
                'phone'     => $user->phone,
                'roles'     => $user->getRoleNames(),
                'branch_id' => $user->branch_id,
                'tenant_id' => $user->tenant_id,
            ],
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            throw ValidationException::withMessages([
                'email' => ['Les informations d\'identification sont incorrectes.'],
            ]);
        }

        $user = Auth::user();

        if ($user->is_super_admin) {
            Auth::logout();
            return response()->json(['message' => 'Accès non autorisé pour les super administrateurs.'], 403);
        }

        if ($user->isCommissioner()) {
            Auth::logout();
            return response()->json(['message' => 'Accès non autorisé pour les commissionnaires.'], 403);
        }

        if (!$user->is_active) {
            Auth::logout();
            return response()->json(['message' => 'Votre compte a été désactivé.'], 403);
        }

        $user->update(['last_login_at' => now()]);

        $token = $user->createToken('mobile-app', ['*'], now()->addDays(30))->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'roles' => $user->getRoleNames(),
                'branch_id' => $user->branch_id,
                'tenant_id' => $user->tenant_id,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnecté avec succès.']);
    }

    public function user(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'id'        => $user->id,
            'name'      => $user->name,
            'email'     => $user->email,
            'phone'     => $user->phone,
            'roles'     => $user->getRoleNames(),
            'branch_id' => $user->branch_id,
            'tenant_id' => $user->tenant_id,
        ]);
    }
}
