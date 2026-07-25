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
       Schema::table('user_combos', function (Blueprint $table) {
            $table->string('code')->nullable()->after('combo_id');
            $table->dateTime('end_date')->nullable()->after('is_used');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('user_combos', function (Blueprint $table) {
            $table->dropColumn(['code', 'end_date']);
        });
    }
};
