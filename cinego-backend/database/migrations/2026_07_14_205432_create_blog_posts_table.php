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
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->text('excerpt');
            $table->longText('content');
            $table->string('thumbnail_url', 255);

            // Khóa ngoại liên kết danh mục và phim
            $table->foreignId('category_id')->nullable()->constrained('blog_categories')->onDelete('set null');
            $table->foreignId('movie_id')->nullable()->constrained('movies')->onDelete('set null');

            $table->enum('status', ['draft', 'published', 'scheduled'])->default('draft');
            $table->dateTime('published_at')->nullable();

            // SEO Meta
            $table->string('meta_title', 255)->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};
