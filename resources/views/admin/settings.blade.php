@extends('layouts.app')
@section('title', 'Paramètres')
@section('page-title', 'Paramètres de l\'application')
@section('content')

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-4">
            @csrf @method('PUT')
            @foreach($settings as $key => $setting)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    {{ $setting->description ?? $key }}
                </label>
                <input type="text" name="settings[{{ $key }}]" value="{{ old("settings.$key", $setting->value) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            @endforeach

            @if($settings->isEmpty())
            <p class="text-gray-400 text-sm text-center py-4">Aucun paramètre configuré.</p>
            @endif

            <div class="pt-3">
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-indigo-700">
                    <i class="fas fa-save mr-2"></i>Enregistrer les paramètres
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
