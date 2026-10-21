<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidence', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('clinic_id')->nullable()->constrained('clinics')->nullOnDelete();
            $table->foreignUuid('audit_execution_id')->nullable()->constrained('audit_executions')->nullOnDelete();
            $table->foreignUuid('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('storage_disk', 40)->default('local');
            $table->string('storage_path', 500);
            $table->string('original_filename', 255);
            $table->string('mime_type', 120);
            $table->unsignedInteger('size_bytes');
            $table->string('checksum', 128)->nullable();
            $table->string('evidence_type', 80)->nullable();
            $table->string('access_classification', 40)->default('internal');
            $table->text('description')->nullable();
            $table->timestamp('uploaded_at')->useCurrent();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id']);
            $table->index(['clinic_id']);
            $table->index(['audit_execution_id']);
            $table->index(['uploaded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence');
    }
};
