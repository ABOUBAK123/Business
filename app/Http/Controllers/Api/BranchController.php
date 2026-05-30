<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $branches = Branch::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn($b) => [
                'id'      => $b->id,
                'name'    => $b->name,
                'address' => $b->address,
                'city'    => $b->city,
                'phone'   => $b->phone,
                'is_main' => $b->is_main,
            ]);

        return response()->json(['data' => $branches]);
    }
}
