<?php

namespace App\Support;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Katalog peran resmi SIMMAK (bukan master jabatan).
 *
 * Jabatan pegawai hanya kelengkapan dokumen; akses sistem ditentukan
 * oleh peran di bawah ini plus scope klinik dan status alur kerja.
 */
class SimmakRoles
{
    public const SUPER_ADMIN = 'super-admin';

    public const ADMIN_SISTEM = 'admin-sistem';

    public const KEPALA_PUSAT = 'kepala-pusat';

    public const KASUBBAG_TU = 'kasubbag-tu';

    public const KETUA_TIM_MUTU = 'ketua-tim-mutu';

    public const KETUA_TIM_AUDIT = 'ketua-tim-audit';

    public const KETUA_TIM_AKREDITASI = 'ketua-tim-akreditasi';

    public const AUDITOR = 'auditor';

    public const KEPALA_KLINIK = 'kepala-klinik';

    public const AUDITI = 'auditi';

    /**
     * Peran lama yang masih dibuat agar tes/akun existing tidak pecah.
     *
     * @return array<string, string>
     */
    public static function legacyMap(): array
    {
        return [
            'admin-mutu' => self::ADMIN_SISTEM,
            'pj-mutu' => self::KETUA_TIM_MUTU,
            'tim-akreditasi' => self::KETUA_TIM_AKREDITASI,
            'staff' => self::AUDITI,
        ];
    }

