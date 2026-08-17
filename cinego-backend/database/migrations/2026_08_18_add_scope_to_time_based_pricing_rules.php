<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('time_based_pricing_rules', function (Blueprint $table) {
            $table->enum('scope', ['system', 'movie'])->default('system')->after('name');
            $table->foreignId('movie_id')->nullable()->after('scope')->constrained('movies')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('time_based_pricing_rules', function (Blueprint $table) {
            $table->dropForeignIdFor('movies');
            $table->dropColumn(['scope', 'movie_id']);
        });
    }
};
