<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seat_holds', function (Blueprint $table) {
            $table->boolean('is_checkout')->default(false)->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('seat_holds', function (Blueprint $table) {
            $table->dropColumn('is_checkout');
        });
    }
};
