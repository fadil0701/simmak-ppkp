<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

class SimmakMenu
{
    /**
     * Modul yang ditampilkan di sidebar.
     *
     * Untuk demo Release 1 (Audit Internal) hanya modul yang berkaitan dengan
     * Audit Internal yang ditawarkan agar pengguna tidak bingung. Modul lain
     * (DMS, Akreditasi, Mutu, Risiko, Pelaporan, Pengaturan, dll.) tetap ada
     * di belakang permission-nya masing-masing, hanya tidak ditampilkan di menu.
     *
     * @return array<int, array{route: string, label: string, icon: string, permission: string|list<string>|null}>
     */
    public static function items(): array
    {
        return [
            ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'grid', 'permission' => ['dashboard.executive', 'audit.dashboard.view']],
            ['route' => 'clinic.dashboard', 'label' => 'Dashboard Klinik', 'icon' => 'building', 'permission' => 'dashboard.clinic'],
            ['route' => 'clinics.index', 'label' => 'Master Unit/Klinik', 'icon' => 'building', 'permission' => 'clinics.view'],
            ['route' => 'employees.index', 'label' => 'Master Pegawai', 'icon' => 'users', 'permission' => 'employees.view'],
            ['route' => 'positions.index', 'label' => 'Master Jabatan', 'icon' => 'award', 'permission' => 'positions.view'],
            ['route' => 'audit.index', 'label' => 'Audit Internal', 'icon' => 'search', 'permission' => 'audit.view'],
            ['route' => 'audit.reports.index', 'label' => 'Laporan Audit', 'icon' => 'file-text', 'permission' => 'audit.report.view'],
            ['route' => 'audit.standards.index', 'label' => 'Standar & Instrumen', 'icon' => 'book-open', 'permission' => 'audit.standard.view'],
            ['route' => 'audit.programs.index', 'label' => 'Program Audit', 'icon' => 'clipboard-list', 'permission' => 'audit.program.view'],
            ['route' => 'audit.assignments.index', 'label' => 'Penugasan Auditor', 'icon' => 'user-group', 'permission' => 'audit.assignment.view'],
            ['route' => 'audit.executions.index', 'label' => 'Pelaksanaan Audit', 'icon' => 'clipboard-check', 'permission' => 'audit.execution.view'],
            ['route' => 'audit.evidence.index', 'label' => 'Bukti Audit', 'icon' => 'paperclip', 'permission' => 'audit.evidence.view'],
            ['route' => 'fiscal-years.index', 'label' => 'Tahun Anggaran', 'icon' => 'settings', 'permission' => 'settings.manage'],
            ['route' => 'findings.index', 'label' => 'Temuan Audit', 'icon' => 'alert-triangle', 'permission' => 'audit.finding.view'],
            ['route' => 'capa.index', 'label' => 'CAPA', 'icon' => 'tool', 'permission' => 'capa.view'],
            ['route' => 'users.index', 'label' => 'Pengguna', 'icon' => 'users', 'permission' => 'users.view'],
            // Ditahan selama demo Audit Internal:
            // - accreditation.index  (Akreditasi)
            // - risk.index           (Risiko)
            // - quality.index        (Program Mutu)
            // - dms.index            (Dokumen)
            // - reports.index        (Pelaporan)
            // - settings.index       (Pengaturan)
        ];
    }

    public static function homeRouteFor(User $user): string
    {
        if ($user->can('dashboard.executive') || $user->can('audit.dashboard.view')) {
            return route('dashboard');
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

                $permissions = (array) $item['permission'];

                return collect($permissions)->contains(fn (string $permission) => $user->can($permission));
            })
            ->filter(fn (array $item) => Route::has($item['route']))
            ->values()
            ->all();
    }
}
