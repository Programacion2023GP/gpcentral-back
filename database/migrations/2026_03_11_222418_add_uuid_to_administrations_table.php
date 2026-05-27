<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('administrations', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        DB::statement('UPDATE administrations SET uuid = UUID() WHERE uuid IS NULL');

        Schema::table('administrations', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('administrations', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
