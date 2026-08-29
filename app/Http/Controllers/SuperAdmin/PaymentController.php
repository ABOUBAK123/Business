<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPayment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = SubscriptionPayment::with(['tenant', 'subscription.plan'])
            ->withoutGlobalScopes();

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->provider) {
            $query->where('provider', $request->provider);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('reference', 'like', "%{$request->search}%")
                  ->orWhereHas('tenant', fn ($t) => $t->where('shop_name', 'like', "%{$request->search}%"));
            });
        }

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $stats = [
            'total_success' => (clone $query)->where('status', 'success')->sum('amount'),
            'count_success' => (clone $query)->where('status', 'success')->count(),
            'count_failed' => (clone $query)->where('status', 'failed')->count(),
            'count_pending' => (clone $query)->where('status', 'pending')->count(),
        ];

        $payments = $query->latest()->paginate(25)->withQueryString();

        $providers = SubscriptionPayment::withoutGlobalScopes()
            ->whereNotNull('provider')
            ->distinct()
            ->orderBy('provider')
            ->pluck('provider');

        return view('super-admin.payments.index', compact('payments', 'stats', 'providers'));
    }
}
