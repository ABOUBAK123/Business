<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\CommissionPayout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CommissionPayoutController extends Controller
{
    public function index(Request $request): View
    {
        $query = CommissionPayout::with('commissioner');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $payouts = $query->latest()->paginate(20)->withQueryString();
        $pendingCount = CommissionPayout::where('status', 'pending')->count();

        return view('super-admin.commission-payouts.index', compact('payouts', 'pendingCount'));
    }

    public function markPaid(Request $request, CommissionPayout $commissionPayout): RedirectResponse
    {
        $request->validate(['admin_notes' => 'nullable|string|max:255']);

        if (! $commissionPayout->isPending()) {
            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        DB::transaction(function () use ($commissionPayout, $request) {
            $commissionPayout->update([
                'status' => 'paid',
                'admin_notes' => $request->admin_notes,
                'processed_by' => auth()->id(),
                'processed_at' => now(),
            ]);

            Commission::where('commission_payout_id', $commissionPayout->id)
                ->update(['status' => 'paid', 'paid_at' => now()]);
        });

        return back()->with('success', 'Retrait marqué comme payé.');
    }

    public function reject(Request $request, CommissionPayout $commissionPayout): RedirectResponse
    {
        $request->validate(['admin_notes' => 'required|string|max:255']);

        if (! $commissionPayout->isPending()) {
            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        DB::transaction(function () use ($commissionPayout, $request) {
            $commissionPayout->update([
                'status' => 'rejected',
                'admin_notes' => $request->admin_notes,
                'processed_by' => auth()->id(),
                'processed_at' => now(),
            ]);

            // Release the linked commissions back into the available pool.
            Commission::where('commission_payout_id', $commissionPayout->id)
                ->update(['commission_payout_id' => null]);
        });

        return back()->with('success', 'Demande de retrait rejetée.');
    }
}
