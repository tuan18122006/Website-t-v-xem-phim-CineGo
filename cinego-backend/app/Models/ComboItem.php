<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComboItem extends Model
{
    protected $fillable = [
        'combo_id',
        'item_id',
        'quantity'
    ];

    public function combo()
    {
        return $this->belongsTo(Combo::class, 'combo_id');
    }

    public function item()
    {
        return $this->belongsTo(Combo::class, 'item_id');
    }
    
}
