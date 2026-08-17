<?php

namespace Database\Seeders;

use App\Models\TimeBasedPricingRule;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TimeBasedPricingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $today = Carbon::today();

        // Ví dụ 1: Ngày lễ 30/4 - tăng giá tất cả loại ghế (chỉ ngày, không có giờ) - Toàn hệ thống
        TimeBasedPricingRule::create([
            'name' => 'Ngày Lễ 30/4 - Tăng Giá',
            'seat_type' => 'standard',
            'price_adjustment' => 10000,
            'start_date' => $today->copy()->addDays(14)->toDateString(),
            'end_date' => $today->copy()->addDays(14)->toDateString(),
            'start_time' => null,
            'end_time' => null,
            'use_date' => true,
            'use_time' => false,
            'scope' => 'system',
            'movie_id' => null,
            'is_active' => true
        ]);

        TimeBasedPricingRule::create([
            'name' => 'Ngày Lễ 30/4 - Tăng Giá',
            'seat_type' => 'vip',
            'price_adjustment' => 15000,
            'start_date' => $today->copy()->addDays(14)->toDateString(),
            'end_date' => $today->copy()->addDays(14)->toDateString(),
            'start_time' => null,
            'end_time' => null,
            'use_date' => true,
            'use_time' => false,
            'scope' => 'system',
            'movie_id' => null,
            'is_active' => true
        ]);

        TimeBasedPricingRule::create([
            'name' => 'Ngày Lễ 30/4 - Tăng Giá',
            'seat_type' => 'couple',
            'price_adjustment' => 20000,
            'start_date' => $today->copy()->addDays(14)->toDateString(),
            'end_date' => $today->copy()->addDays(14)->toDateString(),
            'start_time' => null,
            'end_time' => null,
            'use_date' => true,
            'use_time' => false,
            'scope' => 'system',
            'movie_id' => null,
            'is_active' => true
        ]);

        // Ví dụ 2: Giờ cao điểm (18:00 - 22:00) - tăng giá 5000 VND (kết hợp ngày và giờ) - Toàn hệ thống
        TimeBasedPricingRule::create([
            'name' => 'Giờ Cao Điểm (18:00-22:00) - Tăng Giá',
            'seat_type' => 'standard',
            'price_adjustment' => 5000,
            'start_date' => $today->toDateString(),
            'end_date' => $today->addDays(30)->toDateString(),
            'start_time' => '18:00',
            'end_time' => '22:00',
            'use_date' => true,
            'use_time' => true,
            'scope' => 'system',
            'movie_id' => null,
            'is_active' => true
        ]);

        // Ví dụ 3: Giờ vàng (14:00 - 17:00) - giảm giá 3000 VND (kết hợp ngày và giờ) - Toàn hệ thống
        TimeBasedPricingRule::create([
            'name' => 'Giờ Vàng (14:00-17:00) - Giảm Giá',
            'seat_type' => 'standard',
            'price_adjustment' => -3000,
            'start_date' => $today->toDateString(),
            'end_date' => $today->addDays(30)->toDateString(),
            'start_time' => '14:00',
            'end_time' => '17:00',
            'use_date' => true,
            'use_time' => true,
            'scope' => 'system',
            'movie_id' => null,
            'is_active' => true
        ]);

        // Ví dụ 4: Phim bán quyền cao (tăng giá VIP, chỉ ngày) - Theo phim
        // Lấy phim đầu tiên từ database (nếu có)
        $movie = \App\Models\Movie::first();
        if ($movie) {
            TimeBasedPricingRule::create([
                'name' => 'Phim Bản Quyền Cao - VIP +15000',
                'seat_type' => 'vip',
                'price_adjustment' => 15000,
                'start_date' => $today->toDateString(),
                'end_date' => $today->addDays(7)->toDateString(),
                'start_time' => null,
                'end_time' => null,
                'use_date' => true,
                'use_time' => false,
                'scope' => 'movie',
                'movie_id' => $movie->id,
                'is_active' => false  // Vô hiệu theo mặc định, admin kích hoạt khi cần
            ]);
        }
    }
}
