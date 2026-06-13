<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Genre;
use App\Models\Movie;
use App\Models\Room;
use App\Models\Seat;
use App\Models\Combo;
use App\Models\Voucher;
use App\Models\Showtime;
use App\Models\PriceConfig;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Tạo Users
        User::create([
            'name' => 'Quản trị viên CineGo',
            'email' => 'admin@cinego.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'phone' => '0987654321',
            'age' => 30
        ]);

        User::create([
            'name' => 'Khách hàng CineGo',
            'email' => 'customer@cinego.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
            'phone' => '0123456789',
            'age' => 25
        ]);

        // 2. Tạo Genres
        $genresData = [
            ['name' => 'Hành Động', 'slug' => 'hanh-dong'],
            ['name' => 'Viễn Tưởng', 'slug' => 'vien-tuong'],
            ['name' => 'Kỳ Ảo', 'slug' => 'ky-ao'],
            ['name' => 'Hoạt Hình', 'slug' => 'hoat-hinh'],
            ['name' => 'Trinh Thám', 'slug' => 'trinh-tham']
        ];
        $genres = [];
        foreach ($genresData as $g) {
            $genres[] = Genre::create($g);
        }

        // 3. Tạo Movies
        $movies = [
            Movie::create([
                'title' => 'Doctor Strange: Đa Vũ Trụ Hỗn Loạn',
                'slug' => 'doctor-strange-2',
                'description' => 'Doctor Strange du hành vào không gian đa vũ trụ phức tạp để bảo vệ thế giới khỏi những hiểm nguy khôn lường.',
                'duration' => 126,
                'release_date' => '2026-05-15',
                'poster_url' => 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?auto=format&fit=crop&w=400&q=80',
                'rating' => 'T13',
                'status' => 'showing'
            ]),
            Movie::create([
                'title' => 'Avatar: Dòng Chảy Của Nước',
                'slug' => 'avatar-2',
                'description' => 'Jake Sully và Neytiri phải rời bỏ tổ ấm và khám phá các vùng đất mới của Pandora khi mối đe dọa cũ quay trở lại.',
                'duration' => 192,
                'release_date' => '2026-06-01',
                'poster_url' => 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=400&q=80',
                'rating' => 'PG-13',
                'status' => 'showing'
            ]),
            Movie::create([
                'title' => 'Kẻ Kiến Tạo (The Creator)',
                'slug' => 'the-creator',
                'description' => 'Giữa cuộc chiến khốc liệt của nhân loại và trí tuệ nhân tạo, một cựu đặc vụ được giao nhiệm vụ ám sát "Kẻ kiến tạo".',
                'duration' => 133,
                'release_date' => '2026-05-20',
                'poster_url' => 'https://images.unsplash.com/photo-1594909122845-11baa439b7bf?auto=format&fit=crop&w=400&q=80',
                'rating' => 'T16',
                'status' => 'showing'
            ])
        ];

        // Gắn genres cho movies
        $movies[0]->genres()->attach([$genres[0]->id, $genres[1]->id, $genres[2]->id]);
        $movies[1]->genres()->attach([$genres[0]->id, $genres[1]->id, $genres[2]->id]);
        $movies[2]->genres()->attach([$genres[0]->id, $genres[1]->id]);

        // 4. Tạo Rooms & Seats
        $rooms = [
            Room::create(['name' => 'Phòng Chiếu LUXURY 01', 'total_seats' => 120]),
            Room::create(['name' => 'Phòng Chiếu IMAX 3D 02', 'total_seats' => 120])
        ];

        foreach ($rooms as $room) {
            $rows = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J'];
            foreach ($rows as $row) {
                $cols = ($row === 'J') ? 6 : 12; // J là Couple nên gom 2 ghế làm 1, có thể xếp 6 ghế đôi lớn
                for ($col = 1; $col <= ($row === 'J' ? 12 : 12); $col++) {
                    $type = 'standard';
                    if (in_array($row, ['F', 'G', 'H'])) {
                        $type = 'vip';
                    } elseif ($row === 'J') {
                        $type = 'couple';
                    }
                    
                    Seat::create([
                        'room_id' => $room->id,
                        'row' => $row,
                        'number' => $col,
                        'type' => $type,
                        'status' => 'available'
                    ]);
                }
            }
        }

        // 5. Tạo Combos bắp nước
        Combo::create([
            'name' => 'Combo Solo Bắp Ngọt',
            'description' => '1 Bắp lớn vị ngọt + 1 Nước ngọt size L tùy chọn',
            'price' => 75000,
            'image_url' => 'https://images.unsplash.com/photo-1578849278619-e73505e9610f?auto=format&fit=crop&w=150&q=80',
            'status' => 'active'
        ]);

        Combo::create([
            'name' => 'Combo Couple Hỗn Hợp',
            'description' => '1 Bắp lớn vị phô mai/caramel + 2 Nước ngọt size L tùy chọn',
            'price' => 115000,
            'image_url' => 'https://images.unsplash.com/photo-1585647347483-22b66260dfff?auto=format&fit=crop&w=150&q=80',
            'status' => 'active'
        ]);

        // 6. Tạo Vouchers
        Voucher::create([
            'code' => 'CINEGO10',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'min_spend' => 50000,
            'expires_at' => Carbon::now()->addMonths(6)
        ]);

        Voucher::create([
            'code' => 'CINEGOFREE',
            'discount_type' => 'percentage',
            'discount_value' => 100,
            'min_spend' => 0,
            'expires_at' => Carbon::now()->addMonths(6)
        ]);

        // 7. Tạo Showtimes & Price Configs cho ngày hôm nay
        $today = Carbon::today();
        
        $showtimesData = [
            [
                'movie' => $movies[0],
                'room' => $rooms[0],
                'starts' => ['10:00', '13:30', '17:00', '20:30']
            ],
            [
                'movie' => $movies[1],
                'room' => $rooms[1],
                'starts' => ['11:15', '15:00', '18:45', '22:15']
            ]
        ];

        foreach ($showtimesData as $sData) {
            foreach ($sData['starts'] as $startTimeStr) {
                list($hours, $minutes) = explode(':', $startTimeStr);
                $start = $today->copy()->setHour((int)$hours)->setMinute((int)$minutes);
                $end = $start->copy()->addMinutes($sData['movie']->duration);
                
                $showtime = Showtime::create([
                    'movie_id' => $sData['movie']->id,
                    'room_id' => $sData['room']->id,
                    'start_time' => $start,
                    'end_time' => $end,
                    'format' => $sData['room']->id == 2 ? '3D' : '2D',
                    'translation' => $sData['movie']->id == 2 ? 'Thuyết minh' : 'Phụ đề',
                    'status' => 'active'
                ]);

                // Tạo Price Configs cho Suất chiếu này
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
}
