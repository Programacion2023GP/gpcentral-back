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
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index();                    // identificador lógico del puesto
            // $table->foreignId('organization_id')->constrained();
            // $table->foreignId('department_id')->constrained();
            // $table->uuid('department_uuid')->nullable()->index(); // UUID del departamento lógico
            $table->string('name', 255);
            $table->string('office_phone', 50)->nullable();
            $table->string('ext', 20)->nullable();
            $table->uuid('parent_position_uuid')->nullable()->index(); // puesto superior (jefe)
            $table->date('start_date');
            $table->date('end_date')->nullable();

            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->dateTime('deleted_at')->nullable();

            $table->index(['uuid', 'start_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
