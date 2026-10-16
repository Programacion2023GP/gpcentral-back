<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->enum('employment_type', ['confianza', 'sindicalizado'])->default('confianza')->after('hire_date')->index();
            $table->date('termination_date')->nullable()->after('employment_type');
            $table->string('external_source', 100)->nullable()->after('termination_date');
            $table->string('external_id', 150)->nullable()->after('external_source');
            $table->unique(['external_source', 'external_id']);
        });

        Schema::create('sync_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 100);
            $table->enum('trigger', ['manual', 'scheduled', 'api']);
            $table->enum('status', ['preview', 'running', 'completed', 'failed', 'cancelled'])->default('preview');
            $table->string('source_hash', 64)->nullable()->index();
            $table->json('summary')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sync_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sync_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employee_code', 50)->index();
            $table->enum('change_type', ['create', 'update', 'rehire', 'terminate', 'unchanged', 'conflict', 'invalid']);
            $table->json('incoming_data')->nullable();
            $table->json('current_data')->nullable();
            $table->json('differences')->nullable();
            $table->boolean('selected')->default(true);
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
            $table->unique(['sync_run_id', 'employee_code']);
        });

        Schema::create('api_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('client_id', 64)->unique();
            $table->string('secret_hash');
            $table->json('abilities');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_clients');
        Schema::dropIfExists('sync_changes');
        Schema::dropIfExists('sync_runs');
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['external_source', 'external_id']);
            $table->dropColumn(['employment_type', 'termination_date', 'external_source', 'external_id']);
        });
    }
};
