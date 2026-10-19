<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_clients', function (Blueprint $table) {
            $table->foreignId('system_id')->nullable()->after('id')->constrained('systems')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable()->after('last_used_at');
            $table->index(['active', 'expires_at', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::table('api_clients', function (Blueprint $table) {
            $table->dropIndex(['active', 'expires_at', 'revoked_at']);
            $table->dropConstrainedForeignId('system_id');
            $table->dropColumn('revoked_at');
        });
    }
};
