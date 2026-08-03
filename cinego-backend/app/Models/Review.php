<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = [
        'user_id', 'movie_id', 'rating', 'comment',
        'is_hidden', 'is_featured', 'admin_reply', 'replied_at',
    ];

    protected $casts = [
        'is_hidden' => 'boolean',
        'is_featured' => 'boolean',
        'replied_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }
}
