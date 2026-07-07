<?php

namespace Database\Seeders;

use App\Models\Movie;
use App\Models\Room;
use App\Models\Showtime;
use App\Models\PriceConfig;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class WeeklyShowtimeSeeder extends Seeder
{
    public function run()
    {
        $movies = Movie::where('status', 'showing')->orWhere('status', 'Đang chiếu')->get();
        if ($movies->count() == 0) {
            $movies = Movie::all();
        }
        $rooms = Room::all();
        if ($rooms->count() == 0) {
            return;
        }

        $startDate = Carbon::today();
        
        // Generate 10 random showtimes over the next 7 days
        for ($i = 0; $i < 10; $i++) {
            $movie = $movies->random();
            $room = $rooms->random();
            $daysToAdd = rand(0, 6);
            $hours = rand(9, 22);
            $minutes = array_rand([0 => '00', 1 => '30']);
            $minutesStr = [0 => '00', 1 => '30'][$minutes];
            
            $start = $startDate->copy()->addDays($daysToAdd)->setHour($hours)->setMinute((int)$minutesStr);
            $end = $start->copy()->addMinutes($movie->duration ?? 120);

            $showtime = Showtime::create([
                'movie_id' => $movie->id,
                'room_id' => $room->id,
                'start_time' => $start,
                'end_time' => $end,
                'format' => rand(0, 1) ? '2D' : '3D',
                'translation' => rand(0, 1) ? 'Phụ đề' : 'Thuyết minh',
                'status' => 'active'
            ]);

            // Create Price Configs for this Showtime
            PriceConfig::create([
                'showtime_id' => $showtime->id,
                'seat_type' => 'standard',
                'price' => 75000
            ]);
            PriceConfig::create([
                'showtime_id' => $showtime->id,
                'seat_type' => 'vip',
                'price' => 95000
            ]);
            PriceConfig::create([
                'showtime_id' => $showtime->id,
                'seat_type' => 'couple',
                'price' => 140000
            ]);
        }
    }
}
