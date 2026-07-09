<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingRule extends Model
{
    protected $fillable = [
        'standard_price',
        'vip_price',
        'couple_price',
        'weekend_surcharge',
        'happy_hour_discount',
        'format_3d_surcharge',
        'sneak_show_surcharge'
    ];
}
