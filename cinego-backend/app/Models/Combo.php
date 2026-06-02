<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Combo extends Model
{
    protected $fillable = ['name', 'description', 'price', 'image_url', 'status'];

    public function bookingCombos(): HasMany
    {
        return $this->hasMany(BookingCombo::class);
    }
}
