@extends('layouts.app')

@section('title', 'Demander un retrait')
@section('page-title', 'Demander un retrait')

@section('content')
<div class="max-w-lg">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <div class="bg-blue-50 rounded-lg p-4 mb-5 flex items-center justify-between">
            <span class="text-sm text-blue-700 font-medium">Solde à retirer</span>
            <span class="text-xl font-bold text-blue-800">{{ number_format($availableBalance, 0, ',', ' ') }} XOF</span>
        </div>

        <form method="POST" action="{{ route('commissioner.payouts.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Moyen de paiement</label>
                <select name="method" required
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Choisir...</option>
                    <option value="orange_money" {{ old('method') === 'orange_money' ? 'selected' : '' }}>Orange Money</option>
                    <option value="mtn_momo" {{ old('method') === 'mtn_momo' ? 'selected' : '' }}>MTN Mobile Money</option>
                    <option value="wave" {{ old('method') === 'wave' ? 'selected' : '' }}>Wave</option>
                    <option value="moov_money" {{ old('method') === 'moov_money' ? 'selected' : '' }}>Moov Money</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Numéro Mobile Money</label>
                <input type="text" name="phone_number" required
                       value="{{ old('phone_number') }}"
                       placeholder="Ex : 0700000000"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Note (optionnel)</label>
                <textarea name="commissioner_notes" rows="2"
                          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('commissioner_notes') }}</textarea>
            </div>

            <p class="text-[11px] text-gray-400">
                Votre demande sera traitée manuellement par l'équipe Business. Le paiement sera envoyé sur le numéro indiqué ci-dessus une fois validée.
            </p>

            <div class="pt-3 border-t border-gray-100 flex justify-end gap-2">
                <a href="{{ route('commissioner.payouts') }}"
                   class="border border-gray-200 text-gray-600 px-4 py-2 rounded-lg text-sm hover:bg-gray-50 transition">
                    Annuler
                </a>
                <button type="submit"
                        class="bg-blue-600 text-white px-5 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition">
                    <i class="fas fa-paper-plane mr-1.5"></i> Envoyer la demande
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
