<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            Schema::table('evidence', function (Blueprint $table) {
                $table->fullText(['original_filename', 'description'], 'evidence_fulltext');
            });
            Schema::table('audit_findings', function (Blueprint $table) {
                $table->fullText(['finding_code', 'title', 'description', 'recommendation'], 'findings_fulltext');
            });

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement("CREATE INDEX IF NOT EXISTS evidence_fulltext ON evidence USING GIN (to_tsvector('simple', coalesce(original_filename, '') || ' ' || coalesce(description, '')))");
            DB::statement("CREATE INDEX IF NOT EXISTS findings_fulltext ON audit_findings USING GIN (to_tsvector('simple', coalesce(finding_code, '') || ' ' || coalesce(title, '') || ' ' || coalesce(description, '') || ' ' || coalesce(recommendation, '')))");
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            Schema::table('evidence', function (Blueprint $table) {
                $table->dropFullText('evidence_fulltext');
            });
            Schema::table('audit_findings', function (Blueprint $table) {
                $table->dropFullText('findings_fulltext');
            });

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS evidence_fulltext');
            DB::statement('DROP INDEX IF EXISTS findings_fulltext');
        }
    }
};
