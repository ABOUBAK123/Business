<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR expiré</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen py-8 px-4">
<div class="max-w-xl mx-auto bg-white rounded-2xl shadow p-6 text-center">
    <h1 class="text-xl font-bold text-gray-800">QR Code non valide</h1>
    <p class="text-sm text-gray-600 mt-2">La période d'émargement est expirée pour la réunion: <strong>{{ $meeting->title }}</strong>.</p>
</div>
</body>
</html>
