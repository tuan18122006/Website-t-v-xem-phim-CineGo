<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceConfig extends Model
{
    protected $fillable = ['showtime_id', 'seat_type', 'price'];

    public function showtime(): BelongsTo
    {
        return $this->belongsTo(Showtime::class);
    }
}
