<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleMovie extends Model
{
    protected $table = 'article_movies';

    protected $fillable = [
        'article_id',
        'movie_id',
        'rank',
        'review_text',
    ];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }
}
