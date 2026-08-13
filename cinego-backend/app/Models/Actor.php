<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Actor extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'avatar_url',
        'birth_date',
        'nationality',
        'bio'
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function movies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class, 'actor_movie')
            ->withPivot('character');
    }
}
