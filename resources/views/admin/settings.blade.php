@extends('layouts.app')
@section('title', 'Paramètres')
@section('page-title', 'Paramètres de l\'application')
@section('content')

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="mb-6">
            <h2 class="text-base font-semibold text-gray-800 mb-1">Seuils de securite templates</h2>
            <p class="text-xs text-gray-500">Ajustez les seuils du mode safe sans SQL manuel ni redeploiement.</p>
        </div>

        @if($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-4">
            @csrf @method('PUT')

            @foreach($thresholdSettings as $key => $meta)
            @php
                $current = old($key, $settings[$key]->value ?? $meta['default']);
            @endphp
            <div class="rounded-lg border border-blue-100 bg-blue-50/40 p-4">
                <label for="{{ $key }}" class="block text-sm font-semibold text-gray-800 mb-1">
                    {{ $meta['label'] }}
                </label>
                <p class="text-xs text-gray-500 mb-2">{{ $meta['description'] }}</p>
                <input
                    id="{{ $key }}"
                    type="number"
                    name="{{ $key }}"
                    min="{{ $meta['min'] }}"
                    max="{{ $meta['max'] }}"
                    step="1"
                    value="{{ $current }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                <p class="text-[11px] text-gray-500 mt-1">Bornes: {{ number_format($meta['min'], 0, ',', ' ') }} - {{ number_format($meta['max'], 0, ',', ' ') }} bytes. Defaut: {{ number_format($meta['default'], 0, ',', ' ') }}.</p>
            </div>
            @endforeach

            <hr class="border-gray-100">

            <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Autres parametres</p>
            @foreach($settings as $key => $setting)
            @continue(isset($thresholdSettings[$key]))
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    {{ $setting->description ?? $key }}
                </label>
                <input type="text" name="{{ $key }}" value="{{ old($key, $setting->value) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            @endforeach

            @if($settings->isEmpty())
            <p class="text-gray-400 text-sm text-center py-4">Aucun paramètre configuré.</p>
            @endif

            <div class="pt-3 flex flex-wrap items-center gap-2">
                <button
                    type="submit"
                    name="restore_defrag_defaults"
                    value="1"
                    class="bg-amber-100 text-amber-800 px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-amber-200 border border-amber-200"
                >
                    <i class="fas fa-rotate-left mr-2"></i>Restaurer valeurs recommandees
                </button>
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-indigo-700">
                    <i class="fas fa-save mr-2"></i>Enregistrer les paramètres
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
