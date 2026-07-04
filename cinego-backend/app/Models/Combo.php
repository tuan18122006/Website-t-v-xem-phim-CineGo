<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Combo extends Model
{
    protected $fillable = ['name', 'description','type', 'price', 'image_url', 'status', 'stock'];

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
}
