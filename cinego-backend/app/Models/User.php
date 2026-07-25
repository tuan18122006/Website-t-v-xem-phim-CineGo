<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\PointHistory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'status',
        'membership_tier',
        'total_spent',
        'lock_reason',
        'is_anonymized',
        'work_status',
        'cine_points',
        'age',
    ];

    public function pointHistories(): HasMany
    {
        return $this->hasMany(PointHistory::class);
    }

    public function getPointMultiplierAttribute()
    {
        switch ($this->membership_tier) {
            case 'VVIP':
                return 1.5;
            case 'VIP':
                return 1.2;
            case 'Standard':
            default:
                return 1.0;
        }
    }

    public function monthlyFreeTickets(): HasMany
    {
        return $this->hasMany(MonthlyFreeTicket::class);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_anonymized' => 'boolean',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function seatHolds(): HasMany
    {
        return $this->hasMany(SeatHold::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function deviceLogs(): HasMany
    {
        return $this->hasMany(UserDeviceLog::class);
    }

    public function shiftLogs(): HasMany
    {
        return $this->hasMany(ShiftLog::class);
    }

    public function actionLogs(): HasMany
    {
        return $this->hasMany(ActionLog::class);
    }

    public function refundRequests(): HasMany
    {
        return $this->hasMany(RefundRequest::class);
    }

    public function vouchers(): BelongsToMany
    {
        return $this->belongsToMany(Voucher::class, 'user_vouchers')
            ->withPivot('id', 'is_used', 'used_at')
            ->withTimestamps();
    }
    public function combos(): BelongsToMany
    {
        return $this->belongsToMany(Combo::class, 'user_combos')
            ->withPivot('id', 'code', 'end_date', 'is_used', 'used_at')
            ->withTimestamps();
    }
}
