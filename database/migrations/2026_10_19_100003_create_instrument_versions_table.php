<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instrument_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('audit_standard_id')->constrained('audit_standards')->cascadeOnDelete();
            $table->string('version_label', 80);
            $table->string('status', 40)->default('draft'); // draft/review/approved/active/archived
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->text('source_snapshot')->nullable();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['audit_standard_id', 'version_label']);
            $table->index(['audit_standard_id', 'status']);
            $table->index(['effective_from', 'effective_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instrument_versions');
    }
};
