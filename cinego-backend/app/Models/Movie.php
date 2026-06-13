<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Movie extends Model
{
    protected $fillable = [
       'title', 
    'slug', 
    'description', 
    'duration', 
    'release_date', 
    'status', 
    'rating', 
    'poster_url', 
    'trailer_url'
    ];

    protected $casts = [
        'release_date' => 'date',
    ];

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'movie_genre','movie_id', 'genre_id');
    }

    public function showtimes(): HasMany
    {
        return $this->hasMany(Showtime::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
