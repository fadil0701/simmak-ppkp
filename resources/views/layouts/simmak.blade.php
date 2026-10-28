<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SIMMAK-PPKP DKI Jakarta')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-sim-screen bg-sim-surface text-sim-ink antialiased"
      x-data="{ sidebarOpen: false, userMenuOpen: false, clinicMenuOpen: false }">

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
                @foreach (\App\Support\SimmakMenu::groupsForUser(auth()->user()) as $group)
                    <div class="sim-sidebar-group">
                        <div class="sim-sidebar-group-label">{{ $group['label'] }}</div>
                        @foreach ($group['items'] as $item)
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
                    </div>
                @endforeach
            </nav>

            <div class="sim-sidebar-footer">
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
                        @if ($clinics->isNotEmpty() || $contextUser?->viewsAllClinics())
                            @php
                                $canSwitchClinic = $clinics->count() > 1 || $contextUser?->viewsAllClinics();
                                $clinicLabel = $activeClinic?->name ?? ($contextUser?->viewsAllClinics() ? 'Semua Klinik' : 'Pilih Klinik');
                                $clinicCode = $activeClinic?->code ?? ($contextUser?->viewsAllClinics() ? 'Lintas' : '—');
                            @endphp
                            <div class="sim-header-clinic-picker" @click.outside="clinicMenuOpen = false">
                                <button type="button"
                                        class="sim-header-clinic-picker-trigger flex items-center gap-2 rounded-md border border-sim-border px-3 py-2 hover:bg-sim-hover"
                                        @if ($canSwitchClinic) @click.stop="clinicMenuOpen = !clinicMenuOpen" @endif
                                        @if ($canSwitchClinic) aria-haspopup="true" :aria-expanded="clinicMenuOpen.toString()" @endif
                                        @unless ($canSwitchClinic) disabled @endunless>
                                    <svg class="sim-header-clinic-icon text-sim-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                    <div class="min-w-0 text-left">
                                        <div class="sim-header-clinic-label">{{ $clinicLabel }}</div>
                                        <div class="sim-header-clinic-code text-[10px]">{{ $clinicCode }}</div>
                                    </div>
                                    @if ($canSwitchClinic)
                                        <svg class="sim-header-clinic-caret transition-transform" :class="{ 'rotate-180': clinicMenuOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    @endif
                                </button>
                                @if ($canSwitchClinic)
                                    <div class="absolute right-0 z-50 mt-1 max-h-80 w-72 overflow-y-auto rounded-md border border-sim-border bg-white py-1 shadow-sim-lg"
                                         x-show="clinicMenuOpen"
                                         x-cloak
                                         role="menu">
                                        @if ($contextUser?->viewsAllClinics())
                                            <form method="POST" action="{{ route('clinics.switch-all') }}">
                                                @csrf
                                                <button type="submit" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm hover:bg-sim-hover {{ $activeClinic ? 'text-sim-ink' : 'bg-sim-active text-sim-primary' }}">
                                                    <span class="min-w-0 flex-1 truncate">Semua Klinik</span>
                                                    <span class="text-[10px] uppercase text-sim-muted">Lintas</span>
                                                </button>
                                            </form>
                                        @endif
                                        @foreach ($clinics as $clinic)
                                            <form method="POST" action="{{ route('clinics.switch', $clinic) }}">
                                                @csrf
                                                <button type="submit" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm hover:bg-sim-hover {{ $activeClinic?->id === $clinic->id ? 'bg-sim-active text-sim-primary' : 'text-sim-ink' }}">
                                                    <span class="min-w-0 flex-1 truncate">{{ $clinic->name }}</span>
                                                    <span class="text-[10px] uppercase text-sim-muted">{{ $clinic->code }}</span>
                                                </button>
                                            </form>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif

                        <!-- Notifications -->
                        <button type="button" class="sim-header-button" aria-label="Notifikasi">
                            <span class="relative inline-flex">
                                <svg class="sim-header-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                </svg>
                                <span class="sim-notification-badge">3</span>
                            </span>
                        </button>

                        <!-- Help -->
                        <a href="#" class="sim-header-button hidden sm:flex items-center gap-2 text-sm text-sim-muted hover:text-sim-primary transition-colors" aria-label="Bantuan">
                            <svg class="sim-header-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Bantuan
                        </a>

                        <!-- User Profile -->
                        <div class="sim-header-user" @click.outside="userMenuOpen = false">
                            <button type="button"
                                    class="sim-header-user-trigger"
                                    @click.stop="userMenuOpen = !userMenuOpen"
                                    :aria-expanded="userMenuOpen.toString()"
                                    aria-haspopup="true"
                                    aria-label="Menu akun">
                                <div class="sim-header-user-avatar flex h-9 w-9 items-center justify-center rounded-md bg-sim-primary text-white text-sm font-semibold">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                                </div>
                                <div class="hidden md:block text-left">
                                    <div class="text-sm font-medium text-sim-ink leading-tight">{{ auth()->user()->name }}</div>
                                    <div class="text-[10px] uppercase tracking-wide text-sim-muted">{{ \App\Support\RoleLabel::for(auth()->user()->getRoleNames()->first()) }}</div>
                                </div>
                                <svg class="h-4 w-4 text-sim-muted transition-transform" :class="{ 'rotate-180': userMenuOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div class="sim-header-user-menu absolute right-0 z-50 mt-2 w-60 origin-top-right rounded-md border border-sim-border bg-white py-1 shadow-sim-lg"
                                 x-show="userMenuOpen"
                                 x-cloak
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 role="menu">
                                <div class="border-b border-sim-border px-4 py-2.5">
                                    <div class="text-sm font-medium text-sim-ink">{{ auth()->user()->name }}</div>
                                    <div class="truncate text-xs text-sim-muted">{{ auth()->user()->email }}</div>
                                </div>
                                <a href="{{ route('profile.edit') }}" class="flex w-full items-center gap-3 px-4 py-2.5 text-sm text-sim-ink hover:bg-sim-hover" role="menuitem" class="sim-btn sim-btn-edit">
                                    <span class="inline-flex text-sim-muted">@include('partials.icons.pencil')</span>
                                    Edit Profil
                                </a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-sim-danger hover:bg-sim-hover" role="menuitem">
                                        <span class="inline-flex">@include('partials.icons.log-out')</span>
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