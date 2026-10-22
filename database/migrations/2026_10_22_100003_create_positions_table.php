<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Master jabatan (pangkat/jabatan pegawai) — sumber untuk employees.position_id.
        Schema::create('positions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name', 150);
            $table->string('category', 50)->default('fungsional'); // struktural, fungsional, teknis, administrasi
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'is_active']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->foreignUuid('position_id')->nullable()->after('unit_id')->constrained('positions')->nullOnDelete();
        });

        // Backfill: nama jabatan lama pada employees dipindahkan ke master jabatan.
        if (Schema::hasColumn('employees', 'position')) {
            $rows = DB::table('employees')
                ->whereNotNull('position')
                ->where('position', '<>', '')
                ->select('organization_id', 'position')
                ->distinct()
                ->get();

            foreach ($rows as $row) {
                $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]+/', '_', $row->position), 0, 40));

                $positionId = DB::table('positions')->where('organization_id', $row->organization_id)
                    ->where(function ($query) use ($row, $code) {
                        $query->where('code', $code)->orWhere('name', $row->position);
                    })
                    ->value('id');

                if (! $positionId) {
                    $positionId = DB::table('positions')->insertGetId([
                        'id' => (string) Str::uuid(),
                        'organization_id' => $row->organization_id,
                        'code' => $code,
                        'name' => $row->position,
                        'category' => 'fungsional',
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('employees')
                    ->where('organization_id', $row->organization_id)
                    ->where('position', $row->position)
                    ->update(['position_id' => $positionId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('position_id');
        });

        Schema::dropIfExists('positions');
    }
};
