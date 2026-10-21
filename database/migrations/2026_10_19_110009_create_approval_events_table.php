<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('entity_type', 80);
            $table->uuid('entity_id');
            $table->string('step_key', 80);
            $table->foreignUuid('actor_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('decision', 40); // approved, rejected, returned
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('acted_at')->useCurrent();
            $table->timestamps();

            $table->index(['organization_id']);
            $table->index(['entity_type', 'entity_id']);
            $table->index(['step_key']);
            $table->index(['acted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_events');
    }
};
