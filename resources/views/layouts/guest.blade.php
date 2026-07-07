<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-900 via-blue-800 to-blue-900 min-h-screen flex items-center justify-center font-sans">

    <div class="w-full max-w-md px-4">

        {{-- Logo --}}
        <div class="flex flex-col items-center mb-8">
            <div class="w-16 h-16 bg-yellow-400 rounded-2xl flex items-center justify-center shadow-lg mb-4">
                <i class="fas fa-store text-blue-900 text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-white">{{ config('app.name') }}</h1>
            <p class="text-blue-300 text-sm mt-1">Gestion de quincaillerie</p>
        </div>

        {{-- Card --}}
        <div class="bg-white rounded-2xl shadow-2xl px-8 py-8">
            {{ $slot }}
        </div>

        <p class="text-center text-blue-400 text-xs mt-6">
            &copy; {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.
        </p>
    </div>

    @include('components.contact-footer')

</body>
</html>
