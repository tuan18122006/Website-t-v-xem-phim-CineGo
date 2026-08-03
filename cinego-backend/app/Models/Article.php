<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'thumbnail_url',
        'views',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function movies()
    {
        return $this->belongsToMany(Movie::class, 'article_movies')
            ->withPivot('rank', 'review_text')
            ->orderByPivot('rank', 'asc');
    }
}
