<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Article;
use App\Models\Movie;
use App\Models\Genre;
use Illuminate\Support\Str;

class ArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Tạo thêm một số bộ phim mới
        $moviesData = [
            [
                'title' => 'Lật Mặt 7: Một Điều Ước',
                'slug' => 'lat-mat-7-mot-dieu-uoc',
                'description' => 'Một bộ phim đầy cảm động về tình cảm gia đình của đạo diễn Lý Hải.',
                'release_date' => '2024-04-26',
                'duration' => 138,
                'poster_url' => 'https://media.lottecinemavn.com/Media/MovieFile/MovieImg/202404/11440_103_100010.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'status' => 'showing'
            ],
            [
                'title' => 'Mai',
                'slug' => 'mai',
                'description' => 'Câu chuyện về cuộc đời của Mai, một phụ nữ có số phận lận đận, vượt lên định kiến xã hội.',
                'release_date' => '2024-02-10',
                'duration' => 131,
                'poster_url' => 'https://media.lottecinemavn.com/Media/MovieFile/MovieImg/202402/11383_103_100004.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'status' => 'showing'
            ],
            [
                'title' => 'Godzilla x Kong: Đế Chế Mới',
                'slug' => 'godzilla-x-kong-de-che-moi',
                'description' => 'Cuộc chiến hoành tráng giữa Godzilla, Kong và các thế lực mới đe dọa Trái Đất.',
                'release_date' => '2024-03-29',
                'duration' => 115,
                'poster_url' => 'https://media.lottecinemavn.com/Media/MovieFile/MovieImg/202403/11417_103_100003.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'status' => 'showing'
            ],
            [
                'title' => 'Kung Fu Panda 4',
                'slug' => 'kung-fu-panda-4',
                'description' => 'Hành trình mới của chú gấu Po trong việc tìm kiếm người kế nhiệm Thần Long Đại Hiệp.',
                'release_date' => '2024-03-08',
                'duration' => 94,
                'poster_url' => 'https://media.lottecinemavn.com/Media/MovieFile/MovieImg/202403/11409_103_100005.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'status' => 'showing'
            ]
        ];

        $createdMovies = [];
        foreach ($moviesData as $data) {
            $movie = Movie::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
            $createdMovies[] = $movie;
            
            // Add a random genre to movie
            $genre = Genre::inRandomOrder()->first();
            if ($genre) {
                $movie->genres()->syncWithoutDetaching([$genre->id]);
            }
        }

        // 2. Lấy tất cả movies để gắn vào bài viết
        $allMovies = Movie::all();

        // 3. Tạo một số danh sách Top Phim
        $articlesData = [
            [
                'title' => 'Top 10 Phim Doanh Thu Cao Nhất Rạp Chiếu 2024',
                'excerpt' => 'Điểm danh những siêu phẩm làm mưa làm gió phòng vé Việt Nam trong nửa đầu năm 2024, từ bom tấn Hollywood đến phim Việt.',
                'content' => '<p>Năm 2024 đánh dấu sự trở lại mạnh mẽ của rạp chiếu phim với hàng loạt siêu phẩm phòng vé. Các bộ phim không chỉ đầu tư về mặt hình ảnh mà còn có nội dung sâu sắc. Dưới đây là danh sách những bộ phim đạt doanh thu cao nhất mà bạn nhất định phải xem.</p><p>Hãy cùng CineGo điểm qua những cái tên nổi bật nhất nhé!</p>',
                'thumbnail_url' => 'https://www.momo.vn/v2/w_900,f_webp/uploads/kinhtevimo/2024/02/19/phim-tet-2024-thumbnail_1708316400326.jpg',
                'views' => 12540,
                'is_published' => true,
                'published_at' => now()->subDays(5)
            ],
            [
                'title' => 'Top Phim Hành Động Kịch Tính Cuối Tuần Không Thể Bỏ Lỡ',
                'excerpt' => 'Bạn đang tìm kiếm những bộ phim giật gân, những pha hành động nghẹt thở? Danh sách này là dành cho bạn.',
                'content' => '<p>Cuối tuần là khoảng thời gian tuyệt vời để thư giãn với những bộ phim hành động bom tấn. Từ những màn rượt đuổi tốc độ cao đến các pha đấu súng mãn nhãn, các bộ phim này sẽ không làm bạn thất vọng.</p>',
                'thumbnail_url' => 'https://www.momo.vn/v2/w_900,f_webp/uploads/kinhtevimo/2024/03/12/phim-hanh-dong-my-thumbnail_1710214800326.jpg',
                'views' => 8420,
                'is_published' => true,
                'published_at' => now()->subDays(12)
            ],
            [
                'title' => 'Tuyển Tập Phim Hoạt Hình Dành Cho Gia Đình',
                'excerpt' => 'Những bộ phim hoạt hình mang thông điệp ý nghĩa, phù hợp để cả gia đình cùng nhau thưởng thức tại rạp.',
                'content' => '<p>Phim hoạt hình không chỉ dành cho trẻ em mà còn chứa đựng nhiều bài học cuộc sống cho người lớn. Hãy cùng tận hưởng những giây phút ấm áp bên gia đình với các tác phẩm hoạt hình xuất sắc nhất hiện nay.</p>',
                'thumbnail_url' => 'https://www.momo.vn/v2/w_900,f_webp/uploads/kinhtevimo/2024/04/05/phim-hoat-hinh-hay-nhat-thumbnail_1712288400326.jpg',
                'views' => 6100,
                'is_published' => true,
                'published_at' => now()->subDays(20)
            ]
        ];

        foreach ($articlesData as $index => $data) {
            $data['slug'] = Str::slug($data['title']) . '-' . rand(1000, 9999);
            $article = Article::create($data);

            // Gắn ngẫu nhiên 3-5 phim vào danh sách
            $movieCount = rand(3, 5);
            $selectedMovies = $allMovies->random(min($movieCount, $allMovies->count()));
            
            $rank = 1;
            $reviews = [
                'Một bộ phim không thể bỏ qua, hình ảnh xuất sắc.',
                'Nội dung ý nghĩa, diễn xuất tuyệt vời của dàn diễn viên chính.',
                'Âm nhạc lôi cuốn, kịch bản đầy bất ngờ và lôi cuốn người xem.',
                'Đứng đầu danh sách phim phải xem trong tháng này.',
                'Kỹ xảo hoành tráng, xứng đáng với từng xu bỏ ra.'
            ];

            foreach ($selectedMovies as $sm) {
                $article->movies()->attach($sm->id, [
                    'rank' => $rank++,
                    'review_text' => $reviews[array_rand($reviews)]
                ]);
            }
        }
    }
}
