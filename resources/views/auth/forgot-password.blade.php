@extends('layouts.auth')
@section('title', __('auth.forgot_password'))
@section('content')
<div class="mb-2">
    <a href="{{ route('login') }}" class="inline-flex items-center gap-1 text-sm text-gray-400 hover:text-indigo-600 transition">
        <i class="fa-solid fa-arrow-left text-xs"></i> {{ __('buttons.back') }}
    </a>
</div>
<div class="flex items-center justify-between mb-2">
    <h2 class="text-xl font-bold text-gray-800">{{ __('auth.password_reset') }}</h2>
    <form method="POST" action="{{ route('lang.switch', app()->getLocale()) }}" id="lang-form-forgot">
        @csrf
        <select onchange="document.getElementById('lang-form-forgot').action='{{ url('/lang') }}/'+this.value; document.getElementById('lang-form-forgot').submit();"
                class="text-sm border border-gray-300 rounded-lg px-2 py-1.5 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white cursor-pointer">
            <option value="fr" {{ app()->getLocale() === 'fr' ? 'selected' : '' }}>🇫🇷 FR</option>
            <option value="en" {{ app()->getLocale() === 'en' ? 'selected' : '' }}>🇬🇧 EN</option>
            <option value="es" {{ app()->getLocale() === 'es' ? 'selected' : '' }}>🇪🇸 ES</option>
            <option value="pt" {{ app()->getLocale() === 'pt' ? 'selected' : '' }}>🇵🇹 PT</option>
            <option value="ar" {{ app()->getLocale() === 'ar' ? 'selected' : '' }}>🇸🇦 AR</option>
        </select>
    </form>
</div>
<p class="text-sm text-gray-500 mb-6">{{ __('auth.reset_link_sent') }}</p>

@if(session('success'))
<div class="mb-4 bg-green-50 border border-green-200 text-green-700 rounded-lg p-3 text-sm flex items-center gap-2">
    <i class="fa-solid fa-circle-check text-green-500"></i> {{ session('success') }}
</div>
@endif

@if($errors->any())
<div class="mb-4 bg-red-50 border border-red-200 text-red-600 rounded-lg p-3 text-sm">
    {{ $errors->first() }}
</div>
@endif

<form method="POST" action="{{ route('password.email') }}" class="space-y-4">
    @csrf
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.email') }}</label>
        <input type="email" name="email" value="{{ old('email') }}" required autofocus
               placeholder="your@email.com"
               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
    </div>
    <button type="submit"
            class="w-full bg-indigo-600 text-white py-2.5 rounded-lg font-medium hover:bg-indigo-700 transition">
        {{ __('auth.send_reset_link') }}
    </button>
</form>
@endsection
