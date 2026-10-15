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

    <!-- Leaflet geocoder control -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>

    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Pusher JavaScript client (for real-time WebSocket) -->
    <script src="https://js.pusher.com/7.0/pusher.min.js"></script>

    <!-- Laravel Echo (uses Pusher) -->
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.11.3/dist/echo.iife.js"></script>

    <!-- Pickr color picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/themes/classic.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/pickr.min.js"></script>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'smart-blue': '#0466c8ff',
                        'sapphire': '#0353a4ff',
                        'regal-navy': '#023e7dff',
                        'prussian-blue': '#003595',
                        'prussian-blue-2': '#003595',
                        'prussian-blue-3': '#003595',
                        'twilight-indigo': '#003595',
                        'blue-slate': '#5c677dff',
                        'slate-grey': '#5c677dff',
                        'lavender-grey': '#5c677dff',   

                        'munti-blue-0': '#003595',
                        'munti-blue-1': '#5c677dff',
                        'munti-blue-2': '#2196f3',
                        'munti-yellow-0': '#ffb702ff',
                        'munti-yellow-1': '#fccc3dff',
                        'munti-yellow-2': '#ffd966',
                        'munti-red-0': '#e2261b',
                        'munti-green-0': '#d9f99d', 
                        'munti-green-1': '#84cc16', 
                        'munti-white-0': '#e0e0e0',
                        'munti-black-0': '#080808', 
                    }
                }
            }
        };
    </script>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <!-- iro.js color picker (alternative to Pickr) -->
    <script src="https://cdn.jsdelivr.net/npm/@jaames/iro@5"></script>

    <!-- Alpine.js for reactive UI components (deferred loading) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Socket.io client -->
    <script src="http://172.0.6.250:3123/socket.io/socket.io.js"></script>
    @auth
        <script>window.serialToken = @json(config('services.serial.token'));</script>
    @endauth

    @livewireStyles
</head>

<body>
    @vite(['resources/js/app.js'])
