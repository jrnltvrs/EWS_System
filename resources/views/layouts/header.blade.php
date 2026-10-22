<!DOCTYPE html>
<html lang="en" class="h-full overflow-hidden">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Muntinlupa City - EWS</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Local custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Pusher JavaScript client (for real-time WebSocket) -->
    <script src="https://js.pusher.com/7.0/pusher.min.js"></script>

    <!-- Laravel Echo (uses Pusher) -->
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.11.3/dist/echo.iife.js"></script>

    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <!-- Tailwind (theme in tailwind.config.js), SweetAlert helpers, ApexCharts -->
    @vite(['resources/js/app.js'])

    {{-- socket.io is loaded only by the pages that talk to the Pi (controller, settings/portSetup). --}}
    @auth
        <script>
            window.serialToken = @json(config('services.serial.token'));
            window.serialBackendUrl = @json(config('services.serial.url'));
            window.sirenApiConfigured = @json(\App\Models\ApiConnection::where('name', 'Siren')->where('revoked', false)->exists());
        </script>
    @endauth
</head>

<body>
