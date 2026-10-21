<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regulatory_sources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('document_number', 120)->unique();
            $table->string('title');
            $table->string('issuer')->nullable();
            $table->string('source_url')->nullable();
            $table->date('published_at')->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->string('verification_status', 40)->default('UNVERIFIED');
            $table->timestamp('verified_at')->nullable();
            $table->foreignUuid('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('verification_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['verification_status']);
            $table->index(['effective_from', 'effective_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regulatory_sources');
    }
};
