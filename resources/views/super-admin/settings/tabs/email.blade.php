<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
    <div class="flex items-center gap-3 mb-5">
        <div class="w-9 h-9 bg-purple-100 rounded-lg flex items-center justify-center">
            <i class="fas fa-envelope text-purple-600"></i>
        </div>
        <div>
            <h3 class="text-sm font-semibold text-gray-800">Configuration Email</h3>
            <p class="text-xs text-gray-400">Serveur d'envoi d'emails transactionnels</p>
        </div>
    </div>

    <form method="POST" action="{{ route('super-admin.settings.update', 'email') }}" class="space-y-4">
        @csrf

        {{-- Toggle --}}
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div>
                <p class="text-sm font-medium text-gray-700">Activer l'envoi d'emails</p>
                <p class="text-xs text-gray-400">Emails de bienvenue, factures, alertes</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="mail_enabled" value="1" class="sr-only peer"
                    {{ ($settings['mail_enabled'] ?? '0') === '1' ? 'checked' : '' }}>
                <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-purple-600
                            peer-checked:after:translate-x-full after:content-[''] after:absolute
                            after:top-0.5 after:left-0.5 after:bg-white after:rounded-full
                            after:h-5 after:w-5 after:transition-all"></div>
            </label>
        </div>

        {{-- Driver --}}
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Protocole / Service</label>
            <select name="mail_driver" id="mailDriver" onchange="toggleSmtpFields()"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @foreach(['smtp' => 'SMTP', 'mailgun' => 'Mailgun', 'sendgrid' => 'SendGrid', 'ses' => 'Amazon SES'] as $val => $lbl)
                <option value="{{ $val }}" {{ ($settings['mail_driver'] ?? 'smtp') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>

        {{-- SMTP fields --}}
        <div id="smtpFields" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Hôte SMTP</label>
                    <input type="text" name="mail_host"
                           value="{{ $settings['mail_host'] ?? '' }}"
                           placeholder="smtp.gmail.com"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Port</label>
                    <input type="number" name="mail_port"
                           value="{{ $settings['mail_port'] ?? '587' }}"
                           placeholder="587"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Nom d'utilisateur</label>
                    <input type="text" name="mail_username"
                           value="{{ $settings['mail_username'] ?? '' }}"
                           placeholder="user@example.com"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Mot de passe</label>
                    <div class="relative">
                        <input type="password" id="mail_password" name="mail_password"
                               value="{{ $settings['mail_password'] ?? '' }}"
                               placeholder="{{ isset($settings['mail_password']) && $settings['mail_password'] ? '••••••••' : '' }}"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 pr-9">
                        <button type="button" onclick="toggleSecret(this, 'mail_password')"
                                class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600">
                            <i class="fas fa-eye text-sm"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Chiffrement</label>
                <select name="mail_encryption"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="tls" {{ ($settings['mail_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' }}>TLS (STARTTLS — port 587)</option>
                    <option value="ssl" {{ ($settings['mail_encryption'] ?? '') === 'ssl' ? 'selected' : '' }}>SSL (port 465)</option>
                    <option value=""   {{ ($settings['mail_encryption'] ?? '') === '' ? 'selected' : '' }}>Aucun (port 25)</option>
                </select>
            </div>
        </div>

        {{-- From --}}
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Adresse expéditeur</label>
                <input type="email" name="mail_from_address"
                       value="{{ $settings['mail_from_address'] ?? '' }}"
                       placeholder="noreply@business.com"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Nom expéditeur</label>
                <input type="text" name="mail_from_name"
                       value="{{ $settings['mail_from_name'] ?? config('app.name') }}"
                       placeholder="{{ config('app.name') }}"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        <div class="pt-3 border-t border-gray-100 flex justify-end">
            <button type="submit"
                    class="bg-purple-600 text-white px-5 py-2 rounded-lg text-sm font-semibold hover:bg-purple-700 transition">
                <i class="fas fa-save mr-1.5"></i> Enregistrer
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function toggleSmtpFields() {
    const driver = document.getElementById('mailDriver').value;
    const fields = document.getElementById('smtpFields');
    fields.style.display = driver === 'smtp' ? 'block' : 'none';
}
document.addEventListener('DOMContentLoaded', toggleSmtpFields);
</script>
@endpush
