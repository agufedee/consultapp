<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased">
        <div class="flex min-h-screen items-center justify-center">
            <div class="text-center">
                <h1 class="text-4xl font-bold">ConsultApp</h1>
                <p class="mt-4 text-lg text-gray-600">Sistema de gestión de consultas médicas</p>
                <a href="/consultorio" class="mt-6 inline-block rounded-lg bg-primary-600 px-6 py-3 text-white transition hover:bg-primary-700">
                    Ir al Panel
                </a>
            </div>
        </div>
    </body>
</html>
