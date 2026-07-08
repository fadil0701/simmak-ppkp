<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SIMMAK PPKP')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased">
    <div class="flex min-h-screen">
        <aside class="hidden w-64 flex-shrink-0 bg-gradient-to-b from-teal-800 to-teal-900 text-white lg:block">
            <div class="border-b border-teal-700 px-6 py-5">
                <div class="text-lg font-bold tracking-wide">SIMMAK PPKP</div>
                <div class="text-xs text-teal-200">Mutu · Audit · Akreditasi</div>
            </div>
            <nav class="space-y-1 p-4 text-sm">
                @php
                    $links = [
                        ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => '📊'],
                        ['route' => '#', 'label' => 'Dokumen (DMS)', 'icon' => '📄'],
                        ['route' => '#', 'label' => 'Audit Internal', 'icon' => '🔍'],
                        ['route' => '#', 'label' => 'CAPA', 'icon' => '✅'],
                        ['route' => '#', 'label' => 'Manajemen Risiko', 'icon' => '⚠️'],
                        ['route' => '#', 'label' => 'Akreditasi', 'icon' => '🏥'],
                        ['route' => '#', 'label' => 'Program Mutu', 'icon' => '📈'],
                    ];
                @endphp
                @foreach ($links as $link)
                    <a href="{{ $link['route'] === '#' ? '#' : route($link['route']) }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 transition hover:bg-teal-700/60 {{ request()->routeIs($link['route']) ? 'bg-teal-700' : '' }}">
                        <span>{{ $link['icon'] }}</span>
                        <span>{{ $link['label'] }}</span>
                    </a>
                @endforeach
            </nav>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="border-b border-slate-200 bg-white px-4 py-3 shadow-sm lg:px-8">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h1 class="text-lg font-semibold text-slate-900">@yield('page-title', 'Dashboard')</h1>
                        @if(isset($clinic) && $clinic)
                            <p class="text-sm text-slate-500">{{ $clinic->name }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="hidden text-sm text-slate-600 sm:inline">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-200">
                                Keluar
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="flex-1 p-4 lg:p-8">
                @if (session('status'))
                    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                        {{ session('status') }}
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
    @livewireScripts
</body>
</html>
