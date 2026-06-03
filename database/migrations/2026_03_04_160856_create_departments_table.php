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
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index();                    // identificador lógico del departamento
            $table->foreignId('organization_id')->constrained();
            $table->string('code', 45)->nullable()->unique();
            $table->string('name', 255);
            $table->string('logo')->nullable();         // logo
            $table->string('seal_image')->nullable();         // sello
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
        Schema::dropIfExists('departments');
    }
};
