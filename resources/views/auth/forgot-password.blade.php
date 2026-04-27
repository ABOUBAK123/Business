@extends('layouts.auth')
@section('title', __('auth.forgot_password'))
@section('content')
<div class="mb-2">
    <a href="{{ route('login') }}" class="inline-flex items-center gap-1 text-sm text-gray-400 hover:text-indigo-600 transition">
        <i class="fa-solid fa-arrow-left text-xs"></i> {{ __('buttons.back') }}
    </a>
</div>
<h2 class="text-xl font-bold text-gray-800 mb-2">{{ __('auth.password_reset') }}</h2>
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
