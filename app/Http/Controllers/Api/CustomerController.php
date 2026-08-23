<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%");
            });
        }

        $customers = $query->orderBy('name')->limit(50)->get([
            'id', 'name', 'phone', 'type', 'credit_limit', 'credit_balance',
        ]);

        return response()->json([
            'data' => $customers->map(fn (Customer $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'phone' => $c->phone,
                'type' => $c->type,
                'credit_limit' => (float) $c->credit_limit,
                'credit_balance' => (float) $c->credit_balance,
                'available_credit' => $c->available_credit,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'phone' => 'nullable|string|max:20',
            'type' => 'nullable|in:individual,professional,wholesale',
            'credit_limit' => 'nullable|numeric|min:0',
        ]);

        $customer = Customer::create($data + ['type' => $data['type'] ?? 'individual']);

        return response()->json([
            'data' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'type' => $customer->type,
                'credit_limit' => (float) $customer->credit_limit,
                'credit_balance' => (float) $customer->credit_balance,
                'available_credit' => $customer->available_credit,
            ],
        ], 201);
    }
}
