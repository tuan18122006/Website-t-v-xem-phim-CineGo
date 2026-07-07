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
        Schema::table('booking_combos', function (Blueprint $table) {
            $table->renameColumn('price', 'price_at_purchase');

            $table->decimal('subtotal', 12, 2)->after('price_at_purchase');

            $table->dropForeign(['combo_id']); 
            $table->foreign('combo_id')->references('id')->on('combos')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_combos', function (Blueprint $table) {
            //
            $table->renameColumn('price_at_purchase', 'price');
            $table->dropColumn('subtotal');
        });
    }
};
