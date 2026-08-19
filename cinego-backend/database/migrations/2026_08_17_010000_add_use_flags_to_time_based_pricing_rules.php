<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('time_based_pricing_rules', function (Blueprint $table) {
            $table->boolean('use_date')->default(true)->after('end_date');
            $table->boolean('use_time')->default(false)->after('use_date');
        });
    }

    public function down(): void
    {
        Schema::table('time_based_pricing_rules', function (Blueprint $table) {
            $table->dropColumn(['use_date', 'use_time']);
        });
    }
};
