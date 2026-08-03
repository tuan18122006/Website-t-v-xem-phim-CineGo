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
        Schema::table('vouchers', function (Blueprint $table) {
            if (!Schema::hasColumn('vouchers', 'starts_at')) {
                $table->timestamp('starts_at')->nullable()->after('expires_at');
            }

            if (!Schema::hasColumn('vouchers', 'user_limit')) {
                $table->integer('user_limit')->nullable()->default(1)->after('usage_limit');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn(['starts_at', 'user_limit']);
        });
    }
};
