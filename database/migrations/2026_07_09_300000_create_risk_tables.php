<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'code']);
        });

        Schema::create('risk_registers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('clinic_id')->nullable()->constrained('clinics')->nullOnDelete();
            $table->foreignUuid('fiscal_year_id')->nullable()->constrained('fiscal_years')->nullOnDelete();
            $table->string('register_code', 80);
            $table->string('name');
            $table->string('status', 50)->default('active');
            $table->foreignUuid('owner_id')->constrained('users')->restrictOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unique(['organization_id', 'register_code']);
        });

        Schema::create('risk_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('risk_register_id')->constrained('risk_registers')->cascadeOnDelete();
            $table->foreignUuid('risk_category_id')->constrained('risk_categories')->restrictOnDelete();
            $table->foreignUuid('clinic_id')->nullable()->constrained('clinics')->nullOnDelete();
            $table->string('risk_code', 80);
            $table->string('title');
            $table->text('risk_description')->nullable();
            $table->string('risk_source')->nullable();
            $table->unsignedTinyInteger('inherent_likelihood')->default(1);
            $table->unsignedTinyInteger('inherent_impact')->default(1);
            $table->unsignedSmallInteger('inherent_score')->default(1);
            $table->unsignedTinyInteger('residual_likelihood')->default(1);
            $table->unsignedTinyInteger('residual_impact')->default(1);
            $table->unsignedSmallInteger('residual_score')->default(1);
            $table->string('risk_level', 30)->default('low');
            $table->string('status', 50)->default('identified');
            $table->foreignUuid('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('review_date')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unique(['organization_id', 'risk_code']);
            $table->index(['organization_id', 'risk_level', 'status']);
            $table->index(['inherent_likelihood', 'inherent_impact']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_items');
        Schema::dropIfExists('risk_registers');
        Schema::dropIfExists('risk_categories');
    }
};
