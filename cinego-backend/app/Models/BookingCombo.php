<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingCombo extends Model
{
    protected $fillable = ['booking_id', 'combo_id', 'quantity', 'price_at_purchase','subtotal'];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function combo(): BelongsTo
    {
        return $this->belongsTo(Combo::class);
    }
}
