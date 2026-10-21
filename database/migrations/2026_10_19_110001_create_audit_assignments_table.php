<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('audit_program_id')->constrained('audit_programs')->cascadeOnDelete();
            $table->foreignUuid('clinic_id')->nullable()->constrained('clinics')->nullOnDelete();
            $table->string('assignment_code', 80);
            $table->string('title');
            $table->text('scope_notes')->nullable();
            $table->foreignUuid('lead_auditor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('scheduled_start');
            $table->date('scheduled_end');
            $table->string('status', 40)->default('draft'); // draft, proposed, active, completed, cancelled
            $table->string('conflict_check_status', 40)->default('pending'); // pending, declared, resolved, blocked
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unique(['organization_id', 'assignment_code']);
            $table->index(['organization_id', 'status']);
            $table->index(['clinic_id', 'status']);
            $table->index(['lead_auditor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_assignments');
    }
};
