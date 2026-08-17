<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TimeBasedPricingRule extends Model
{
    protected $fillable = [
        'name',
        'scope',
        'movie_id',
        'seat_type',
        'adjustment_type',
        'price_adjustment',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'use_date',
        'use_time',
        'is_active'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'use_date' => 'boolean',
        'use_time' => 'boolean',
        'is_active' => 'boolean',
        'price_adjustment' => 'integer'
    ];

    /**
     * Relationship: Rule belongs to a Movie (if scope = 'movie')
     */
    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }

    /**
     * Kiểm tra quy tắc có áp dụng cho một showtime cụ thể không
     *
     * @param \Carbon\Carbon $datetime Thời gian của showtime
     * @param string $seatType Loại ghế
     * @return bool
     */
    public function appliesToShowtime(Carbon $datetime, string $seatType): bool
    {
        // Kiểm tra loại ghế
        if ($this->seat_type !== $seatType) {
            return false;
        }

        // Kiểm tra ngày nếu use_date được kích hoạt
        if ($this->use_date) {
            if ($datetime->date() < $this->start_date->format('Y-m-d') ||
                $datetime->date() > $this->end_date->format('Y-m-d')) {
                return false;
            }
        }

        // Kiểm tra giờ nếu use_time được kích hoạt
        if ($this->use_time) {
            if ($this->start_time !== null && $this->end_time !== null) {
                $showtime = $datetime->format('H:i:s');
                $startTimeStr = $this->start_time instanceof Carbon ? $this->start_time->format('H:i:s') : date('H:i:s', strtotime($this->start_time));
                $endTimeStr = $this->end_time instanceof Carbon ? $this->end_time->format('H:i:s') : date('H:i:s', strtotime($this->end_time));

                if ($showtime < $startTimeStr || $showtime > $endTimeStr) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Lấy tất cả quy tắc áp dụng cho một showtime (bao gồm cả quy tắc toàn hệ thống và quy tắc riêng phim)
     *
     * @param Carbon $datetime Thời gian showtime
     * @param string $seatType Loại ghế
     * @param int|null $movieId ID của phim (optional, để lọc quy tắc riêng phim)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getApplicableRules(Carbon $datetime, string $seatType, ?int $movieId = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = self::where('is_active', true)
            ->where('seat_type', $seatType);

        // Nếu có movie_id, lọc quy tắc toàn hệ thống (scope='system') + quy tắc riêng phim (scope='movie' và movie_id khớp)
        if ($movieId !== null) {
            $query->where(function ($q) use ($movieId) {
                $q->where('scope', 'system')
                  ->orWhere(function ($q2) use ($movieId) {
                      $q2->where('scope', 'movie')->where('movie_id', $movieId);
                  });
            });
        } else {
            // Nếu không có movie_id, chỉ lấy quy tắc toàn hệ thống
            $query->where('scope', 'system');
        }

        return $query
            ->where(function ($q) use ($datetime) {
                $q->whereNull('start_date')
                  ->orWhere('start_date', '<=', $datetime->date());
            })
            ->where(function ($q) use ($datetime) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $datetime->date());
            })
            ->get()
            ->filter(function (self $rule) use ($datetime, $seatType) {
                return $rule->appliesToShowtime($datetime, $seatType);
            });
    }

    /**
     * Tính toán tổng điều chỉnh giá từ tất cả quy tắc áp dụng (bao gồm quy tắc toàn hệ thống và riêng phim)
     *
     * @param Carbon $datetime Thời gian showtime
     * @param string $seatType Loại ghế
     * @param int|null $movieId ID của phim
     * @return int
     */
    public static function calculateTotalAdjustment(Carbon $datetime, string $seatType, ?int $movieId = null): int
    {
        return self::getApplicableRules($datetime, $seatType, $movieId)
            ->sum('price_adjustment');
    }
}
