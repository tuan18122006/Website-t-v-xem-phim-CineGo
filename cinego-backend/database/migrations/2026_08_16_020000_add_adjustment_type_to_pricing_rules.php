<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add adjustment_type column to support different types of price adjustments:
     * - surcharge: Fixed amount in VND
     * - percentage: Percentage increase/decrease (%)
     * - free: Make tickets free for selected seat type
     */
    public function up(): void
    {
        Schema::table('time_based_pricing_rules', function (Blueprint $table) {
            $table->enum('adjustment_type', ['surcharge', 'percentage', 'free'])
                ->default('surcharge')
                ->after('seat_type')
                ->comment('Loại điều chỉnh giá: cộng tiền, tăng %, hoặc miễn phí');
        });
    }

    public function down(): void
    {
        Schema::table('time_based_pricing_rules', function (Blueprint $table) {
            $table->dropColumn('adjustment_type');
        });
    }
};
