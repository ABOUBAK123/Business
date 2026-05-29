@extends('layouts.app')
@section('title', 'Top articles')
@section('page-title', 'Top articles vendus')

@section('content')
<div class="flex items-center gap-4 mb-4 flex-wrap">
    <form class="flex gap-2 flex-wrap">
        <input type="date" name="date_from" value="{{ request('date_from', now()->startOfMonth()->toDateString()) }}"
               class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
        <input type="date" name="date_to" value="{{ request('date_to', now()->toDateString()) }}"
               class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
        <select name="branch_id" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            <option value="">Toutes succursales</option>
            @foreach($branches as $b)
                <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
            @endforeach
        </select>
        <select name="limit" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            <option value="10" {{ request('limit', 10) == 10 ? 'selected' : '' }}>Top 10</option>
            <option value="20" {{ request('limit') == 20 ? 'selected' : '' }}>Top 20</option>
            <option value="50" {{ request('limit') == 50 ? 'selected' : '' }}>Top 50</option>
        </select>
        <button class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">Appliquer</button>
    </form>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700">Par quantité vendue</h3>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">#</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Article</th>
                    <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500">Qté</th>
                    <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500">CA TTC</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($topByQuantity as $i => $item)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2.5">
                        <span class="w-6 h-6 rounded-full inline-flex items-center justify-center text-xs font-bold
                              {{ $i === 0 ? 'bg-yellow-100 text-yellow-700' : ($i === 1 ? 'bg-gray-100 text-gray-600' : ($i === 2 ? 'bg-orange-100 text-orange-600' : 'bg-gray-50 text-gray-500')) }}">
                            {{ $i + 1 }}
                        </span>
                    </td>
                    <td class="px-4 py-2.5">
                        <p class="font-medium text-gray-800">{{ Str::limit($item->designation, 25) }}</p>
                        <p class="text-xs text-gray-400 font-mono">{{ $item->reference }}</p>
                    </td>
                    <td class="px-4 py-2.5 text-right font-semibold text-gray-800">
                        {{ number_format($item->total_qty, 0) }} {{ $item->unit }}
                    </td>
                    <td class="px-4 py-2.5 text-right text-gray-700">
                        {{ number_format($item->total_revenue, 0, ',', ' ') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700">Par chiffre d'affaires</h3>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">#</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Article</th>
                    <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500">CA TTC</th>
                    <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500">Marge</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($topByRevenue as $i => $item)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2.5">
                        <span class="w-6 h-6 rounded-full inline-flex items-center justify-center text-xs font-bold
                              {{ $i === 0 ? 'bg-yellow-100 text-yellow-700' : ($i === 1 ? 'bg-gray-100 text-gray-600' : ($i === 2 ? 'bg-orange-100 text-orange-600' : 'bg-gray-50 text-gray-500')) }}">
                            {{ $i + 1 }}
                        </span>
                    </td>
                    <td class="px-4 py-2.5">
                        <p class="font-medium text-gray-800">{{ Str::limit($item->designation, 25) }}</p>
                    </td>
                    <td class="px-4 py-2.5 text-right font-semibold text-gray-800">
                        {{ number_format($item->total_revenue, 0, ',', ' ') }}
                    </td>
                    <td class="px-4 py-2.5 text-right">
                        <span class="text-xs {{ $item->profit_margin >= 20 ? 'text-green-600' : 'text-yellow-600' }}">
                            {{ number_format($item->profit_margin, 1) }}%
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
