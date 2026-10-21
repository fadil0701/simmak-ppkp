<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audit_reports')) {
            Schema::create('audit_reports', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
                $table->foreignUuid('audit_execution_id')->nullable()->constrained('audit_executions')->nullOnDelete();
                $table->foreignUuid('clinic_id')->nullable()->constrained('clinics')->nullOnDelete();
                $table->string('report_number', 80)->unique();
                $table->string('title');
                $table->string('report_type', 40); // program, findings, capa, execution, summary
                $table->date('period_start')->nullable();
                $table->date('period_end')->nullable();
                $table->text('executive_summary')->nullable();
                $table->json('findings_summary')->nullable();
                $table->json('capa_summary')->nullable();
                $table->string('file_path', 500)->nullable();
                $table->string('file_type', 20)->nullable(); // pdf, excel
                $table->unsignedInteger('file_size')->nullable();
                $table->string('checksum', 128)->nullable();
                $table->string('status', 40)->default('generated'); // generated, published, archived
                $table->foreignUuid('generated_by')->constrained('users')->cascadeOnDelete();
                $table->timestamp('generated_at')->useCurrent();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['organization_id', 'report_number']);
                $table->index(['organization_id', 'status']);
                $table->index(['clinic_id']);
                $table->index(['report_type']);
                $table->index(['generated_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_reports');
    }
};
