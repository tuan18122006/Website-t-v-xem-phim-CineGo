<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        'target_limit',
        'usage_condition', 
        'used_count',
        'is_active',
        'points_required',
        'max_exchanges',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'usage_condition' => 'array',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_vouchers');
    }
}