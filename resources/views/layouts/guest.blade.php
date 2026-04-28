<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MDPMS') &mdash; {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-50 text-slate-900 antialiased">
    <main class="flex min-h-full items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <div class="mb-8 text-center">
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">{{ config('app.name') }}</h1>
                <p class="mt-1 text-sm text-slate-500">Multi-Department Payroll Management System</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                @yield('content')
            </div>
        </div>
    </main>
</body>
</html>
