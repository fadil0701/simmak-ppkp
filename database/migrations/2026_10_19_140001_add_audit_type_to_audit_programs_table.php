<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_programs', function (Blueprint $table) {
            $table->string('audit_type', 40)->nullable()->after('program_code'); // periodic, thematic, special, follow_up
            $table->text('scope_notes')->nullable()->after('audit_type');
            $table->index(['audit_type']);
        });
    }

    public function down(): void
    {
        Schema::table('audit_programs', function (Blueprint $table) {
            $table->dropIndex(['audit_type']);
            $table->dropColumn(['audit_type', 'scope_notes']);
        });
    }
};
