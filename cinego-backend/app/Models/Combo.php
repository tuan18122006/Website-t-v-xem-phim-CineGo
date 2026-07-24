<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Combo extends Model
{
    protected $fillable = ['name', 'description', 'type', 'price', 'image_url', 'status','is_sellable','is_redeemable', 'stock','points_required','valid_days','valid_minutes','limit_per_user'];

    public function bookingCombos(): HasMany
    {
        return $this->hasMany(BookingCombo::class);
    }
    public function items()
    {
        return $this->hasMany(ComboItem::class);
    }
    public function comboItems()
    {
        return $this->hasMany(ComboItem::class, 'combo_id');
    }
    protected function imageUrl(): Attribute
{
    return Attribute::make(
        get: fn ($value) => $value ? asset('storage/' . $value) : null,
    );
}
}
