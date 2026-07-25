<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PointHistory extends Model
{
    protected $fillable = [
        'user_id', 'points', 'type', 'description', 'reference_type', 'reference_id'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Quan hệ Polymorphic với Booking, Review, Voucher, Combo...
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}