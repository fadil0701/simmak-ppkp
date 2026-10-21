<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_findings', function (Blueprint $table) {
            $table->boolean('is_escalated')->default(false)->after('severity');
            $table->timestamp('escalated_at')->nullable()->after('is_escalated');
            $table->foreignUuid('escalated_by')->nullable()->after('escalated_at')->constrained('users')->nullOnDelete();
            $table->text('escalation_reason')->nullable()->after('escalated_by');

            $table->index(['is_escalated', 'escalated_at']);
            $table->index(['severity', 'is_escalated', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('audit_findings', function (Blueprint $table) {
            $table->dropIndex(['is_escalated', 'escalated_at']);
            $table->dropIndex(['severity', 'is_escalated', 'status']);
            $table->dropColumn([
                'is_escalated',
                'escalated_at',
                'escalated_by',
                'escalation_reason',
            ]);
        });
    }
};
