@extends('layouts.auth')
@section('title', __('auth.register'))
@section('content')
<h2 class="text-xl font-bold text-gray-800 mb-6">{{ __('auth.register') }}</h2>

@if($errors->any())
<div class="mb-4 bg-red-50 border border-red-200 text-red-600 rounded-lg p-3 text-sm">
    <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form method="POST" action="{{ route('register') }}" class="space-y-4">
    @csrf
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
        <input type="text" name="name" value="{{ old('name') }}" required
               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.email') }}</label>
        <input type="email" name="email" value="{{ old('email') }}" required
               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.password') }}</label>
        <input type="password" name="password" required minlength="8"
               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.confirm_password') }}</label>
        <input type="password" name="password_confirmation" required
               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
    </div>
    <button type="submit"
            class="w-full bg-indigo-600 text-white py-2.5 rounded-lg font-medium hover:bg-indigo-700 transition">
        {{ __('auth.register_button') }}
    </button>
</form>
<p class="text-center text-sm text-gray-500 mt-6">
    {{ __('auth.have_account') }} <a href="{{ route('login') }}" class="text-indigo-600 hover:underline">{{ __('auth.login') }}</a>
</p>
@endsection
