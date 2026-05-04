@extends('layouts.auth')
@section('title', __('auth.login'))
@section('content')
<div class="flex items-center justify-between mb-6">
    <h2 class="text-xl font-bold text-gray-800">{{ __('auth.login') }}</h2>
    <form method="POST" action="{{ route('lang.switch', app()->getLocale()) }}" id="lang-form-login">
        @csrf
        <select onchange="document.getElementById('lang-form-login').action='{{ url('/lang') }}/'+this.value; document.getElementById('lang-form-login').submit();"
                class="text-sm border border-gray-300 rounded-lg px-2 py-1.5 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white cursor-pointer">
            <option value="fr" {{ app()->getLocale() === 'fr' ? 'selected' : '' }}>🇫🇷 FR</option>
            <option value="en" {{ app()->getLocale() === 'en' ? 'selected' : '' }}>🇬🇧 EN</option>
            <option value="es" {{ app()->getLocale() === 'es' ? 'selected' : '' }}>🇪🇸 ES</option>
            <option value="pt" {{ app()->getLocale() === 'pt' ? 'selected' : '' }}>🇵🇹 PT</option>
            <option value="ar" {{ app()->getLocale() === 'ar' ? 'selected' : '' }}>🇸🇦 AR</option>
        </select>
    </form>
</div>

@if($errors->any())
<div class="mb-4 bg-red-50 border border-red-200 text-red-600 rounded-lg p-3 text-sm">
    {{ $errors->first() }}
</div>
@endif

<form method="POST" action="{{ route('login') }}" class="space-y-4">
    @csrf
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.email') }}</label>
        <input type="email" name="email" value="{{ old('email') }}" required
               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.password') }}</label>
        <input type="password" name="password" required
               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
    </div>
    <div class="flex items-center justify-between">
        <label class="flex items-center gap-2 text-sm text-gray-600">
            <input type="checkbox" name="remember" class="rounded"> {{ __('auth.remember_me') }}
        </label>
        <a href="{{ route('password.request') }}" class="text-sm text-indigo-600 hover:underline">{{ __('auth.forgot_password') }}</a>
    </div>
    <button type="submit"
            class="w-full bg-indigo-600 text-white py-2.5 rounded-lg font-medium hover:bg-indigo-700 transition">
        {{ __('auth.login_button') }}
    </button>
</form>
@endsection
