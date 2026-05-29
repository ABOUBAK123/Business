<div class="space-y-4">

    {{-- ── Orange Money ─────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background:#ff6600">
                <i class="fas fa-mobile-alt text-white"></i>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-800">Orange Money</h3>
                <p class="text-xs text-gray-400">API Orange Money — paiements et collectes</p>
            </div>
        </div>

        <form method="POST" action="{{ route('super-admin.settings.update', 'mobile_money') }}" class="space-y-4">
            @csrf
            {{-- hidden: only Orange keys in this form --}}
            <input type="hidden" name="_provider" value="orange_money">

            {{-- passthrough other providers' existing values --}}
            <input type="hidden" name="mtn_momo_enabled"     value="{{ $settings['mtn_momo_enabled']     ?? '0' }}">
            <input type="hidden" name="mtn_momo_api_key"     value="{{ $settings['mtn_momo_api_key']     ?? '' }}">
            <input type="hidden" name="mtn_momo_api_secret"  value="{{ $settings['mtn_momo_api_secret']  ?? '' }}">
            <input type="hidden" name="mtn_momo_subscription_key" value="{{ $settings['mtn_momo_subscription_key'] ?? '' }}">
            <input type="hidden" name="wave_enabled"         value="{{ $settings['wave_enabled']         ?? '0' }}">
            <input type="hidden" name="wave_api_key"         value="{{ $settings['wave_api_key']         ?? '' }}">
            <input type="hidden" name="wave_business_id"     value="{{ $settings['wave_business_id']     ?? '' }}">
            <input type="hidden" name="moov_money_enabled"   value="{{ $settings['moov_money_enabled']   ?? '0' }}">
            <input type="hidden" name="moov_money_api_key"   value="{{ $settings['moov_money_api_key']   ?? '' }}">
            <input type="hidden" name="moov_money_merchant_code" value="{{ $settings['moov_money_merchant_code'] ?? '' }}">

            <div class="flex items-center justify-between p-3 bg-orange-50 rounded-lg">
                <p class="text-sm font-medium text-gray-700">Activer Orange Money</p>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="orange_money_enabled" value="1" class="sr-only peer"
                        {{ ($settings['orange_money_enabled'] ?? '0') === '1' ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute
                                after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"
                         style="--tw-ring-color:transparent"
                         x-bind:class="{}"></div>
                </label>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">API Key (Client ID)</label>
                    <input type="text" name="orange_money_api_key"
                           value="{{ $settings['orange_money_api_key'] ?? '' }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Secret API</label>
                    <div class="relative">
                        <input type="password" id="om_secret" name="orange_money_api_secret"
                               value="{{ $settings['orange_money_api_secret'] ?? '' }}"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 pr-9">
                        <button type="button" onclick="toggleSecret(this, 'om_secret')"
                                class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600">
                            <i class="fas fa-eye text-sm"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Merchant ID</label>
                <input type="text" name="orange_money_merchant_id"
                       value="{{ $settings['orange_money_merchant_id'] ?? '' }}"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="pt-3 border-t border-gray-100 flex justify-end">
                <button type="submit"
                        class="text-white px-5 py-2 rounded-lg text-sm font-semibold transition"
                        style="background:#ff6600">
                    <i class="fas fa-save mr-1.5"></i> Enregistrer Orange Money
                </button>
            </div>
        </form>
    </div>

    {{-- ── MTN MoMo ──────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-9 h-9 bg-yellow-400 rounded-lg flex items-center justify-center">
                <i class="fas fa-mobile-alt text-gray-900"></i>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-800">MTN Mobile Money</h3>
                <p class="text-xs text-gray-400">API MTN MoMo (Collections & Disbursements)</p>
            </div>
        </div>

        <form method="POST" action="{{ route('super-admin.settings.update', 'mobile_money') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="_provider" value="mtn_momo">
            <input type="hidden" name="orange_money_enabled"    value="{{ $settings['orange_money_enabled']    ?? '0' }}">
            <input type="hidden" name="orange_money_api_key"    value="{{ $settings['orange_money_api_key']    ?? '' }}">
            <input type="hidden" name="orange_money_api_secret" value="{{ $settings['orange_money_api_secret'] ?? '' }}">
            <input type="hidden" name="orange_money_merchant_id" value="{{ $settings['orange_money_merchant_id'] ?? '' }}">
            <input type="hidden" name="wave_enabled"             value="{{ $settings['wave_enabled']             ?? '0' }}">
            <input type="hidden" name="wave_api_key"             value="{{ $settings['wave_api_key']             ?? '' }}">
            <input type="hidden" name="wave_business_id"         value="{{ $settings['wave_business_id']         ?? '' }}">
            <input type="hidden" name="moov_money_enabled"       value="{{ $settings['moov_money_enabled']       ?? '0' }}">
            <input type="hidden" name="moov_money_api_key"       value="{{ $settings['moov_money_api_key']       ?? '' }}">
            <input type="hidden" name="moov_money_merchant_code" value="{{ $settings['moov_money_merchant_code'] ?? '' }}">

            <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                <p class="text-sm font-medium text-gray-700">Activer MTN MoMo</p>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="mtn_momo_enabled" value="1" class="sr-only peer"
                        {{ ($settings['mtn_momo_enabled'] ?? '0') === '1' ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-yellow-400
                                peer-checked:after:translate-x-full after:content-[''] after:absolute
                                after:top-0.5 after:left-0.5 after:bg-white after:rounded-full
                                after:h-5 after:w-5 after:transition-all"></div>
                </label>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">API Key (User ID)</label>
                    <input type="text" name="mtn_momo_api_key"
                           value="{{ $settings['mtn_momo_api_key'] ?? '' }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">API Secret (API Key)</label>
                    <div class="relative">
                        <input type="password" id="mtn_secret" name="mtn_momo_api_secret"
                               value="{{ $settings['mtn_momo_api_secret'] ?? '' }}"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 pr-9">
                        <button type="button" onclick="toggleSecret(this, 'mtn_secret')"
                                class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600">
                            <i class="fas fa-eye text-sm"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Subscription Key (Ocp-Apim)</label>
                <input type="text" name="mtn_momo_subscription_key"
                       value="{{ $settings['mtn_momo_subscription_key'] ?? '' }}"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="pt-3 border-t border-gray-100 flex justify-end">
                <button type="submit"
                        class="bg-yellow-400 text-gray-900 px-5 py-2 rounded-lg text-sm font-semibold hover:bg-yellow-500 transition">
                    <i class="fas fa-save mr-1.5"></i> Enregistrer MTN MoMo
                </button>
            </div>
        </form>
    </div>

    {{-- ── Wave ──────────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-9 h-9 bg-blue-500 rounded-lg flex items-center justify-center">
                <i class="fas fa-water text-white"></i>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-800">Wave</h3>
                <p class="text-xs text-gray-400">API Wave — paiements en ligne</p>
            </div>
        </div>

        <form method="POST" action="{{ route('super-admin.settings.update', 'mobile_money') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="_provider" value="wave">
            <input type="hidden" name="orange_money_enabled"    value="{{ $settings['orange_money_enabled']    ?? '0' }}">
            <input type="hidden" name="orange_money_api_key"    value="{{ $settings['orange_money_api_key']    ?? '' }}">
            <input type="hidden" name="orange_money_api_secret" value="{{ $settings['orange_money_api_secret'] ?? '' }}">
            <input type="hidden" name="orange_money_merchant_id" value="{{ $settings['orange_money_merchant_id'] ?? '' }}">
            <input type="hidden" name="mtn_momo_enabled"        value="{{ $settings['mtn_momo_enabled']        ?? '0' }}">
            <input type="hidden" name="mtn_momo_api_key"        value="{{ $settings['mtn_momo_api_key']        ?? '' }}">
            <input type="hidden" name="mtn_momo_api_secret"     value="{{ $settings['mtn_momo_api_secret']     ?? '' }}">
            <input type="hidden" name="mtn_momo_subscription_key" value="{{ $settings['mtn_momo_subscription_key'] ?? '' }}">
            <input type="hidden" name="moov_money_enabled"      value="{{ $settings['moov_money_enabled']      ?? '0' }}">
            <input type="hidden" name="moov_money_api_key"      value="{{ $settings['moov_money_api_key']      ?? '' }}">
            <input type="hidden" name="moov_money_merchant_code" value="{{ $settings['moov_money_merchant_code'] ?? '' }}">

            <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                <p class="text-sm font-medium text-gray-700">Activer Wave</p>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="wave_enabled" value="1" class="sr-only peer"
                        {{ ($settings['wave_enabled'] ?? '0') === '1' ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-blue-500
                                peer-checked:after:translate-x-full after:content-[''] after:absolute
                                after:top-0.5 after:left-0.5 after:bg-white after:rounded-full
                                after:h-5 after:w-5 after:transition-all"></div>
                </label>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Clé secrète API</label>
                    <div class="relative">
                        <input type="password" id="wave_api_key" name="wave_api_key"
                               value="{{ $settings['wave_api_key'] ?? '' }}"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 pr-9">
                        <button type="button" onclick="toggleSecret(this, 'wave_api_key')"
                                class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600">
                            <i class="fas fa-eye text-sm"></i>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Business ID</label>
                    <input type="text" name="wave_business_id"
                           value="{{ $settings['wave_business_id'] ?? '' }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="pt-3 border-t border-gray-100 flex justify-end">
                <button type="submit"
                        class="bg-blue-500 text-white px-5 py-2 rounded-lg text-sm font-semibold hover:bg-blue-600 transition">
                    <i class="fas fa-save mr-1.5"></i> Enregistrer Wave
                </button>
            </div>
        </form>
    </div>

    {{-- ── Moov Money ────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-9 h-9 bg-red-600 rounded-lg flex items-center justify-center">
                <i class="fas fa-mobile-alt text-white"></i>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-800">Moov Money</h3>
                <p class="text-xs text-gray-400">API Moov Money (Flooz)</p>
            </div>
        </div>

        <form method="POST" action="{{ route('super-admin.settings.update', 'mobile_money') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="_provider" value="moov_money">
            <input type="hidden" name="orange_money_enabled"    value="{{ $settings['orange_money_enabled']    ?? '0' }}">
            <input type="hidden" name="orange_money_api_key"    value="{{ $settings['orange_money_api_key']    ?? '' }}">
            <input type="hidden" name="orange_money_api_secret" value="{{ $settings['orange_money_api_secret'] ?? '' }}">
            <input type="hidden" name="orange_money_merchant_id" value="{{ $settings['orange_money_merchant_id'] ?? '' }}">
            <input type="hidden" name="mtn_momo_enabled"         value="{{ $settings['mtn_momo_enabled']         ?? '0' }}">
            <input type="hidden" name="mtn_momo_api_key"         value="{{ $settings['mtn_momo_api_key']         ?? '' }}">
            <input type="hidden" name="mtn_momo_api_secret"      value="{{ $settings['mtn_momo_api_secret']      ?? '' }}">
            <input type="hidden" name="mtn_momo_subscription_key" value="{{ $settings['mtn_momo_subscription_key'] ?? '' }}">
            <input type="hidden" name="wave_enabled"              value="{{ $settings['wave_enabled']              ?? '0' }}">
            <input type="hidden" name="wave_api_key"              value="{{ $settings['wave_api_key']              ?? '' }}">
            <input type="hidden" name="wave_business_id"          value="{{ $settings['wave_business_id']          ?? '' }}">

            <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                <p class="text-sm font-medium text-gray-700">Activer Moov Money</p>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="moov_money_enabled" value="1" class="sr-only peer"
                        {{ ($settings['moov_money_enabled'] ?? '0') === '1' ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-red-600
                                peer-checked:after:translate-x-full after:content-[''] after:absolute
                                after:top-0.5 after:left-0.5 after:bg-white after:rounded-full
                                after:h-5 after:w-5 after:transition-all"></div>
                </label>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Clé API</label>
                    <div class="relative">
                        <input type="password" id="moov_api_key" name="moov_money_api_key"
                               value="{{ $settings['moov_money_api_key'] ?? '' }}"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 pr-9">
                        <button type="button" onclick="toggleSecret(this, 'moov_api_key')"
                                class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600">
                            <i class="fas fa-eye text-sm"></i>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Code marchand</label>
                    <input type="text" name="moov_money_merchant_code"
                           value="{{ $settings['moov_money_merchant_code'] ?? '' }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="pt-3 border-t border-gray-100 flex justify-end">
                <button type="submit"
                        class="bg-red-600 text-white px-5 py-2 rounded-lg text-sm font-semibold hover:bg-red-700 transition">
                    <i class="fas fa-save mr-1.5"></i> Enregistrer Moov Money
                </button>
            </div>
        </form>
    </div>

</div>
