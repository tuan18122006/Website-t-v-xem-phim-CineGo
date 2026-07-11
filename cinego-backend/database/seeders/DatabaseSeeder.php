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
use App\Models\Review;
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

        // Tạo một số tài khoản reviewer mẫu
        $reviewers = [
            User::create([
                'name' => 'Nguyễn Thùy Linh',
                'email' => 'reviewer1@cinego.com',
                'password' => bcrypt('password'),
                'role' => 'customer',
                'phone' => '0912345678',
                'age' => 24
            ]),
            User::create([
                'name' => 'Lê Hoàng',
                'email' => 'reviewer2@cinego.com',
                'password' => bcrypt('password'),
                'role' => 'customer',
                'phone' => '0981222333',
                'age' => 28
            ]),
            User::create([
                'name' => 'Trần Thị Mai',
                'email' => 'reviewer3@cinego.com',
                'password' => bcrypt('password'),
                'role' => 'customer',
                'phone' => '0977888999',
                'age' => 26
            ]),
            User::create([
                'name' => 'Phạm Văn Quân',
                'email' => 'reviewer4@cinego.com',
                'password' => bcrypt('password'),
                'role' => 'customer',
                'phone' => '0901234567',
                'age' => 30
            ]),
            User::create([
                'name' => 'Đỗ Minh Hằng',
                'email' => 'reviewer5@cinego.com',
                'password' => bcrypt('password'),
                'role' => 'customer',
                'phone' => '0933445566',
                'age' => 22
            ])
        ];

        $movieReviews = [
            [
                'movie' => $movies[0],
                'reviews' => [
                    ['user' => $reviewers[0], 'rating' => 5, 'comment' => 'Một siêu phẩm hành động viễn tưởng mạnh mẽ, hình ảnh đa vũ trụ thật sự choáng ngợp. Tôi đặc biệt thích phần phục trang và kỹ xảo.'],
                    ['user' => $reviewers[1], 'rating' => 4, 'comment' => 'Cốt truyện hơi phức tạp nhưng vẫn rất cuốn. Âm nhạc và ánh sáng tạo cảm giác hoành tráng.'],
                    ['user' => $reviewers[2], 'rating' => 5, 'comment' => 'Doctor Strange đem đến cảm giác mới mẻ, pha trộn yếu tố hài hước và kịch tính rất tốt. Rất đáng xem!'],
                    ['user' => $reviewers[3], 'rating' => 4, 'comment' => 'Diễn xuất ấn tượng, nhiều tình tiết bất ngờ. Phù hợp với những bạn thích thể loại viễn tưởng.'],
                    ['user' => $reviewers[4], 'rating' => 5, 'comment' => 'Tôi thích nhất phân đoạn chiến thắng đầy sáng tạo, kết thúc để lại nhiều suy ngẫm.'],
                    ['user' => $reviewers[0], 'rating' => 4, 'comment' => 'Các màn kết hợp giữa Strange và các pháp sư khác rất sống động. Nhiều đoạn hài hước đúng lúc.'],
                    ['user' => $reviewers[1], 'rating' => 5, 'comment' => 'Nội dung nhiều lớp nhưng vẫn thuyết phục. Những pha hành động cuối phim rất đã.'],
                    ['user' => $reviewers[2], 'rating' => 5, 'comment' => 'Âm thanh và kỹ xảo cực đỉnh, tôi không thể rời mắt suốt 2 tiếng.'],
                    ['user' => $reviewers[3], 'rating' => 4, 'comment' => 'Một số ý tưởng khá mới lạ, tuy đôi khi hơi rối nhưng vẫn rất đáng trải nghiệm.'],
                    ['user' => $reviewers[4], 'rating' => 5, 'comment' => 'Mạch phim nhanh, cảm xúc tăng dần, đặc biệt thích cảnh chiến đấu tại đa vũ trụ.']
                ]
            ],
            [
                'movie' => $movies[1],
                'reviews' => [
                    ['user' => $reviewers[0], 'rating' => 5, 'comment' => 'Avatar vẫn giữ được chất hoành tráng, cảnh biển và kỹ xảo cực kỳ mãn nhãn. Xem rạp thì càng đã.'],
                    ['user' => $reviewers[1], 'rating' => 4, 'comment' => 'Câu chuyện nhân văn, cảnh quay dưới nước rất đẹp. Một vài đoạn hơi dài nhưng vẫn rất xứng đáng.'],
                    ['user' => $reviewers[2], 'rating' => 5, 'comment' => 'Âm nhạc và màu sắc phối hợp nhịp nhàng. Nên xem bằng định dạng IMAX nếu có thể.'],
                    ['user' => $reviewers[3], 'rating' => 4, 'comment' => 'Thế giới Pandora đẹp quá, tuyến nhân vật mới rất đáng chú ý. Mọi người nên trải nghiệm xem phim tại rạp.'],
                    ['user' => $reviewers[4], 'rating' => 5, 'comment' => 'Tác phẩm tuyệt vời, vừa mãn nhãn vừa sâu sắc. Đi xem cùng gia đình càng hợp lý.'],
                    ['user' => $reviewers[0], 'rating' => 5, 'comment' => 'Cảm giác như được lặn giữa đại dương Pandora, mọi thứ đều rất sống động.'],
                    ['user' => $reviewers[1], 'rating' => 4, 'comment' => 'Phần kỹ xảo dưới nước quá đẹp, nhưng mình muốn câu chuyện bớt rườm rà hơn.'],
                    ['user' => $reviewers[2], 'rating' => 5, 'comment' => 'Diễn xuất nhân vật rất nhập vai, nhất là những pha tình cảm gia đình.'],
                    ['user' => $reviewers[3], 'rating' => 4, 'comment' => 'Một vài đoạn hơi dài nhưng lại rất mãn nhãn về mặt hình ảnh.'],
                    ['user' => $reviewers[4], 'rating' => 5, 'comment' => 'Một trải nghiệm rạp phong phú, tôi ấn tượng với cảnh chiến đấu và thiết kế thế giới.'],
                ]
            ],
            [
                'movie' => $movies[2],
                'reviews' => [
                    ['user' => $reviewers[0], 'rating' => 5, 'comment' => 'Kẻ Kiến Tạo có nhịp phim khá căng, chủ đề AI được thể hiện rất sắc bén. Diễn xuất đáng khen.'],
                    ['user' => $reviewers[1], 'rating' => 4, 'comment' => 'Tôi thích những góc nhìn nhân văn về trí tuệ nhân tạo và con người. Kết thúc để lại nhiều suy nghĩ.'],
                    ['user' => $reviewers[2], 'rating' => 5, 'comment' => 'Hình ảnh đẹp, cách dựng cảnh hành động rất chất. Rất thích phong cách điện ảnh này.'],
                    ['user' => $reviewers[3], 'rating' => 4, 'comment' => 'Âm thanh và hiệu ứng chiến đấu tạo cảm giác hồi hộp. Phim hơi nặng đề tài nhưng cực kỳ đáng xem.'],
                    ['user' => $reviewers[4], 'rating' => 5, 'comment' => 'Một tác phẩm thú vị, vừa giải trí vừa có chiều sâu. Tôi recommend cho mọi người.'],
                    ['user' => $reviewers[0], 'rating' => 5, 'comment' => 'Cốt truyện kịch tính và ý nghĩa, phù hợp với người thích phim phản địa đàng.'],
                    ['user' => $reviewers[1], 'rating' => 4, 'comment' => 'Những cảnh đối thoại rất căng thẳng, tôi thích cách xây dựng tâm lý nhân vật.'],
                    ['user' => $reviewers[2], 'rating' => 5, 'comment' => 'Kỹ xảo lẫn âm thanh đều đạt chuẩn cao, tạo cảm giác đắm chìm.'],
                    ['user' => $reviewers[3], 'rating' => 4, 'comment' => 'Một số đoạn khá sâu nhưng vẫn dễ theo dõi với người yêu thích thể loại này.'],
                    ['user' => $reviewers[4], 'rating' => 5, 'comment' => 'Rất đáng xem nếu bạn thích phim trí tuệ và hành động phối hợp nhịp nhàng.']
                ]
            ]
        ];

        foreach ($movieReviews as $movieData) {
            foreach ($movieData['reviews'] as $reviewData) {
                Review::create([
                    'user_id' => $reviewData['user']->id,
                    'movie_id' => $movieData['movie']->id,
                    'rating' => $reviewData['rating'],
                    'comment' => $reviewData['comment']
                ]);
            }
        }

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

        // 8. Gọi thêm các Seeder phim khác (Dune, Godzilla, v.v.)
        $this->call([
            MovieSeeder::class,
        ]);
    }
}
