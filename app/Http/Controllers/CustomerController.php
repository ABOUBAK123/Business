<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::withCount('sales');
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%");
            });
        }
        if ($request->type) {
            $query->where('type', $request->type);
        }
        $customers = $query->orderBy('name')->paginate(20)->withQueryString();
        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        return view('customers.form', ['customer' => new Customer()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'nif' => 'nullable|string|max:50',
            'type' => 'required|in:individual,professional,wholesale',
            'credit_limit' => 'nullable|numeric|min:0',
        ]);
        Customer::create($data);
        return redirect()->route('customers.index')->with('success', 'Client créé avec succès.');
    }

    public function show(Customer $customer)
    {
        $sales = Sale::where('customer_id', $customer->id)->with('items', 'branch')->latest()->take(20)->get();
        return view('customers.show', compact('customer', 'sales'));
    }

    public function edit(Customer $customer)
    {
        return view('customers.form', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'nif' => 'nullable|string|max:50',
            'type' => 'required|in:individual,professional,wholesale',
            'credit_limit' => 'nullable|numeric|min:0',
            'classification' => 'in:regular,vip,inactive',
        ]);
        $customer->update($data);
        return redirect()->route('customers.index')->with('success', 'Client modifié.');
    }
}
