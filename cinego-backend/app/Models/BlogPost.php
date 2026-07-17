<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogPost extends Model
{
    // Định nghĩa chính xác tên bảng trong database
    protected $table = 'blog_posts';

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'thumbnail_url',
        'status',
        'published_at',
        'category_id',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    /**
     * Mối quan hệ: Một bài viết thuộc về một chuyên mục (Category)
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }
}