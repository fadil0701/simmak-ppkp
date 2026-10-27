<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Scope Sistem multi-pilih per akun (lihat dokumen
 * PERBAIKAN_MASTER_PEGAWAI_PENGGUNA_PENUGASAN.md §3.5).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_scope_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('scope', 40);
            $table->foreignUuid('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('granted_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'scope']);
        });

        $this->backfillFromRoles();
    }

    public function down(): void
    {
        Schema::dropIfExists('user_scope_assignments');
    }

    /**
     * Isi awal: peran lama yang fungsinya kini menjadi scope.
     * Idempoten (insertOrIgnore) dan aman untuk MySQL maupun PostgreSQL.
     */
    private function backfillFromRoles(): void
    {
        $map = [
            'auditor' => 'auditor',
            'auditi' => 'auditi',
            'staff' => 'auditi',
            'kepala-pusat' => 'monitoring',
        ];

        $now = now();

        foreach ($map as $role => $scope) {
            $userIds = DB::table('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('roles.name', $role)
                ->where('roles.guard_name', 'web')
                ->distinct()
                ->pluck('model_has_roles.model_id');

            $rows = $userIds->map(fn ($userId) => [
                'id' => (string) Str::uuid(),
                'user_id' => $userId,
                'scope' => $scope,
                'granted_by' => null,
                'granted_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            if ($rows !== []) {
                DB::table('user_scope_assignments')->insertOrIgnore($rows);
            }
        }
    }
};
