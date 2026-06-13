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
        // 1. Cập nhật bảng users
        Schema::table('users', function (Blueprint $table) {
            $table->string('membership_tier')->default('Bronze'); // Bronze, Silver, Gold, Diamond
            $table->integer('loyalty_points')->default(0);
            $table->decimal('total_spent', 12, 2)->default(0.00);
            $table->text('lock_reason')->nullable();
            $table->boolean('is_anonymized')->default(false);
            $table->string('work_status')->default('off_shift'); // off_shift, on_shift
        });

        // 2. Tạo bảng user_devices_logs
        Schema::create('user_devices_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('ip_address', 45);
            $table->text('user_agent');
            $table->timestamp('last_login_at')->useCurrent();
        });

        // 3. Tạo bảng shift_logs
        Schema::create('shift_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('shift_name', 50); // Ca Sáng, Ca Chiều, Ca Tối
            $table->string('workstation', 50); // Quầy vé số 1, Quầy bắp nước số 2
            $table->timestamp('checkin_time')->useCurrent();
            $table->timestamp('checkout_time')->nullable();
            $table->decimal('reported_cash', 10, 2)->default(0.00);
            $table->decimal('reported_transfer', 10, 2)->default(0.00);
            $table->decimal('system_revenue', 10, 2)->default(0.00);
            $table->string('status', 20)->default('open'); // open, closed
            $table->foreignId('audited_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('audit_note')->nullable();
            $table->timestamps();
        });

        // 4. Tạo bảng action_logs (audit logs)
        Schema::create('action_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('action', 50); // refund_ticket, edit_price, etc.
            $table->string('target_type', 50)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->json('details')->nullable();
            $table->string('ip_address', 45);
            $table->timestamp('created_at')->useCurrent();
        });

        // 5. Tạo bảng refund_requests
        Schema::create('refund_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('status', 20)->default('pending'); // pending, approved, rejected
            $table->text('reason');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refund_requests');
        Schema::dropIfExists('action_logs');
        Schema::dropIfExists('shift_logs');
        Schema::dropIfExists('user_devices_logs');
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'membership_tier',
                'loyalty_points',
                'total_spent',
                'lock_reason',
                'is_anonymized',
                'work_status'
            ]);
        });
    }
};
