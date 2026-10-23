<aside class="hidden w-64 flex-shrink-0 flex-col bg-navy-950 text-white lg:flex">
    <div class="border-b border-white/10 px-5 py-5">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-600 text-lg font-bold">+</div>
            <div>
                <div class="text-sm font-bold tracking-wide">SIMMAK PPKP</div>
                <div class="text-[10px] text-slate-400">Mutu · Audit · Akreditasi</div>
            </div>
        </div>
    </div>

    <nav class="flex-1 space-y-3 overflow-y-auto p-3 text-sm">
        @foreach (\App\Support\SimmakMenu::groupsForUser(auth()->user()) as $group)
            <div>
                <div class="px-3 pb-1 text-[10px] font-semibold uppercase tracking-wider text-slate-500">{{ $group['label'] }}</div>
                @foreach ($group['items'] as $item)
                    @php
                        $active = request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*');
                        $href = route($item['route']);
                    @endphp
                    <a href="{{ $href }}"
                       class="simmak-sidebar-link {{ $active ? 'simmak-sidebar-link-active' : '' }}">
                        @include('partials.icons.'.$item['icon'])
                        <span class="flex-1">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        @endforeach
    </nav>

    @php
        $sidebarClinic = auth()->user()?->defaultClinic;
        $sidebarRole = \App\Support\RoleLabel::for(auth()->user()?->getRoleNames()->first());
    @endphp
    @if ($sidebarClinic)
        <div class="border-t border-white/10 p-4">
            <div class="rounded-lg bg-white/5 px-3 py-2.5">
                <div class="text-[10px] uppercase tracking-wide text-slate-500">Klinik Aktif</div>
                <div class="mt-0.5 truncate text-sm font-medium text-white">{{ $sidebarClinic->name }}</div>
                <div class="mt-2 truncate text-xs text-slate-400">{{ auth()->user()->name }}</div>
                <div class="text-[10px] text-teal-400">{{ $sidebarRole }}</div>
            </div>
        </div>
    @endif

    <div class="border-t border-white/10 p-4 text-[10px] text-slate-500">
        <div class="mb-2 font-medium text-slate-400">Kementerian Kesehatan RI</div>
        <div>PPKP Provinsi DKI Jakarta</div>
    </div>
</aside>
