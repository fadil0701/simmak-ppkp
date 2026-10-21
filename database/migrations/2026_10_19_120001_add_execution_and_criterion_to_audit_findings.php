<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_findings', function (Blueprint $table) {
            $table->foreignUuid('audit_execution_id')->nullable()->after('audit_schedule_id');
            $table->foreignUuid('standard_criterion_id')->nullable()->after('audit_execution_id');
            $table->string('finding_type', 40)->nullable()->after('severity'); // non_conformity, observation, improvement
            $table->text('condition')->nullable()->after('description');
            $table->text('requirement')->nullable()->after('condition');
            $table->text('objective_evidence_summary')->nullable()->after('requirement');
            $table->text('impact')->nullable()->after('objective_evidence_summary');
            $table->text('recommendation')->nullable()->after('impact');
            $table->text('auditee_response')->nullable()->after('recommendation');
            $table->foreignUuid('owner_user_id')->nullable()->after('assigned_to');
            $table->timestamp('due_at')->nullable()->after('owner_user_id');
            $table->foreignUuid('reviewed_by')->nullable()->after('updated_by');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->timestamp('closed_at')->nullable()->after('reviewed_at');

            $table->index(['audit_execution_id']);
            $table->index(['standard_criterion_id']);
            $table->index(['owner_user_id']);
            $table->index(['status', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::table('audit_findings', function (Blueprint $table) {
            $table->dropColumn([
                'audit_execution_id', 'standard_criterion_id', 'finding_type',
                'condition', 'requirement', 'objective_evidence_summary', 'impact',
                'recommendation', 'auditee_response', 'owner_user_id', 'due_at',
                'reviewed_by', 'reviewed_at', 'closed_at',
            ]);
        });
    }
};
