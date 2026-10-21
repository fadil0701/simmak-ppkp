<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AuditPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
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
            'audit.action.view',
            'audit.action.update',
            'audit.action.submit',
            'audit.capa.verify',
            'audit.finding.close',
            'audit.finding.reopen',
            'audit.finding.escalate',
            'audit.report.view',
            'audit.report.manage',
            'audit.log.view',
            'audit.configuration.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(array_unique(array_merge(
                $superAdmin->permissions->pluck('name')->all(),
                $permissions
            )));
        }

        $adminMutu = Role::where('name', 'admin-mutu')->where('guard_name', 'web')->first();
        if ($adminMutu) {
            $adminMutu->syncPermissions(array_unique(array_merge(
                $adminMutu->permissions->pluck('name')->all(),
                $permissions
            )));
        }

        $pjMutu = Role::where('name', 'pj-mutu')->where('guard_name', 'web')->first();
        if ($pjMutu) {
            $pjMutu->syncPermissions(array_unique(array_merge(
                $pjMutu->permissions->pluck('name')->all(),
                [
                    'audit.program.view',
                    'audit.program.create',
                    'audit.program.update',
                    'audit.program.submit',
                    'audit.assignment.view',
                    'audit.assignment.manage',
                    'audit.standard.view',
                    'audit.standard.manage',
                    'audit.standard.review',
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
                    'audit.action.view',
                    'audit.action.update',
                    'audit.action.submit',
                    'audit.finding.close',
                    'audit.finding.reopen',
                    'audit.finding.escalate',
                    'audit.report.view',
                    'audit.report.manage',
                ]
            )));
        }

        $auditor = Role::where('name', 'auditor')->where('guard_name', 'web')->first();
        if ($auditor) {
            $auditor->syncPermissions(array_unique(array_merge(
                $auditor->permissions->pluck('name')->all(),
                [
                    'audit.program.view',
                    'audit.assignment.view',
                    'audit.standard.view',
                    'audit.execution.view',
                    'audit.execution.execute',
                    'audit.execution.submit',
                    'audit.evidence.upload',
                    'audit.evidence.view',
                    'audit.finding.view',
                    'audit.finding.create',
                    'audit.finding.update',
                    'audit.action.view',
                    'audit.action.update',
                    'audit.report.view',
                ]
            )));
        }

        $kepalaKlinik = Role::where('name', 'kepala-klinik')->where('guard_name', 'web')->first();
        if ($kepalaKlinik) {
            $kepalaKlinik->syncPermissions(array_unique(array_merge(
                $kepalaKlinik->permissions->pluck('name')->all(),
                [
                    'audit.program.view',
                    'audit.assignment.view',
                    'audit.execution.view',
                    'audit.standard.view',
                    'audit.evidence.view',
                    'audit.finding.view',
                    'audit.finding.review',
                    'audit.action.view',
                    'audit.finding.close',
                    'audit.finding.escalate',
                    'audit.report.view',
                ]
            )));
        }

        Role::where('guard_name', 'web')->each(function (Role $role) {
            $role->forgetCachedPermissions();
        });
    }
}
