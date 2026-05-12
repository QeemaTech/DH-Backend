<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/svg+xml" href="{{ setting('app_icon') ? asset('storage/' . setting('app_icon')) : asset('dashboard/assets/icons/favicon.svg') }}">
    <link rel="icon" type="image/png" href="{{ setting('app_icon') ? asset('storage/' . setting('app_icon')) : asset('dashboard/assets/icons/favicon.png') }}">

    <title>@yield('title', __('Payment')) - {{ setting('app_name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="{{ app()->getLocale() === 'ar' ? 'ar' : 'en' }}">
    <main>
        @yield('content')
    </main>
</body>
</html>

