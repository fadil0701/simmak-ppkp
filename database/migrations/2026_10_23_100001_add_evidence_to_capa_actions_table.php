<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('capa_actions', function (Blueprint $table) {
            if (! Schema::hasColumn('capa_actions', 'evidence_notes')) {
                $table->text('evidence_notes')->nullable()->after('status');
            }
            if (! Schema::hasColumn('capa_actions', 'evidence_submitted_at')) {
                $table->timestamp('evidence_submitted_at')->nullable()->after('evidence_notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('capa_actions', function (Blueprint $table) {
            if (Schema::hasColumn('capa_actions', 'evidence_submitted_at')) {
                $table->dropColumn('evidence_submitted_at');
            }
            if (Schema::hasColumn('capa_actions', 'evidence_notes')) {
                $table->dropColumn('evidence_notes');
            }
        });
    }
};
