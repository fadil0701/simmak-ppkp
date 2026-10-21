<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('standard_sections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('instrument_version_id')->constrained('instrument_versions')->cascadeOnDelete();
            $table->uuid('parent_id')->nullable();
            $table->string('code', 80);
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['instrument_version_id', 'code']);
            $table->index(['instrument_version_id', 'parent_id']);
            $table->index(['instrument_version_id', 'sort_order']);
        });

        Schema::table('standard_sections', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('standard_sections')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('standard_sections');
    }
};
