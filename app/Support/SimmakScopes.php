<?php

namespace App\Support;

/**
 * Scope Sistem: lapisan kewenangan fungsional yang dapat dikombinasikan
 * pada satu akun, terlepas dari peran strukturalnya.
 *
 * Izin efektif = izin peran ∪ izin scope. Scope tidak pernah mengurangi izin
 * peran, tidak melewati segregasi tugas (approve/close/reopen/verify), dan
 * tidak melewati batas klinik (AuditWorkScope & User::hasClinicAccess()).
 */
class SimmakScopes
{
    public const AUDIT_INTERNAL = 'audit_internal';

    public const MUTU = 'mutu';

    public const AKREDITASI = 'akreditasi';

    public const MONITORING = 'monitoring';

    public const AUDITOR = 'auditor';

    public const AUDITI = 'auditi';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::AUDIT_INTERNAL => 'Audit Internal — kelola penuh modul audit',
            self::MUTU => 'Mutu — kelola indikator, risiko, dan tindak lanjut',
            self::AKREDITASI => 'Akreditasi — kelola standar dan bukti akreditasi',
            self::MONITORING => 'Monitoring — baca lintas modul tanpa hak ubah',
            self::AUDITOR => 'Auditor — pelaksanaan pemeriksaan',
            self::AUDITI => 'Auditi — tindak lanjut hasil audit di klinik',
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
     * Seluruh izin yang mungkin diberikan oleh scope mana pun.
     *
     * @return list<string>
     */
    public static function allPermissions(): array
    {
        $all = [];

        foreach (self::assignableNames() as $scope) {
            $all = array_merge($all, self::permissionsFor($scope));
        }

        return array_values(array_unique($all));
    }

    /**
     * @return list<string>
     */
    public static function permissionsFor(string $scope): array
    {
        return match ($scope) {
            self::AUDIT_INTERNAL => array_merge(
                ['dashboard.executive', 'audit.view', 'audit.manage', 'audit.dashboard.view',
                    'capa.view', 'capa.manage', 'audit.log.view'],
                self::auditPermissionsByPrefix([
                    'audit.program.', 'audit.assignment.', 'audit.standard.', 'audit.execution.',
                    'audit.evidence.', 'audit.finding.', 'audit.action.', 'audit.report.',
                ]),
            ),
            self::MUTU => [
                'dashboard.executive',
                'quality.view', 'quality.manage',
                'risk.view', 'risk.manage',
                'capa.view', 'capa.manage',
                'dms.view',
            ],
            self::AKREDITASI => [
                'dashboard.executive',
                'accreditation.view', 'accreditation.manage',
                'dms.view', 'dms.manage',
            ],
            self::MONITORING => array_merge(
                ['dashboard.executive', 'audit.dashboard.view', 'audit.log.view'],
                array_values(array_filter(
                    SimmakRoles::auditPermissions(),
                    fn (string $permission) => str_ends_with($permission, '.view'),
                )),
                [
                    'capa.view', 'risk.view', 'quality.view', 'accreditation.view',
                    'dms.view', 'clinics.view', 'employees.view',
                ],
            ),
            self::AUDITOR => [
                'dashboard.executive', 'audit.dashboard.view', 'audit.view', 'audit.manage',
                'audit.program.view', 'audit.assignment.view', 'audit.standard.view',
                'audit.execution.view', 'audit.execution.execute', 'audit.execution.submit',
                'audit.evidence.upload', 'audit.evidence.view',
                'audit.finding.view', 'audit.finding.create', 'audit.finding.update',
                'audit.action.view', 'audit.action.update', 'audit.capa.verify',
                'audit.report.view', 'capa.view',
            ],
            self::AUDITI => [
                'dashboard.clinic', 'audit.view',
                'audit.execution.view', 'audit.evidence.view', 'audit.evidence.download',
                'audit.finding.view', 'audit.action.view', 'audit.action.update', 'audit.action.submit',
                'capa.view', 'capa.manage',
            ],
            default => [],
        };
    }

    /**
     * Izin `audit.*` yang berawalan salah satu prefiks yang diberikan.
     *
     * @param  array<int, string>  $prefixes
     * @return array<int, string>
     */
    private static function auditPermissionsByPrefix(array $prefixes): array
    {
        return array_values(array_filter(
            SimmakRoles::auditPermissions(),
            function (string $permission) use ($prefixes) {
                foreach ($prefixes as $prefix) {
                    if (str_starts_with($permission, $prefix)) {
                        return true;
                    }
                }

                return false;
            },
        ));
    }
}
