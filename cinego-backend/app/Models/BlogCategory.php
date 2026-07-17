<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlogCategory extends Model
{
    protected $table = 'blog_categories';

    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Mối quan hệ: Một chuyên mục có thể có nhiều bài viết Blog
     */
    public function blogPosts(): HasMany
    {
        return $this->hasMany(BlogPost::class, 'category_id');
    }
}