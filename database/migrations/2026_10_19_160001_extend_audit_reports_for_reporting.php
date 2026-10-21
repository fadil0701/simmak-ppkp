<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_reports', function (Blueprint $table) {
            if (! Schema::hasColumn('audit_reports', 'audit_execution_id')) {
                $table->foreignUuid('audit_execution_id')->nullable()->constrained('audit_executions')->nullOnDelete();
            }

            if (! Schema::hasColumn('audit_reports', 'clinic_id')) {
                $table->foreignUuid('clinic_id')->nullable()->constrained('clinics')->nullOnDelete();
            }

            if (! Schema::hasColumn('audit_reports', 'title')) {
                $table->string('title')->nullable();
            }

            if (! Schema::hasColumn('audit_reports', 'report_type')) {
                $table->string('report_type', 40)->nullable();
            }

            if (! Schema::hasColumn('audit_reports', 'period_start')) {
                $table->date('period_start')->nullable();
            }

            if (! Schema::hasColumn('audit_reports', 'period_end')) {
                $table->date('period_end')->nullable();
            }

            if (! Schema::hasColumn('audit_reports', 'findings_summary')) {
                $table->json('findings_summary')->nullable();
            }

            if (! Schema::hasColumn('audit_reports', 'capa_summary')) {
                $table->json('capa_summary')->nullable();
            }

            if (! Schema::hasColumn('audit_reports', 'file_path')) {
                $table->string('file_path', 500)->nullable();
            }

            if (! Schema::hasColumn('audit_reports', 'file_type')) {
                $table->string('file_type', 20)->nullable();
            }

            if (! Schema::hasColumn('audit_reports', 'file_size')) {
                $table->unsignedInteger('file_size')->nullable();
            }

            if (! Schema::hasColumn('audit_reports', 'checksum')) {
                $table->string('checksum', 128)->nullable();
            }

            if (! Schema::hasColumn('audit_reports', 'generated_by')) {
                $table->foreignUuid('generated_by')->nullable()->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('audit_reports', 'generated_at')) {
                $table->timestamp('generated_at')->nullable();
            }

            if (! Schema::hasColumn('audit_reports', 'metadata')) {
                $table->json('metadata')->nullable();
            }
        });

        if (Schema::hasColumn('audit_reports', 'audit_schedule_id')) {
            Schema::table('audit_reports', function (Blueprint $table) {
                $table->uuid('audit_schedule_id')->nullable()->change();
            });
        }

        if (Schema::hasColumn('audit_reports', 'report_date')) {
            Schema::table('audit_reports', function (Blueprint $table) {
                $table->date('report_date')->nullable()->change();
            });
        }

        DB::table('audit_reports')
            ->whereNull('title')
            ->update(['title' => DB::raw('report_number')]);

        DB::table('audit_reports')
            ->whereNull('report_type')
            ->update(['report_type' => 'summary']);

        DB::table('audit_reports')
            ->whereNull('generated_at')
            ->update(['generated_at' => DB::raw('created_at')]);

        if (Schema::hasColumn('audit_reports', 'prepared_by')) {
            DB::table('audit_reports')
                ->whereNull('generated_by')
                ->whereNotNull('prepared_by')
                ->update(['generated_by' => DB::raw('prepared_by')]);
        }

        if (! Schema::hasIndex('audit_reports', ['generated_at'])) {
            Schema::table('audit_reports', function (Blueprint $table) {
                $table->index('generated_at');
            });
        }
    }

    public function down(): void
    {
        // Keep newly generated report metadata and legacy report data intact.
    }
};
