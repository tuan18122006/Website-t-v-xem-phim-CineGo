<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seat_hold_confirms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('showtime_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('confirmed_at');
            $table->timestamps();
            $table->index(['user_id', 'showtime_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seat_hold_confirms');
    }
};
