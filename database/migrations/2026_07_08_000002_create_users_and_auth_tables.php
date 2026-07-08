<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignUuid('default_clinic_id')->nullable()->constrained('clinics')->nullOnDelete();
            $table->string('name');
            $table->string('username', 100)->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('auth_provider', 20)->default('local');
            $table->string('ldap_dn')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->json('metadata')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'deleted_at']);
            $table->index(['auth_provider', 'is_active']);
        });

        Schema::create('user_clinic_scopes', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'clinic_id']);
        });

        Schema::create('ldap_configurations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name');
            $table->string('host');
            $table->unsignedSmallInteger('port')->default(389);
            $table->boolean('use_tls')->default(false);
            $table->boolean('use_ssl')->default(false);
            $table->string('base_dn');
            $table->string('bind_dn')->nullable();
            $table->text('bind_password')->nullable();
            $table->string('user_filter')->nullable();
            $table->string('username_attribute', 50)->default('sAMAccountName');
            $table->boolean('is_enabled')->default(false);
            $table->boolean('sync_on_login')->default(true);
            $table->json('attribute_map')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ldap_sync_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ldap_configuration_id')->constrained('ldap_configurations')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50);
            $table->string('status', 20);
            $table->text('message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('login_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('identifier');
            $table->string('auth_provider', 20)->default('local');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->boolean('successful')->default(false);
            $table->timestamp('attempted_at')->useCurrent();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUuid('user_id')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('login_attempts');
        Schema::dropIfExists('ldap_sync_logs');
        Schema::dropIfExists('ldap_configurations');
        Schema::dropIfExists('user_clinic_scopes');
        Schema::dropIfExists('users');
    }
};
