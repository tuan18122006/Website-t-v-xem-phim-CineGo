<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_based_pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tên quy tắc (VD: Ngày lễ 30/4, Phim bán quyền cao)
            $table->enum('seat_type', ['standard', 'vip', 'couple']); // Loại ghế
            $table->integer('price_adjustment'); // Điều chỉnh giá (có thể âm hoặc dương)
            $table->date('start_date'); // Ngày bắt đầu
            $table->date('end_date'); // Ngày kết thúc
            $table->time('start_time')->nullable(); // Giờ bắt đầu (NULL = không giới hạn)
            $table->time('end_time')->nullable(); // Giờ kết thúc (NULL = không giới hạn)
            $table->boolean('is_active')->default(true); // Trạng thái kích hoạt
            $table->timestamps();
            
            // Index để tối ưu truy vấn
            $table->index(['start_date', 'end_date']);
            $table->index(['is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_based_pricing_rules');
    }
};
