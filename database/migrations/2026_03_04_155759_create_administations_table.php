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
        Schema::create('administrations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('president_name');
            $table->string('political_party')->nullable();
            $table->string('logo')->nullable();       // ruta del archivo
            $table->string('logo_2')->nullable();
            $table->string('logo_3')->nullable();
            $table->string('primary_color', 50)->nullable();   // código hexadecimal
            $table->string('secondary_color', 50)->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();

            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->dateTime('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('administrations');
    }
};