<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
    <div class="flex items-center gap-3 mb-5">
        <div class="w-9 h-9 bg-blue-100 rounded-lg flex items-center justify-center">
            <i class="fas fa-sms text-blue-600"></i>
        </div>
        <div>
            <h3 class="text-sm font-semibold text-gray-800">Configuration SMS</h3>
            <p class="text-xs text-gray-400">Paramètres d'envoi de SMS (OTP, alertes, notifications)</p>
        </div>
    </div>

    <form method="POST" action="{{ route('super-admin.settings.update', 'sms') }}" class="space-y-4">
        @csrf

        {{-- Activer/Désactiver --}}
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div>
                <p class="text-sm font-medium text-gray-700">Activer les SMS</p>
                <p class="text-xs text-gray-400">Les SMS seront envoyés aux utilisateurs</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="sms_enabled" value="1" class="sr-only peer"
                    {{ ($settings['sms_enabled'] ?? '0') === '1' ? 'checked' : '' }}>
                <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-blue-600
                            peer-checked:after:translate-x-full after:content-[''] after:absolute
                            after:top-0.5 after:left-0.5 after:bg-white after:rounded-full
                            after:h-5 after:w-5 after:transition-all"></div>
            </label>
        </div>

        {{-- Fournisseur --}}
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Fournisseur SMS</label>
            <select name="sms_provider"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @foreach(['twilio' => 'Twilio', 'vonage' => 'Vonage (Nexmo)', 'infobip' => 'Infobip',
                           'orange' => 'Orange SMS API', 'mtn' => 'MTN SMS API', 'africas_talking' => "Africa's Talking"] as $val => $lbl)
                <option value="{{ $val }}" {{ ($settings['sms_provider'] ?? '') === $val ? 'selected' : '' }}>
                    {{ $lbl }}
                </option>
                @endforeach
            </select>
        </div>

        {{-- API Key --}}
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Clé API (API Key / Account SID)</label>
            <div class="relative">
                <input type="password" id="sms_api_key" name="sms_api_key"
                       value="{{ $settings['sms_api_key'] ?? '' }}"
                       placeholder="{{ isset($settings['sms_api_key']) && $settings['sms_api_key'] ? '••••••••••••' : '' }}"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 pr-10">
                <button type="button" onclick="toggleSecret(this, 'sms_api_key')"
                        class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600">
                    <i class="fas fa-eye text-sm"></i>
                </button>
            </div>
            <p class="text-xs text-gray-400 mt-0.5">Laissez vide pour conserver la valeur actuelle</p>
        </div>

        {{-- API Secret --}}
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Secret API (Auth Token / API Secret)</label>
            <div class="relative">
                <input type="password" id="sms_api_secret" name="sms_api_secret"
                       value="{{ $settings['sms_api_secret'] ?? '' }}"
                       placeholder="{{ isset($settings['sms_api_secret']) && $settings['sms_api_secret'] ? '••••••••••••' : '' }}"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 pr-10">
                <button type="button" onclick="toggleSecret(this, 'sms_api_secret')"
                        class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600">
                    <i class="fas fa-eye text-sm"></i>
                </button>
            </div>
        </div>

        {{-- Sender ID --}}
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Identifiant expéditeur (Sender ID)</label>
            <input type="text" name="sms_sender_id"
                   value="{{ $settings['sms_sender_id'] ?? '' }}"
                   placeholder="Business ou +22960000000"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <p class="text-xs text-gray-400 mt-0.5">Nom ou numéro affiché comme expéditeur (max 11 caractères alphanumériques)</p>
        </div>

        <div class="pt-3 border-t border-gray-100 flex justify-end">
            <button type="submit"
                    class="bg-blue-600 text-white px-5 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition">
                <i class="fas fa-save mr-1.5"></i> Enregistrer
            </button>
        </div>
    </form>
</div>
