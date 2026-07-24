<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    protected $fillable = [
        'booking_code',
        'user_id',
        'vnp_txn_ref',
        'showtime_id',
        'voucher_id',
        'subtotal',
        'discount_amount',
        'total_amount',
        'payment_method',
        'payment_status',
        'booking_status',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function showtime(): BelongsTo
    {
        return $this->belongsTo(Showtime::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function bookingDetails(): HasMany
    {
        return $this->hasMany(BookingDetail::class);
    }

    public function bookingCombos(): HasMany
    {
        return $this->hasMany(BookingCombo::class);
    }
    public function userCombos()
    {
        return $this->belongsToMany(Combo::class, 'user_combos', 'booking_id', 'combo_id')
            ->withPivot('is_used', 'used_at');
    }
}
