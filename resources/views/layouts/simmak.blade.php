<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SIMMAK-PPKP DKI Jakarta')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-sim-screen bg-sim-surface text-sim-ink antialiased"
      x-data="{ sidebarOpen: false, userMenuOpen: false, clinicMenuOpen: false }"
      @click.outside="userMenuOpen = false; clinicMenuOpen = false">

    <!-- Skip link for accessibility -->
    <a href="#main-content" class="sim-skip-link">Langsung ke konten utama</a>

    <div class="sim-app">
        <!-- Sidebar (Desktop) -->
        <aside class="sim-sidebar"
               :class="{ 'sim-sidebar-open': sidebarOpen }"
               aria-label="Navigasi utama">
            <div class="sim-sidebar-brand">
                <div class="sim-wordmark">
                    <span class="sim-wordmark-line1">SIMMAK-PPKP DKI Jakarta</span>
                </div>
            </div>

            <nav class="sim-sidebar-nav" aria-label="Menu navigasi">
                @foreach (\App\Support\SimmakMenu::forUser(auth()->user()) as $item)
                    @php
                        $active = request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*');
                        $href = route($item['route']);
                    @endphp
                    <a href="{{ $href }}"
                       class="sim-sidebar-link {{ $active ? 'sim-sidebar-link-active' : '' }}"
                       aria-current="{{ $active ? 'page' : 'false' }}"
                       @click="sidebarOpen = false">
                        @include('partials.icons.'.$item['icon'])
                        <span class="sim-sidebar-link-label">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>

            <div class="sim-sidebar-footer">
                @php
                    $sidebarUser = auth()->user();
                    $sidebarClinic = request()->attributes->get('active_clinic');
                    $sidebarRole = \App\Support\RoleLabel::for($sidebarUser?->getRoleNames()->first());
                    $sidebarAllClinics = $sidebarUser?->viewsAllClinics() && ! $sidebarClinic;
                @endphp
                @if ($sidebarClinic || $sidebarAllClinics)
                    <div class="sim-sidebar-clinic">
                        <div class="sim-sidebar-clinic-label">Klinik Aktif</div>
                        <div class="sim-sidebar-clinic-name">
                            {{ $sidebarClinic?->name ?? 'Semua Klinik' }}
                        </div>
                        <div class="sim-sidebar-clinic-role">{{ $sidebarRole }}</div>
                    </div>
                @endif

                <div class="sim-sidebar-institution">
                    <div class="sim-sidebar-institution-name">PPKP Provinsi DKI Jakarta</div>
                </div>
            </div>
        </aside>

        <!-- Sidebar Mobile Overlay -->
        <div class="sim-sidebar-overlay fixed inset-0 z-[60] bg-black/40 lg:hidden"
             :class="{ 'sim-sidebar-overlay-open': sidebarOpen }"
             @click="sidebarOpen = false"
             x-cloak
             aria-hidden="true"></div>

        <!-- Main Content -->
        <div class="sim-main">
            <!-- Header -->
            <header class="sim-header" role="banner">
                <div class="sim-header-inner">
                    <div class="sim-header-start">
                        <button type="button"
                                class="sim-menu-button lg:hidden"
                                @click="sidebarOpen = !sidebarOpen"
                                :aria-label="sidebarOpen ? 'Tutup navigasi' : 'Buka navigasi'"
                                :aria-expanded="sidebarOpen.toString()">
                            <svg class="sim-menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>
                        <div class="sim-header-page">
                            <h1 class="sim-header-title">@yield('page-title', 'Dashboard')</h1>
                            @hasSection('page-subtitle')
                                <p class="sim-header-subtitle">@yield('page-subtitle')</p>
                            @endif
                        </div>
                    </div>

                    <div class="sim-header-center hidden sm:flex items-center gap-6">
                        <div class="sim-header-date">
                            <svg class="sim-header-date-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span>{{ now()->translatedFormat('d F Y') }}</span>
                        </div>
                    </div>

                    <div class="sim-header-end">
                        <!-- Clinic Context Picker -->
                        @php
                            $contextUser = auth()->user();
                            // Manajemen pusat / administrator sistem: seluruh klinik
                            // yang dapat diakses. Klinik: hanya yang di-assign.
                            $activeClinic = request()->attributes->get('active_clinic');
                            $clinics = \App\Models\Clinic::forUserClinics($contextUser)
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->get();
                        @endphp
                        @if ($clinics && $clinics->count() > 1)
                            <div class="sim-header-clinic-picker" @click="clinicMenuOpen = !clinicMenuOpen">
                                <div class="sim-header-clinic-picker-trigger flex items-center gap-2 px-3 py-2 rounded-md border border-sim-border hover:bg-sim-hover transition-colors">
                                    <svg class="sim-header-clinic-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12.258c1.625-1.775 4.025-2.8 6.438-2.8 2.42 0 4.84.995 6.488 2.766M3 12.258a60.014 60.014 0 000-3.758M7.5 19.758a22.5 22.5 0 015.253-2.358M7.5 19.758C8.53 22.826 12 24 12 24s3.47-1.174 4.5-4.242M12 24c3.094 0 5.969-.781 8.25-2.172m-8.25 0a52.6 52.6 0 01-3.758-3.273M12 15.75a3 3 0 11-6 0 3 3 0 016 0zm0 0a60.6 60.6 0 003.758 3.273M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <div>
                                        <div class="sim-header-clinic-label">
                                            {{ $activeClinic?->name ?? ($contextUser?->viewsAllClinics() ? 'Semua Klinik' : 'Pilih Klinik') }}
                                        </div>
                                        <div class="sim-header-clinic-code text-[10px]">
                                            {{ $activeClinic?->code ?? ($contextUser?->viewsAllClinics() ? 'LINTAS' : '—') }}
                                        </div>
                                    </div>
                                    <svg class="sim-header-clinic-caret" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </div>
                                <div class="sim-header-clinic-menu absolute z-sim-dropdown mt-1 w-56 bg-white border border-sim-border rounded-md shadow-sim-lg py-2 hidden lg:hidden"
                                     x-show="clinicMenuOpen"
                                     x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="transform opacity-0 scale-95"
                                     x-transition:enter-end="transform opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="transform opacity-100 scale-100"
                                     x-transition:leave-end="transform opacity-0 scale-95">
                                    @if ($contextUser?->viewsAllClinics())
                                        <a href="{{ route('clinics.switch-all') }}"
                                           class="flex items-center gap-3 px-4 py-2.5 text-sm text-sim-ink hover:bg-sim-hover {{ $activeClinic ? '' : 'bg-sim-active text-sim-primary' }} block">
                                            {{ $activeClinic ? '○' : '●' }}
                                            <span>Semua Klinik</span>
                                            <span class="ml-auto text-[10px] text-sim-muted uppercase">LINTAS</span>
                                        </a>
                                    @endif
                                    @foreach ($clinics as $clinic)
                                        <a href="{{ route('clinics.switch', $clinic) }}"
                                           class="flex items-center gap-3 px-4 py-2.5 text-sm text-sim-ink hover:bg-sim-hover {{ $activeClinic?->id === $clinic->id ? 'bg-sim-active text-sim-primary' : '' }} block">
                                            {{ $activeClinic?->id === $clinic->id ? '●' : '○' }}
                                            <span>{{ $clinic->name }}</span>
                                            <span class="ml-auto text-[10px] text-sim-muted uppercase">{{ $clinic->code }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Notifications -->
                        <button type="button" class="sim-header-button" aria-label="Notifikasi">
                            <svg class="sim-header-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                            <span class="sim-notification-badge">3</span>
                        </button>

                        <!-- Help -->
                        <a href="#" class="sim-header-button hidden sm:flex items-center gap-2 text-sm text-sim-muted hover:text-sim-primary transition-colors" aria-label="Bantuan">
                            <svg class="sim-header-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Bantuan
                        </a>

                        <!-- User Profile -->
                        <div class="sim-header-user" @click="userMenuOpen = !userMenuOpen">
                            <div class="sim-header-user-avatar flex h-9 w-9 items-center justify-center rounded-md bg-sim-primary text-white text-sm font-semibold">
                                {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                            </div>
                            <div class="sim-header-user-menu absolute right-0 z-sim-dropdown mt-2 w-48 bg-white border border-sim-border rounded-md shadow-sim-lg py-2 hidden"
                                 x-show="userMenuOpen"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95">
                                <div class="px-4 py-2 border-b border-sim-border">
                                    <div class="text-sm font-medium text-sim-ink">{{ auth()->user()->name }}</div>
                                    <div class="text-xs text-sim-muted">{{ \App\Support\RoleLabel::for(auth()->user()->getRoleNames()->first()) }}</div>
                                </div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="flex w-full items-center gap-3 px-4 py-2.5 text-sm text-sim-ink hover:bg-sim-hover rounded-none text-left">
                                        <svg class="sim-header-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                        </svg>
                                        Keluar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Breadcrumb -->
            @hasSection('breadcrumb')
                <nav class="sim-breadcrumb" aria-label="Breadcrumb">
                    <ol class="sim-breadcrumb-list">
                        @yield('breadcrumb')
                    </ol>
                </nav>
            @endif

            <!-- Main Content Area -->
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