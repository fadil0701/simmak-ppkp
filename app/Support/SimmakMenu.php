<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

class SimmakMenu
{
    /**
     * Sidebar dikelompokkan sesuai alur Audit Internal.
     *
     * @return array<int, array{label: string, items: list<array{route: string, label: string, icon: string, permission: string|list<string>|null}>}>
     */
    public static function groups(): array
    {
        return [
            [
                'label' => 'Ringkasan',
                'items' => [
                    ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'grid', 'permission' => ['dashboard.executive', 'audit.dashboard.view']],
                    ['route' => 'clinic.dashboard', 'label' => 'Dashboard Klinik', 'icon' => 'building', 'permission' => 'dashboard.clinic'],
                ],
            ],
            [
                'label' => 'Master Data',
                'items' => [
                    ['route' => 'clinics.index', 'label' => 'Master Unit/Klinik', 'icon' => 'building', 'permission' => 'clinics.view'],
                    ['route' => 'employees.index', 'label' => 'Master Pegawai', 'icon' => 'users', 'permission' => 'employees.view'],
                    ['route' => 'positions.index', 'label' => 'Master Jabatan', 'icon' => 'award', 'permission' => 'positions.view'],
                ],
            ],
            [
                'label' => 'Audit Internal',
                'items' => [
                    ['route' => 'audit.standards.index', 'label' => 'Standar & Instrumen', 'icon' => 'book-open', 'permission' => 'audit.standard.view'],
                    ['route' => 'audit.programs.index', 'label' => 'Program Audit', 'icon' => 'clipboard-list', 'permission' => 'audit.program.view'],
                    ['route' => 'audit.assignments.index', 'label' => 'Penugasan Auditor', 'icon' => 'user-group', 'permission' => 'audit.assignment.view'],
                    ['route' => 'audit.executions.index', 'label' => 'Pelaksanaan Audit', 'icon' => 'clipboard-check', 'permission' => 'audit.execution.view'],
                    ['route' => 'findings.index', 'label' => 'Temuan Audit', 'icon' => 'alert-triangle', 'permission' => 'audit.finding.view'],
                    ['route' => 'capa.index', 'label' => 'CAPA', 'icon' => 'tool', 'permission' => 'capa.view'],
                    ['route' => 'audit.reports.index', 'label' => 'Laporan Audit', 'icon' => 'file-text', 'permission' => 'audit.report.view'],
                    ['route' => 'audit.evidence.index', 'label' => 'Arsip Bukti', 'icon' => 'paperclip', 'permission' => 'audit.evidence.view'],
                ],
            ],
            [
                'label' => 'Pengaturan',
                'items' => [
                    ['route' => 'fiscal-years.index', 'label' => 'Tahun Anggaran', 'icon' => 'settings', 'permission' => 'settings.manage'],
                    ['route' => 'users.index', 'label' => 'Pengguna', 'icon' => 'users', 'permission' => 'users.view'],
                ],
            ],
        ];
    }

    /**
     * @return array<int, array{route: string, label: string, icon: string, permission: string|list<string>|null}>
     */
    public static function items(): array
    {
        return collect(self::groups())->pluck('items')->flatten(1)->all();
    }

    public static function homeRouteFor(User $user): string
    {
        if ($user->can('dashboard.executive') || $user->can('audit.dashboard.view')) {
            return route('dashboard');
        }

        if ($user->can('dashboard.clinic')) {
            return route('clinic.dashboard');
        }

        if ($user->can('audit.program.view')) {
            return route('audit.programs.index');
        }

        if ($user->can('audit.execution.view')) {
            return route('audit.executions.index');
        }

        if ($user->can('audit.view')) {
            return route('audit.executions.index');
        }

        if ($user->can('dms.view')) {
            return route('dms.index');
        }

        return route('dashboard');
    }

    /**
     * @return array<int, array{label: string, items: list<array{route: string, label: string, icon: string, permission: string|list<string>|null}>}>
     */
    public static function groupsForUser(?User $user): array
    {
        if (! $user) {
            return [];
        }

        return collect(self::groups())
            ->map(function (array $group) use ($user) {
                $group['items'] = self::visibleItems($user, $group['items']);

                return $group;
            })
            ->filter(fn (array $group) => $group['items'] !== [])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{route: string, label: string, icon: string, permission: string|list<string>|null}>
     */
    public static function forUser(?User $user): array
    {
        if (! $user) {
            return [];
        }

        return self::visibleItems($user, self::items());
    }

    /**
     * @param  list<array{route: string, label: string, icon: string, permission: string|list<string>|null}>  $items
     * @return list<array{route: string, label: string, icon: string, permission: string|list<string>|null}>
     */
    private static function visibleItems(User $user, array $items): array
    {
        return collect($items)
            ->filter(function (array $item) use ($user) {
                if (! $item['permission']) {
                    return true;
                }

                $permissions = (array) $item['permission'];

                return collect($permissions)->contains(fn (string $permission) => $user->can($permission));
            })
            ->filter(fn (array $item) => Route::has($item['route']))
            ->values()
            ->all();
    }
}
