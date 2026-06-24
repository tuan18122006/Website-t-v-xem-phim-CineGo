<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Movie;
use Carbon\Carbon;
use Illuminate\Support\Str;

class MovieSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $movies = [
            [
                'title' => 'Dune: Hành Tinh Cát - Phần 2',
                'description' => 'Paul Atreides tiếp tục hành trình trả thù cùng với Chani và người Fremen, đồng thời phải đối mặt với sự lựa chọn giữa tình yêu và định mệnh vũ trụ.',
                'duration' => 166,
                'release_date' => Carbon::now()->subDays(10)->toDateString(),
                'status' => 'showing',
                'poster_url' => 'https://image.tmdb.org/t/p/original/8b8R8l88Qje9dn9OE8v44L4766j.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=Way9Dexny3w',
                'rating' => 'T16',
            ],
            [
                'title' => 'Godzilla x Kong: Đế Chế Mới',
                'description' => 'Hai siêu quái vật huyền thoại phải gác lại mối thù để đối đầu với một thế lực bí ẩn đang đe dọa sự sống của cả Trái Đất.',
                'duration' => 115,
                'release_date' => Carbon::now()->subDays(2)->toDateString(),
                'status' => 'showing',
                'poster_url' => 'https://image.tmdb.org/t/p/original/tMefBSflR6PGQLvLuPEt12T68Qy.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=lV1OOlGwExM',
                'rating' => 'T13',
            ],
            [
                'title' => 'Kung Fu Panda 4',
                'description' => 'Po được chọn để trở thành Thủ lĩnh tinh thần của Thung lũng Bình Yên và cần tìm một Chiến binh Thần Long mới để kế nhiệm mình.',
                'duration' => 94,
                'release_date' => Carbon::now()->addDays(5)->toDateString(),
                'status' => 'upcoming',
                'poster_url' => 'https://image.tmdb.org/t/p/original/kDp1vUBnMpe8ak4rjgl3cLELqjU.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=_inKs4eeHMM',
                'rating' => 'G',
            ],
            [
                'title' => 'Lật Mặt 7: Một Điều Ước',
                'description' => 'Một câu chuyện cảm động về tình mẹ con và những góc khuất trong cuộc sống gia đình người Việt, đầy ý nghĩa và đẫm nước mắt.',
                'duration' => 138,
                'release_date' => Carbon::now()->subDays(20)->toDateString(),
                'status' => 'showing',
                'poster_url' => 'https://m.media-amazon.com/images/M/MV5BMTEzODMwZmEtOGI0OC00OWFjLThmY2YtNmRjYjRiOWRjNTgzXkEyXkFqcGdeQXVyMTA3MDk2NDg2._V1_.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=ZfVl-bN9FMI',
                'rating' => 'T13',
            ]
        ];

        foreach ($movies as $movieData) {
            $movieData['slug'] = Str::slug($movieData['title']);
            $movie = Movie::updateOrCreate(
                ['title' => $movieData['title']], // Tránh trùng lặp
                $movieData
            );

            // Gán thể loại ngẫu nhiên nếu chưa có (1: Hành động, 2: Hoạt hình, 3: Viễn tưởng)
            if ($movie->genres()->count() == 0) {
                if (str_contains($movie->title, 'Kung Fu')) {
                    $movie->genres()->attach([2, 5]); // Hoạt hình, Phiêu lưu
                } elseif (str_contains($movie->title, 'Dune')) {
                    $movie->genres()->attach([1, 3]); // Hành động, Viễn tưởng
                } elseif (str_contains($movie->title, 'Lật Mặt')) {
                    $movie->genres()->attach([4]); // Kinh dị / Tâm lý
                } else {
                    $movie->genres()->attach([1]); // Hành động
                }
            }
        }
    }
}
