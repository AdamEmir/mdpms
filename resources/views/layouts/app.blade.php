<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $title ?? 'Dashboard') &mdash; {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @livewireStyles
</head>
<body class="h-full bg-slate-50 text-slate-900 antialiased">
    <div class="min-h-full">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
                <div class="flex items-center gap-8">
                    <a href="{{ route('dashboard') }}" wire:navigate class="text-lg font-semibold tracking-tight text-slate-900">MDPMS</a>
                    <nav class="hidden gap-1 md:flex" aria-label="Primary">
                        @php
                            $links = [
                                ['route' => 'dashboard',          'label' => 'Dashboard'],
                                ['route' => 'departments.index',  'label' => 'Departments'],
                                ['route' => 'employees.index',    'label' => 'Employees'],
                                ['route' => 'payroll.run',        'label' => 'Run Payroll'],
                                ['route' => 'payroll.history',    'label' => 'History'],
                            ];
                        @endphp
                        @foreach ($links as $link)
                            @php
                                $active = request()->routeIs($link['route']) || request()->routeIs(str_replace('.index', '.*', $link['route']));
                            @endphp
                            <a href="{{ route($link['route']) }}" wire:navigate
                               class="rounded-md px-3 py-2 text-sm font-medium {{ $active ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                {{ $link['label'] }}
                            </a>
                        @endforeach
                    </nav>
                </div>
                <div class="flex items-center gap-3">
                    <span class="hidden text-sm text-slate-500 sm:inline">{{ auth()->user()?->name }}</span>
                    @auth
                        <livewire:auth.logout-button />
                    @endauth
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            {{-- Flash banners are rendered by each page (Livewire components include resources/views/partials/flash-messages.blade.php). --}}
            {{ $slot }}
        </main>
    </div>

    <script>
        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (!form.matches('form[data-confirm]')) return;
            if (form.dataset.confirmed === 'yes') return;
            e.preventDefault();
            const isDestructive = form.dataset.confirm === 'delete';
            Swal.fire({
                title: form.dataset.confirmTitle || 'Are you sure?',
                text: form.dataset.confirmText || 'Please confirm to continue.',
                icon: isDestructive ? 'warning' : 'question',
                showCancelButton: true,
                confirmButtonColor: isDestructive ? '#dc2626' : '#0f172a',
                confirmButtonText: form.dataset.confirmButton || (isDestructive ? 'Yes, delete' : 'Confirm'),
            }).then((result) => {
                if (result.isConfirmed) {
                    form.dataset.confirmed = 'yes';
                    form.submit();
                }
            });
        });
    </script>
    @livewireScripts
</body>
</html>
