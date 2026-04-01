<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_system_access', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('system_id')->constrained();
            //    $table->string('role', 50);          // 'admin', 'viewer', 'editor', etc.
            // $table->foreignId('access_role_id')->constrained();
            $table->date('start_date');
            $table->date('end_date')->nullable();

            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->dateTime('deleted_at')->nullable();

            $table->index(['user_id', 'system_id', 'start_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_system_access');
    }
};