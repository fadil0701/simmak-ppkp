<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SIMMAK — Audit Internal Klinik')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-sim-screen bg-sim-surface text-sim-ink antialiased">
    <div class="sim-app">
        @include('partials.sidebar')

        <div class="sim-main">
            @include('partials.header')

            <main id="main-content" class="sim-content" role="main">
                @if (session('status'))
                    <div class="sim-alert sim-alert-success mb-6">
                        <span class="sim-alert-icon">✓</span>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif
                @if (session('error'))
                    <div class="sim-alert sim-alert-error mb-6">
                        <span class="sim-alert-icon">!</span>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif
                @yield('content')
            </main>

            @include('partials.footer')
        </div>
    </div>

    @livewireScripts
</body>
</html>