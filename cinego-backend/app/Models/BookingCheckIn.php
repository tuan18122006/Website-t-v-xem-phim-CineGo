<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingCheckIn extends Model
{
    protected $fillable = [
        'booking_id',
        'checked_in_at',
        'reason',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}