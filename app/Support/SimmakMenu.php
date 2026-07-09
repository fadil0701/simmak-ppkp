<?php

namespace App\Support;

use App\Models\User;

class SimmakMenu
{
    /**
     * @return array<int, array{route: string, label: string, icon: string, permission: string|null}>
     */
    public static function items(): array
    {
        return [
            ['route' => 'dashboard', 'label' => 'Dashboard Eksekutif', 'icon' => 'grid', 'permission' => 'dashboard.executive'],
            ['route' => 'clinic.dashboard', 'label' => 'Dashboard Klinik', 'icon' => 'building', 'permission' => 'dashboard.clinic'],
            ['route' => 'audit.index', 'label' => 'Audit Internal', 'icon' => 'search', 'permission' => 'audit.view'],
            ['route' => 'accreditation.index', 'label' => 'Akreditasi', 'icon' => 'award', 'permission' => 'accreditation.view'],
            ['route' => 'findings.index', 'label' => 'Temuan', 'icon' => 'alert', 'permission' => 'audit.view'],
            ['route' => 'capa.index', 'label' => 'CAPA', 'icon' => 'tool', 'permission' => 'capa.view'],
            ['route' => 'risk.index', 'label' => 'Risiko', 'icon' => 'chart', 'permission' => 'risk.view'],
            ['route' => 'quality.index', 'label' => 'Program Mutu', 'icon' => 'chart', 'permission' => 'quality.view'],
            ['route' => 'dms.index', 'label' => 'Dokumen', 'icon' => 'file', 'permission' => 'dms.view'],
            ['route' => 'reports.index', 'label' => 'Pelaporan', 'icon' => 'chart', 'permission' => 'dashboard.executive'],
            ['route' => 'users.index', 'label' => 'Pengguna', 'icon' => 'users', 'permission' => 'users.view'],
            ['route' => 'settings.index', 'label' => 'Pengaturan', 'icon' => 'settings', 'permission' => 'settings.view'],
        ];
    }

    public static function homeRouteFor(User $user): string
    {
        if ($user->can('dashboard.executive')) {
            return route('dashboard');
        }

        if ($user->hasRole('tim-akreditasi') && $user->can('accreditation.view')) {
            return route('accreditation.index');
        }

        if ($user->can('dashboard.clinic')) {
            return route('clinic.dashboard');
        }

        if ($user->can('audit.view')) {
            return route('audit.index');
        }

        if ($user->can('dms.view')) {
            return route('dms.index');
        }

        return route('dashboard');
    }

    /**
     * @return array<int, array{route: string, label: string, icon: string, permission: string|null}>
     */
    public static function forUser(?User $user): array
    {
        if (! $user) {
            return [];
        }

        return collect(self::items())
            ->filter(function (array $item) use ($user) {
                if (! $item['permission']) {
                    return true;
                }

                return $user->can($item['permission']);
            })
            ->filter(fn (array $item) => \Illuminate\Support\Facades\Route::has($item['route']))
            ->values()
            ->all();
    }
}
