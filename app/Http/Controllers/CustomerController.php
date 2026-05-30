<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::withCount('sales')->withSum('sales', 'total_ttc');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%");
            });
        }
        if ($request->type) {
            $query->where('type', $request->type);
        }
        if ($request->filter === 'credit') {
            $query->where('credit_balance', '>', 0);
        }

        $customers    = $query->orderBy('name')->paginate(20)->withQueryString();
        $totalCredit  = Customer::sum('credit_balance');
        $creditCount  = Customer::where('credit_balance', '>', 0)->count();

        return view('customers.index', compact('customers', 'totalCredit', 'creditCount'));
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
        $customer->load(['payments.user']);
        $sales = $customer->sales()->with('branch')->latest()->take(30)->get();
        $q     = $customer->sales();
        $stats = [
            'total_achats' => (clone $q)->sum('total_ttc'),
            'nb_commandes' => (clone $q)->count(),
            'credit_sales' => (clone $q)->whereIn('payment_status', ['credit', 'partial'])->sum('total_ttc'),
            'total_paye'   => $customer->payments()->sum('amount'),
        ];
        return view('customers.show', compact('customer', 'sales', 'stats'));
    }

    public function edit(Customer $customer)
    {
        return view('customers.form', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:191',
            'phone'          => 'nullable|string|max:20',
            'email'          => 'nullable|email',
            'address'        => 'nullable|string',
            'nif'            => 'nullable|string|max:50',
            'type'           => 'required|in:individual,professional,wholesale',
            'credit_limit'   => 'nullable|numeric|min:0',
            'classification' => 'nullable|in:regular,vip,inactive',
        ]);
        $customer->update($data);
        return redirect()->route('customers.show', $customer)->with('success', 'Client modifié.');
    }

    public function recordPayment(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'amount'         => 'required|numeric|min:1|max:' . $customer->credit_balance,
            'payment_method' => 'required|in:cash,mobile_money,bank_transfer,cheque',
            'reference'      => 'nullable|string|max:100',
            'notes'          => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($data, $customer) {
            CustomerPayment::create([
                'tenant_id'      => app('currentTenant')->id,
                'customer_id'    => $customer->id,
                'user_id'        => auth()->id(),
                'amount'         => $data['amount'],
                'payment_method' => $data['payment_method'],
                'reference'      => $data['reference'] ?? null,
                'notes'          => $data['notes'] ?? null,
            ]);

            $customer->decrement('credit_balance', $data['amount']);
        });

        return back()->with('success', 'Paiement de ' . number_format($data['amount'], 0, ',', ' ') . ' FCFA enregistré.');
    }

    public function destroy(Customer $customer)
    {
        if ($customer->credit_balance > 0) {
            return back()->with('error', 'Impossible de supprimer un client avec un solde crédit en cours.');
        }
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Client supprimé.');
    }

}
