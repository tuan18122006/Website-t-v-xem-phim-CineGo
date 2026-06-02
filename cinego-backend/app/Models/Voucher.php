<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voucher extends Model
{
    protected $fillable = [
        'code',
        'discount_type',
        'discount_value',
        'min_spend',
        'max_discount',
        'expires_at',
        'usage_limit',
        'used_count',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
