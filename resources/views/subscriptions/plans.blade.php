<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" translate="no" class="h-full bg-page-cream">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ __('Planes') }} · {{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body id="app" class="font-body antialiased h-full">
        {{-- Phase 6a mounts the Vue Planes page here, fed by the JSON endpoint. --}}
        <plans-page
            :plans-json-url="'{{ $json_url }}'"
            :notify-payment-url="'{{ route('subscriptions.notify-payment') }}'"
            :register-url="'{{ route('subscriptions.register') }}'"
            :dashboard-url="'{{ route('backoffice.dashboard.index') }}'"
        />
    </body>
</html>
