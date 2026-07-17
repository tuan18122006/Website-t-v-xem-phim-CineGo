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
            ],
            [
                'title' => 'Avatar: Dòng Chảy Của Nước',
                'description' => 'Gia đình Sully phải rời bỏ quê nhà và khám phá những vùng đất mới của Pandora, đồng thời đối mặt với một mối đe dọa quen thuộc.',
                'duration' => 192,
                'release_date' => Carbon::now()->subDays(300)->toDateString(),
                'status' => 'showing',
                'poster_url' => 'https://image.tmdb.org/t/p/original/t6HIqrNDgw1gDOhx6YeZaK1v1F9.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=d9MyW72ELq0',
                'rating' => 'T13',
            ],
            [
                'title' => 'Spider-Man: No Way Home',
                'description' => 'Danh tính của Người Nhện bị tiết lộ, khiến anh phải nhờ đến sự giúp đỡ của Doctor Strange, dẫn đến những hậu quả đa vũ trụ khôn lường.',
                'duration' => 148,
                'release_date' => Carbon::now()->subDays(500)->toDateString(),
                'status' => 'showing',
                'poster_url' => 'https://image.tmdb.org/t/p/original/1g0dhYtq4irTY1R80v63H06FMCV.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=JfVOs4VSpmA',
                'rating' => 'T13',
            ],
            [
                'title' => 'Avengers: Endgame',
                'description' => 'Sau cú búng tay của Thanos, các siêu anh hùng còn lại phải tập hợp một lần cuối để đảo ngược thảm kịch và khôi phục trật tự vũ trụ.',
                'duration' => 181,
                'release_date' => Carbon::now()->subDays(1500)->toDateString(),
                'status' => 'showing',
                'poster_url' => 'https://image.tmdb.org/t/p/original/or06FN3Dka5tukK1e9sl16pB3iy.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=TcMBFSGVi1c',
                'rating' => 'T13',
            ],
            [
                'title' => 'John Wick: Chapter 4',
                'description' => 'John Wick khám phá ra cách để đánh bại High Table, nhưng trước khi anh có thể giành được tự do, anh phải đối mặt với một kẻ thù mới.',
                'duration' => 169,
                'release_date' => Carbon::now()->subDays(30)->toDateString(),
                'status' => 'showing',
                'poster_url' => 'https://image.tmdb.org/t/p/original/rktDFPbfHfUbArZ6OOOKsXcv0Bm.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=qEVUtrk8_B4',
                'rating' => 'T18',
            ],
            [
                'title' => 'Phim Anh Em Super Mario',
                'description' => 'Mario và Luigi bắt đầu một hành trình qua Vương quốc Nấm để cứu Công chúa Peach khỏi tên quái vật Bowser.',
                'duration' => 92,
                'release_date' => Carbon::now()->subDays(100)->toDateString(),
                'status' => 'showing',
                'poster_url' => 'https://image.tmdb.org/t/p/original/qNBAXBIQlnOFiAwvd3InfC4x1x2.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=TnGl01FkMMo',
                'rating' => 'G',
            ],
            [
                'title' => 'Oppenheimer',
                'description' => 'Câu chuyện về nhà vật lý J. Robert Oppenheimer và vai trò của ông trong việc phát triển bom nguyên tử trong Thế chiến II.',
                'duration' => 180,
                'release_date' => Carbon::now()->subDays(200)->toDateString(),
                'status' => 'showing',
                'poster_url' => 'https://image.tmdb.org/t/p/original/8Gxv8gSFCU0XGDykEGv7zR1n2ua.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=uYPbbksJxIg',
                'rating' => 'T16',
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
