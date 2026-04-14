<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        
        <!-- SEO Setup -->
        <meta name="description" content="Sistem Penanganan Customer Service: Keluhan, Monitoring OOS, dan Tracking Pengiriman. Tingkatkan kepuasan pelanggan dengan pelayanan responsif.">
        <meta name="keywords" content="customer service, sistem tracking, manajemen keluhan, dashboard cs">
        <meta name="author" content="Tim Customer Service">
        <meta name="theme-color" content="#4f46e5">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @routes
        @vite(['resources/js/app.ts'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
