<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Movie;
use App\Models\Review;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('role', 'customer')->take(5)->get();
        if ($users->count() < 1) { 
            $users = [User::factory()->create(['role'=>'customer'])]; 
        }
        $movies = Movie::take(5)->get();

        $contents = [
            'Phim rất hay, kỹ xảo hoành tráng!',
            'Nội dung hơi khó hiểu nhưng xem rất cuốn.',
            'Tuyệt vời, 10 điểm không có nhưng.',
            'Phim khá tệ, không như kỳ vọng.',
            'Diễn viên đóng quá đạt, âm thanh xuất sắc.'
        ];

        foreach ($movies as $movie) {
            foreach ($users as $index => $user) {
                if (!Review::where('user_id', $user->id)->where('movie_id', $movie->id)->exists()) {
                    Review::create([
                        'user_id' => $user->id,
                        'movie_id' => $movie->id,
                        'rating' => rand(6, 10),
                        'comment' => $contents[$index % count($contents)],
                        'is_hidden' => rand(0, 1) == 1
                    ]);
                }
            }
        }
    }
}
