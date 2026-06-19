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
        Schema::create('employee_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('avatar')->nullable();
            $table->string('name');
            $table->string('plast_name');
            $table->string('mlast_name')->nullable();
            $table->string('rfc', 20)->nullable();
            $table->string('curp', 20)->nullable();
            $table->enum('gender', ['M','F'])->nullable();
           
            $table->string('cellphone', 50)->nullable();
            $table->string('signature_image')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();

            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->dateTime('deleted_at')->nullable();

            $table->index(['employee_id', 'start_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_details');
    }
};