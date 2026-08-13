<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            if (!Schema::hasColumn('reviews', 'is_hidden')) {
                $table->boolean('is_hidden')->default(false)->after('comment');
            }
            if (!Schema::hasColumn('reviews', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('is_hidden');
            }
            if (!Schema::hasColumn('reviews', 'admin_reply')) {
                $table->text('admin_reply')->nullable()->after('is_featured');
            }
            if (!Schema::hasColumn('reviews', 'replied_at')) {
                $table->timestamp('replied_at')->nullable()->after('admin_reply');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn(['is_hidden', 'is_featured', 'admin_reply', 'replied_at']);
        });
    }
};
