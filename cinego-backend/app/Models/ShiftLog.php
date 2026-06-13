<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftLog extends Model
{
    use HasFactory;

    protected $table = 'shift_logs';

    protected $fillable = [
        'user_id',
        'shift_name',
        'workstation',
        'checkin_time',
        'checkout_time',
        'reported_cash',
        'reported_transfer',
        'system_revenue',
        'status',
        'audited_by',
        'audit_note',
    ];

    protected $casts = [
        'checkin_time' => 'datetime',
        'checkout_time' => 'datetime',
        'reported_cash' => 'decimal:2',
        'reported_transfer' => 'decimal:2',
        'system_revenue' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'audited_by');
    }
}
