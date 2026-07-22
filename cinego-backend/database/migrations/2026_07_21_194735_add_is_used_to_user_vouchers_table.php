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
        Schema::table('user_vouchers', function (Blueprint $table) {
            //
            Schema::table('user_vouchers', function (Blueprint $table) {
                $table->boolean('is_used')->default(false)->after('voucher_id');
                $table->timestamp('used_at')->nullable()->after('is_used');
                $table->timestamp('updated_at')->nullable()->after('created_at');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_vouchers', function (Blueprint $table) {
            //
            Schema::table('user_vouchers', function (Blueprint $table) {
                $table->dropColumn(['is_used', 'used_at', 'updated_at']);
            });
        });
    }
};
