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
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->integer('standard_price')->default(50000);
            $table->integer('vip_price')->default(70000);
            $table->integer('couple_price')->default(120000);
            $table->integer('weekend_surcharge')->default(10000);
            $table->integer('happy_hour_discount')->default(10000);
            $table->integer('format_3d_surcharge')->default(30000);
            $table->integer('sneak_show_surcharge')->default(20000);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
