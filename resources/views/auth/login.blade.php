@extends('layouts.auth')
@section('title', __('auth.login'))
@section('content')
<h2 class="text-xl font-bold text-gray-800 mb-6">{{ __('auth.login') }}</h2>

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
