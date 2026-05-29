<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
    <div class="flex items-center gap-3 mb-5">
        <div class="w-9 h-9 bg-green-100 rounded-lg flex items-center justify-center">
            <i class="fab fa-whatsapp text-green-600 text-lg"></i>
        </div>
        <div>
            <h3 class="text-sm font-semibold text-gray-800">Configuration WhatsApp</h3>
            <p class="text-xs text-gray-400">Envoi de messages WhatsApp via l'API Business</p>
        </div>
    </div>

    <form method="POST" action="{{ route('super-admin.settings.update', 'whatsapp') }}" class="space-y-4">
        @csrf

        {{-- Toggle --}}
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div>
                <p class="text-sm font-medium text-gray-700">Activer WhatsApp</p>
                <p class="text-xs text-gray-400">Envoi de messages WhatsApp aux clients et boutiques</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="whatsapp_enabled" value="1" class="sr-only peer"
                    {{ ($settings['whatsapp_enabled'] ?? '0') === '1' ? 'checked' : '' }}>
                <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-green-500
                            peer-checked:after:translate-x-full after:content-[''] after:absolute
                            after:top-0.5 after:left-0.5 after:bg-white after:rounded-full
                            after:h-5 after:w-5 after:transition-all"></div>
            </label>
        </div>

        {{-- Fournisseur --}}
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Fournisseur</label>
            <select name="whatsapp_provider"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="meta" {{ ($settings['whatsapp_provider'] ?? '') === 'meta' ? 'selected' : '' }}>
                    Meta (WhatsApp Business API officielle)
                </option>
                <option value="twilio" {{ ($settings['whatsapp_provider'] ?? '') === 'twilio' ? 'selected' : '' }}>
                    Twilio (WhatsApp via Twilio)
                </option>
            </select>
        </div>

        {{-- Token --}}
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Token d'accès permanent</label>
            <div class="relative">
                <input type="password" id="whatsapp_token" name="whatsapp_token"
                       value="{{ $settings['whatsapp_token'] ?? '' }}"
                       placeholder="{{ isset($settings['whatsapp_token']) && $settings['whatsapp_token'] ? '••••••••••••' : '' }}"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 pr-10">
                <button type="button" onclick="toggleSecret(this, 'whatsapp_token')"
                        class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600">
                    <i class="fas fa-eye text-sm"></i>
                </button>
            </div>
            <p class="text-xs text-gray-400 mt-0.5">Laissez vide pour conserver la valeur actuelle</p>
        </div>

        {{-- Phone Number ID (Meta) --}}
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Phone Number ID <span class="text-gray-400">(Meta)</span></label>
            <input type="text" name="whatsapp_phone_id"
                   value="{{ $settings['whatsapp_phone_id'] ?? '' }}"
                   placeholder="123456789012345"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <p class="text-xs text-gray-400 mt-0.5">Disponible dans le tableau de bord Meta for Developers</p>
        </div>

        {{-- From number (Twilio) --}}
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Numéro expéditeur <span class="text-gray-400">(Twilio : whatsapp:+1...)</span></label>
            <input type="text" name="whatsapp_from_number"
                   value="{{ $settings['whatsapp_from_number'] ?? '' }}"
                   placeholder="whatsapp:+15005550006"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        {{-- Webhook secret --}}
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Secret Webhook</label>
            <div class="relative">
                <input type="password" id="whatsapp_webhook_secret" name="whatsapp_webhook_secret"
                       value="{{ $settings['whatsapp_webhook_secret'] ?? '' }}"
                       placeholder="{{ isset($settings['whatsapp_webhook_secret']) && $settings['whatsapp_webhook_secret'] ? '••••••••••••' : '' }}"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 pr-10">
                <button type="button" onclick="toggleSecret(this, 'whatsapp_webhook_secret')"
                        class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600">
                    <i class="fas fa-eye text-sm"></i>
                </button>
            </div>
        </div>

        {{-- Info webhook URL --}}
        <div class="bg-blue-50 rounded-lg px-4 py-3 text-xs text-blue-700">
            <i class="fas fa-info-circle mr-1"></i>
            URL Webhook à renseigner dans Meta for Developers :
            <code class="bg-blue-100 px-1.5 py-0.5 rounded font-mono ml-1">{{ url('/api/webhooks/whatsapp') }}</code>
        </div>

        <div class="pt-3 border-t border-gray-100 flex justify-end">
            <button type="submit"
                    class="bg-green-600 text-white px-5 py-2 rounded-lg text-sm font-semibold hover:bg-green-700 transition">
                <i class="fas fa-save mr-1.5"></i> Enregistrer
            </button>
        </div>
    </form>
</div>
