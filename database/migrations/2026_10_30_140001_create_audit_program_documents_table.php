<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dokumen acuan Program Audit: berkas disimpan di file storage, metadata di tabel ini.
 * Tidak merusak program atau penugasan lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('audit_program_documents')) {
            return;
        }

        Schema::create('audit_program_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('audit_program_id')->constrained('audit_programs')->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('storage_disk', 40)->default('local');
            $table->string('storage_path', 500);
            $table->string('original_filename', 255);
            $table->string('mime_type', 120);
            $table->unsignedInteger('size_bytes');
            $table->string('checksum', 128)->nullable();
            $table->json('metadata')->nullable();
            $table->foreignUuid('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('uploaded_at')->useCurrent();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['audit_program_id'], 'apd_program_id_index');
            $table->index(['organization_id'], 'apd_organization_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_program_documents');
    }
};