    /**
     * @return list<string>
     */
    public static function assignableNames(): array
    {
        return array_keys(self::labels());
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::SUPER_ADMIN => 'Super Admin (Akses Penuh Sistem)',
            self::ADMIN_SISTEM => 'Admin Sistem',
            self::KEPALA_PUSAT => 'Kepala Pusat',
            self::KASUBBAG_TU => 'Kasubbag TU',
            self::KETUA_TIM_MUTU => 'Ketua Tim Mutu',
            self::KETUA_TIM_AUDIT => 'Ketua Tim Audit',
            self::KETUA_TIM_AKREDITASI => 'Ketua Tim Akreditasi',
            self::AUDITOR => 'Auditor',
            self::KEPALA_KLINIK => 'Kepala Klinik/Unit',
            self::AUDITI => 'Auditi',
        ];
    }

    public static function label(?string $role): string
    {
        if ($role === null || $role === '') {
            return 'Pengguna';
        }

        $legacy = self::legacyMap();
        if (isset($legacy[$role])) {
            return self::labels()[$legacy[$role]] ?? $role;
        }

        return self::labels()[$role] ?? $role;
    }

    /**
     * Peran manajemen pusat: memantau seluruh kegiatan audit, bukan objek audit.
     *
     * @return list<string>
     */
    public static function headOfficeRoles(): array
    {
        return [
            self::SUPER_ADMIN,
            self::ADMIN_SISTEM,
            self::KEPALA_PUSAT,
            self::KETUA_TIM_MUTU,
            self::KETUA_TIM_AUDIT,
        ];
    }

    /**
     * @return list<string>
     */
    public static function modulePermissions(): array
    {
        return [
            'dashboard.view',
            'dashboard.executive',
            'dashboard.clinic',
            'dms.view', 'dms.manage',
            'audit.view', 'audit.manage',
            'capa.view', 'capa.manage',
            'risk.view', 'risk.manage',
            'accreditation.view', 'accreditation.manage',
            'quality.view', 'quality.manage',
            'users.view', 'users.manage',
            'settings.view', 'settings.manage',
            'clinics.view', 'clinics.manage',
            'employees.view', 'employees.manage',
            'positions.view', 'positions.manage',
            'workflow.manage',
        ];
    }

    /**
     * @return list<string>
     */
    public static function auditPermissions(): array
    {
        return [
            'audit.program.view',
            'audit.program.create',
            'audit.program.update',
            'audit.program.submit',
            'audit.program.approve',
            'audit.assignment.view',
            'audit.assignment.manage',
            'audit.standard.view',
            'audit.standard.manage',
            'audit.standard.review',
            'audit.standard.publish',
            'audit.execution.view',
            'audit.execution.execute',
            'audit.execution.submit',
            'audit.evidence.upload',
            'audit.evidence.view',
            'audit.evidence.download',
            'audit.finding.view',
            'audit.finding.create',
            'audit.finding.update',
            'audit.finding.review',
            'audit.capa.verify',
            'audit.action.view',
            'audit.action.update',
            'audit.action.submit',
            'audit.finding.close',
            'audit.finding.reopen',
            'audit.finding.escalate',
            'audit.report.view',
            'audit.report.manage',
            'audit.report.export',
            'audit.report.publish',
            'audit.dashboard.view',
            'audit.log.view',
            'audit.configuration.manage',
        ];
    }

    /**
     * @return list<string>
     */
    public static function allPermissionNames(): array
    {
        return array_values(array_unique(array_merge(self::modulePermissions(), self::auditPermissions())));
    }

    /**
     * @return list<string>
     */
    public static function permissionsFor(string $role): array
    {
        $all = self::allPermissionNames();

        $monitorAudit = [
            'dashboard.view', 'dashboard.executive', 'audit.dashboard.view', 'audit.view',
            'audit.program.view', 'audit.assignment.view', 'audit.execution.view',
            'audit.standard.view', 'audit.evidence.view', 'audit.evidence.download',
            'audit.finding.view', 'audit.action.view',             'audit.report.view', 'audit.report.export', 'audit.report.publish',
            'audit.log.view', 'capa.view', 'clinics.view', 'employees.view', 'positions.view',
            'risk.view', 'accreditation.view', 'quality.view', 'dms.view',
        ];

        $qualityLead = array_values(array_unique(array_merge($monitorAudit, [
            'dashboard.clinic',
            'dms.manage', 'audit.manage', 'capa.manage', 'risk.manage',
            'accreditation.view', 'quality.manage',
            'clinics.manage', 'employees.view', 'employees.manage',
            'positions.view', 'positions.manage',
            'audit.program.create', 'audit.program.update', 'audit.program.submit',
            'audit.assignment.view', 'audit.assignment.manage',
            'audit.standard.view', 'audit.standard.manage', 'audit.standard.review',
            'audit.execution.view', 'audit.execution.execute', 'audit.execution.submit',
            'audit.evidence.upload', 'audit.evidence.view', 'audit.evidence.download',
            'audit.finding.view', 'audit.finding.create', 'audit.finding.update', 'audit.finding.review',
            'audit.action.view', 'audit.action.update', 'audit.action.submit',
            'audit.finding.close', 'audit.finding.reopen', 'audit.finding.escalate',
            'audit.report.view', 'audit.report.manage', 'audit.report.export', 'audit.report.publish',
        ])));

        $auditLead = [
            'dashboard.view', 'audit.dashboard.view', 'audit.view', 'audit.manage',
            'audit.program.view', 'audit.program.create', 'audit.program.update',
            'audit.program.submit', 'audit.program.approve',
            'audit.assignment.view', 'audit.assignment.manage',
            'audit.standard.view', 'audit.standard.manage', 'audit.standard.review', 'audit.standard.publish',
            'audit.execution.view', 'audit.finding.view', 'audit.finding.review',
            'audit.evidence.view', 'audit.evidence.download',
            'audit.report.view', 'audit.report.manage', 'audit.report.export', 'audit.report.publish',
            'audit.log.view', 'capa.view', 'clinics.view',
        ];

        return match ($role) {
            self::SUPER_ADMIN => $all,
            self::ADMIN_SISTEM => [
                'dashboard.view',
                'users.view', 'users.manage',
                'settings.view', 'settings.manage',
                'clinics.view', 'clinics.manage',
                'employees.view', 'employees.manage',
                'positions.view', 'positions.manage',
                'dms.view', 'dms.manage',
                'audit.configuration.manage', 'audit.log.view', 'workflow.manage',
            ],
            self::KEPALA_PUSAT => array_values(array_unique(array_merge($monitorAudit, [
                'dashboard.clinic', 'clinics.manage',
            ]))),
            self::KASUBBAG_TU => [
                'dashboard.view',
                'users.view', 'users.manage',
                'employees.view', 'employees.manage',
                'positions.view', 'positions.manage',
                'clinics.view',
                'settings.view',
                'dms.view', 'dms.manage',
            ],
            self::KETUA_TIM_MUTU => $qualityLead,
            self::KETUA_TIM_AUDIT => $auditLead,
            self::KETUA_TIM_AKREDITASI => [
                'dashboard.view', 'dashboard.clinic',
                'dms.view',
                'accreditation.view', 'accreditation.manage',
                'capa.view', 'quality.view',
                'audit.view', 'audit.standard.view',
                'clinics.view',
            ],
            self::AUDITOR => [
                'dashboard.view', 'audit.dashboard.view', 'audit.view', 'audit.manage',
                'audit.program.view', 'audit.assignment.view', 'audit.standard.view',
                'audit.execution.view', 'audit.execution.execute', 'audit.execution.submit',
                'audit.evidence.upload', 'audit.evidence.view',
                'audit.finding.view', 'audit.finding.create', 'audit.finding.update',
                'audit.action.view', 'audit.action.update',
                'audit.report.view', 'capa.view',
            ],
            self::KEPALA_KLINIK => [
                'dashboard.view', 'dashboard.clinic',
                'dms.view', 'audit.view', 'capa.view', 'capa.manage',
                'risk.view', 'accreditation.view', 'quality.view',
                'clinics.view', 'employees.view', 'positions.view',
                'audit.program.view', 'audit.assignment.view', 'audit.execution.view',
                'audit.standard.view', 'audit.evidence.view',
                'audit.finding.view', 'audit.finding.review', 'audit.action.view',
                'audit.finding.close', 'audit.finding.escalate',
                'audit.report.view', 'audit.report.export',
            ],
            self::AUDITI => [
                'dashboard.view', 'dashboard.clinic',
                'dms.view', 'audit.view',
                'audit.execution.view', 'audit.evidence.view',
                'audit.finding.view', 'audit.action.view', 'audit.action.update', 'audit.action.submit',
                'capa.view', 'capa.manage', 'accreditation.view',
            ],
            default => [],
        };
    }

    public static function ensurePermissions(): void
    {
        foreach (self::allPermissionNames() as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }

    public static function sync(): void
    {
        self::ensurePermissions();

        foreach (self::labels() as $name => $label) {
            $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            $role->syncPermissions(self::permissionsFor($name));
        }

        foreach (self::legacyMap() as $legacy => $canonical) {
            $role = Role::firstOrCreate(['name' => $legacy, 'guard_name' => 'web']);
            $role->syncPermissions(self::permissionsFor($canonical));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
