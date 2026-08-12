<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->boolean('payment_reminder_sent')
                ->default(false)
                ->after('retry_count')
                ->comment('Đã gửi thông báo nhắc thanh toán cho đơn (1 lần/đơn)');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('payment_reminder_sent');
        });
    }
};