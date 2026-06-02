<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seat_holds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('showtime_id')->constrained()->onDelete('cascade');
            $table->foreignId('seat_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('expires_at');
            $table->timestamps();
            
            // Một ghế trong cùng 1 suất chiếu chỉ được có tối đa 1 active hold tại một thời điểm
            $table->unique(['showtime_id', 'seat_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seat_holds');
    }
};
