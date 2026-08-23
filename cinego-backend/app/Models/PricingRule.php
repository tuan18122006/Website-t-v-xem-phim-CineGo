<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingRule extends Model
{
    protected $fillable = [
        'standard_price',
        'vip_price',
        'couple_price',
        'weekend_price',
        'happy_hour_price',
        'format_3d_price',
        'sneak_show_price',
        'weekend_surcharge',
        'happy_hour_discount',
        'format_3d_surcharge',
        'sneak_show_surcharge',
        'pricing_rules'
    ];

    protected $casts = [
        'pricing_rules' => 'array'
    ];

    protected static array $legacyAliases = [
        'weekend_price' => 'weekend_surcharge',
        'happy_hour_price' => 'happy_hour_discount',
        'format_3d_price' => 'format_3d_surcharge',
        'sneak_show_price' => 'sneak_show_surcharge',
    ];

    public static function normalizePayload(array $payload): array
    {
        foreach (self::$legacyAliases as $newKey => $oldKey) {
            if (array_key_exists($newKey, $payload) && !array_key_exists($oldKey, $payload)) {
                $payload[$oldKey] = $payload[$newKey];
            }
        }

        return $payload;
    }

    public function toArray(): array
    {
        $data = parent::toArray();

        foreach (self::$legacyAliases as $newKey => $oldKey) {
            if (!array_key_exists($newKey, $data) && array_key_exists($oldKey, $data)) {
                $data[$newKey] = $data[$oldKey];
            }
        }

        return $data;
    }
}
