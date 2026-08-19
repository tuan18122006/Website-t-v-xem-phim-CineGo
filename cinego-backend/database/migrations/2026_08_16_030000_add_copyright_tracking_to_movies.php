<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Thêm các trường để theo dõi bản quyền phim:
     * - copyright_fee_level: Mức phí bản quyền (low, medium, high)
     * - copyright_notes: Ghi chú về bản quyền
     */
    public function up(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->enum('copyright_fee_level', ['low', 'medium', 'high'])->default('low')->after('status')->comment('Mức phí bản quyền');
            $table->text('copyright_notes')->nullable()->after('copyright_fee_level')->comment('Ghi chú về bản quyền');
        });
    }

    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropColumn(['copyright_fee_level', 'copyright_notes']);
        });
    }
};
