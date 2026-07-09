<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Showtime extends Model
{
    protected $fillable = ['movie_id', 'room_id', 'start_time', 'end_time', 'format', 'translation', 'status', 'is_sneak_show', 'pricing_snapshot'];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_sneak_show' => 'boolean',
        'pricing_snapshot' => 'array',
    ];

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function priceConfigs(): HasMany
    {
        return $this->hasMany(PriceConfig::class);
    }

    public function seatHolds(): HasMany
    {
        return $this->hasMany(SeatHold::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
