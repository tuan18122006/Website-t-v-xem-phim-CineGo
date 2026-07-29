<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BookingDetail;
use App\Models\Combo;
use App\Models\Movie;
use App\Models\PriceConfig;
use App\Models\Showtime;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    /**
     * Trợ lý AI CineGo — trả lời khách bằng cách TRA DỮ LIỆU THẬT qua các "công cụ"
     * (Gemini function calling). Miễn phí qua Google Gemini free tier.
     */
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
            'history' => 'nullable|array',
        ]);

        $key = config('services.gemini.key');
        $model = config('services.gemini.model', 'gemini-flash-latest');

        // Chưa cấu hình key -> trả lời mock để test giao diện
        if (empty($key)) {
            return response()->json([
                'reply' => 'Xin chào! Mình là trợ lý CineGo 🎬. (Hiện chưa cấu hình GEMINI_API_KEY nên đang chạy chế độ demo.) '
                    . 'Bạn có thể hỏi mình về phim đang chiếu, lịch chiếu, giá vé hoặc combo bắp nước nhé!',
                'mock' => true,
            ], 200);
        }

        // Dựng lịch sử hội thoại theo định dạng Gemini
        $contents = [];
        foreach ((array) $request->input('history', []) as $turn) {
            $role = ($turn['role'] ?? 'user') === 'bot' ? 'model' : ($turn['role'] ?? 'user');
            $text = trim((string) ($turn['text'] ?? ''));
            if ($text === '') continue;
            $contents[] = ['role' => $role, 'parts' => [['text' => $text]]];
        }
        $contents[] = ['role' => 'user', 'parts' => [['text' => $request->input('message')]]];

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";
        $payload = [
            'system_instruction' => ['parts' => [['text' => $this->systemPrompt()]]],
            'tools' => [['function_declarations' => $this->toolDeclarations()]],
        ];

        // Vòng lặp: gọi model -> nếu model gọi công cụ thì chạy DB rồi gửi kết quả lại
        for ($i = 0; $i < 5; $i++) {
            try {
                $res = Http::timeout(30)->post($url . '?key=' . $key, array_merge($payload, ['contents' => $contents]));
            } catch (\Throwable $e) {
                return response()->json(['reply' => 'Xin lỗi, mình đang gặp sự cố kết nối. Bạn thử lại sau giây lát nhé!'], 200);
            }

            if ($res->status() === 429) {
                return response()->json(['reply' => 'Trợ lý đang hơi quá tải 😅. Bạn thử lại sau vài giây nhé!'], 200);
            }
            if ($res->failed()) {
                return response()->json(['reply' => 'Xin lỗi, mình chưa trả lời được lúc này. Bạn thử lại sau nhé!'], 200);
            }

            $parts = $res->json('candidates.0.content.parts') ?? [];
            $calls = array_values(array_filter($parts, fn ($p) => isset($p['functionCall'])));

            // Không gọi công cụ nữa -> lấy câu trả lời cuối
            if (empty($calls)) {
                $text = collect($parts)->pluck('text')->filter()->implode("\n");
                if ($text === '') {
                    $text = 'Mình chưa rõ ý bạn lắm 😅. Bạn có thể hỏi về phim đang chiếu, lịch chiếu, giá vé hay combo bắp nước nhé!';
                }
                return response()->json(['reply' => $text], 200);
            }

            // Lưu lượt của model (chứa functionCall) rồi chạy từng công cụ.
            // Ép args rỗng về object để Gemini không hiểu nhầm thành list ([] -> {}).
            foreach ($parts as &$mp) {
                if (isset($mp['functionCall']) && empty($mp['functionCall']['args'])) {
                    $mp['functionCall']['args'] = (object) [];
                }
            }
            unset($mp);
            $contents[] = ['role' => 'model', 'parts' => $parts];
            $responseParts = [];
            foreach ($calls as $p) {
                $name = $p['functionCall']['name'] ?? '';
                $args = $p['functionCall']['args'] ?? [];
                $result = $this->runTool($name, (array) $args);
                $fr = ['name' => $name, 'response' => ['result' => $result]];
                if (!empty($p['functionCall']['id'])) {
                    $fr['id'] = $p['functionCall']['id']; // model mới (Gemini 3) yêu cầu khớp id
                }
                $responseParts[] = ['functionResponse' => $fr];
            }
            $contents[] = ['role' => 'user', 'parts' => $responseParts];
        }

        return response()->json(['reply' => 'Mình đã tra cứu nhưng chưa tổng hợp kịp. Bạn hỏi lại cụ thể hơn giúp mình nhé!'], 200);
    }

    /* ==================== SYSTEM PROMPT ==================== */
    private function systemPrompt(): string
    {
        return implode("\n", [
            'Bạn là "Trợ lý CineGo" — trợ lý ảo thân thiện của rạp chiếu phim CineGo, luôn trả lời bằng tiếng Việt, ngắn gọn, lịch sự, có thể dùng emoji vừa phải.',
            'Nhiệm vụ: giúp khách tìm phim đang chiếu, xem lịch chiếu, giá vé, combo bắp nước và hướng dẫn đặt vé.',
            'QUY TẮC QUAN TRỌNG: Với thông tin về phim, lịch chiếu, giá vé, combo — LUÔN gọi công cụ để lấy dữ liệu THẬT, KHÔNG tự bịa. Nếu công cụ không có dữ liệu, hãy nói thật là chưa có.',
            'Hướng dẫn đặt vé khi khách hỏi: (1) Chọn phim → (2) Chọn suất chiếu/ngày giờ → (3) Chọn ghế → (4) Chọn combo (nếu muốn) → (5) Thanh toán. Cần đăng nhập để đặt vé.',
            'Không trả lời các câu hỏi ngoài phạm vi rạp phim CineGo; lịch sự từ chối và gợi ý hỏi về phim/vé.',
        ]);
    }

    /* ==================== KHAI BÁO CÔNG CỤ (function declarations) ==================== */
    private function toolDeclarations(): array
    {
        return [
            [
                'name' => 'list_movies',
                'description' => 'Lấy danh sách các phim đang chiếu tại CineGo (tên, thể loại, thời lượng, độ tuổi, mô tả ngắn).',
                'parameters' => ['type' => 'object', 'properties' => (object) []],
            ],
            [
                'name' => 'get_showtimes',
                'description' => 'Lấy lịch chiếu (suất chiếu) của một phim. Có thể lọc theo ngày.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'movie_title' => ['type' => 'string', 'description' => 'Tên phim (có thể gần đúng)'],
                        'date' => ['type' => 'string', 'description' => 'Ngày dạng YYYY-MM-DD (tuỳ chọn)'],
                    ],
                    'required' => ['movie_title'],
                ],
            ],
            [
                'name' => 'get_ticket_prices',
                'description' => 'Lấy giá vé theo loại ghế (thường/VIP/đôi). Có thể truyền tên phim để lấy giá của phim đó.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'movie_title' => ['type' => 'string', 'description' => 'Tên phim (tuỳ chọn)'],
                    ],
                ],
            ],
            [
                'name' => 'list_combos',
                'description' => 'Lấy danh sách combo bắp nước đang bán tại CineGo kèm giá.',
                'parameters' => ['type' => 'object', 'properties' => (object) []],
            ],
            [
                'name' => 'get_available_seats',
                'description' => 'Xem số ghế còn trống của các suất chiếu một phim (có thể lọc theo ngày).',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'movie_title' => ['type' => 'string', 'description' => 'Tên phim'],
                        'date' => ['type' => 'string', 'description' => 'Ngày dạng YYYY-MM-DD (tuỳ chọn)'],
                    ],
                    'required' => ['movie_title'],
                ],
            ],
            [
                'name' => 'list_active_vouchers',
                'description' => 'Lấy danh sách mã giảm giá (voucher) đang còn hiệu lực để dùng khi đặt vé.',
                'parameters' => ['type' => 'object', 'properties' => (object) []],
            ],
        ];
    }

    /* ==================== CHẠY CÔNG CỤ (truy vấn DB) ==================== */
    private function runTool(string $name, array $args)
    {
        switch ($name) {
            case 'list_movies':
                return Movie::where('status', 'showing')->with('genres:id,name')
                    ->latest('id')->limit(20)->get()
                    ->map(fn ($m) => [
                        'ten_phim' => $m->title,
                        'the_loai' => $m->genres->pluck('name')->implode(', '),
                        'thoi_luong' => $m->duration ? $m->duration . ' phút' : null,
                        'do_tuoi' => $m->rating,
                        'mo_ta' => Str::limit(strip_tags((string) $m->description), 150),
                    ])->values();

            case 'get_showtimes':
                $movie = $this->findMovie($args['movie_title'] ?? '');
                if (!$movie) return ['loi' => 'Không tìm thấy phim phù hợp.'];
                $q = Showtime::where('movie_id', $movie->id)->where('status', 'active')->with('room:id,name');
                if (!empty($args['date'])) {
                    $q->whereDate('start_time', $args['date']);
                } else {
                    $q->where('start_time', '>=', Carbon::now());
                }
                $list = $q->orderBy('start_time')->limit(40)->get()->map(fn ($s) => [
                    'ngay' => Carbon::parse($s->start_time)->format('d/m/Y'),
                    'gio' => Carbon::parse($s->start_time)->format('H:i'),
                    'phong' => $s->room->name ?? '—',
                    'dinh_dang' => $s->format,
                    'dich' => $s->translation,
                ])->values();
                return ['phim' => $movie->title, 'so_suat' => $list->count(), 'suat_chieu' => $list];

            case 'get_ticket_prices':
                $labels = ['standard' => 'Ghế thường', 'vip' => 'Ghế VIP', 'couple' => 'Ghế đôi'];
                $q = PriceConfig::query();
                $movieName = null;
                if (!empty($args['movie_title'])) {
                    $movie = $this->findMovie($args['movie_title']);
                    if ($movie) {
                        $movieName = $movie->title;
                        $q->whereIn('showtime_id', Showtime::where('movie_id', $movie->id)->pluck('id'));
                    }
                }
                $rows = $q->select('seat_type')->selectRaw('MIN(price) as gia_min, MAX(price) as gia_max')
                    ->groupBy('seat_type')->get();
                if ($rows->isEmpty()) {
                    return ['gia_ve' => [
                        ['loai_ghe' => 'Ghế thường', 'gia' => '75.000đ'],
                        ['loai_ghe' => 'Ghế VIP', 'gia' => '95.000đ'],
                        ['loai_ghe' => 'Ghế đôi', 'gia' => '140.000đ'],
                    ], 'ghi_chu' => 'Giá tham khảo, có thể thay đổi theo suất.'];
                }
                $prices = $rows->map(function ($r) use ($labels) {
                    $min = (int) $r->gia_min;
                    $max = (int) $r->gia_max;
                    $gia = $min === $max ? number_format($min) . 'đ' : number_format($min) . 'đ – ' . number_format($max) . 'đ';
                    return ['loai_ghe' => $labels[$r->seat_type] ?? $r->seat_type, 'gia' => $gia];
                })->values();
                return array_filter(['phim' => $movieName, 'gia_ve' => $prices]);

            case 'list_combos':
                $combos = Combo::where('status', 'active')->get()->map(fn ($c) => [
                    'ten' => $c->name,
                    'gia' => number_format((int) $c->price) . 'đ',
                    'mo_ta' => Str::limit(strip_tags((string) $c->description), 120),
                ])->values();
                return $combos->isEmpty() ? ['thong_bao' => 'Hiện chưa có combo nào đang bán.'] : ['combo' => $combos];

            case 'get_available_seats':
                $movie = $this->findMovie($args['movie_title'] ?? '');
                if (!$movie) return ['loi' => 'Không tìm thấy phim phù hợp.'];
                $q = Showtime::where('movie_id', $movie->id)->where('status', 'active')->with('room:id,name,total_seats');
                if (!empty($args['date'])) {
                    $q->whereDate('start_time', $args['date']);
                } else {
                    $q->where('start_time', '>=', Carbon::now());
                }
                $list = $q->orderBy('start_time')->limit(15)->get()->map(function ($s) {
                    $total = (int) ($s->room->total_seats ?? 0);
                    $booked = BookingDetail::whereHas('booking', function ($b) use ($s) {
                        $b->where('showtime_id', $s->id)->where('payment_status', 'paid');
                    })->count();
                    $available = max($total - $booked, 0);
                    return [
                        'ngay' => Carbon::parse($s->start_time)->format('d/m/Y'),
                        'gio' => Carbon::parse($s->start_time)->format('H:i'),
                        'phong' => $s->room->name ?? '—',
                        'con_trong' => $available,
                        'tong_ghe' => $total,
                    ];
                })->values();
                return ['phim' => $movie->title, 'suat_chieu' => $list];

            case 'list_active_vouchers':
                $vouchers = Voucher::where('is_active', true)
                    ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', Carbon::now()))
                    ->where(fn ($q) => $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'))
                    ->get()
                    ->map(function ($v) {
                        $giam = $v->discount_type === 'percentage'
                            ? 'Giảm ' . rtrim(rtrim((string) $v->discount_value, '0'), '.') . '%'
                                . ($v->max_discount ? ' (tối đa ' . number_format((int) $v->max_discount) . 'đ)' : '')
                            : 'Giảm ' . number_format((int) $v->discount_value) . 'đ';
                        return array_filter([
                            'ma' => $v->code,
                            'uu_dai' => $giam,
                            'don_toi_thieu' => $v->min_spend ? number_format((int) $v->min_spend) . 'đ' : null,
                            'han_dung' => $v->expires_at ? Carbon::parse($v->expires_at)->format('d/m/Y') : 'Không giới hạn',
                        ]);
                    })->values();
                return $vouchers->isEmpty() ? ['thong_bao' => 'Hiện chưa có mã giảm giá nào đang hoạt động.'] : ['ma_giam_gia' => $vouchers];

            default:
                return ['loi' => 'Công cụ không tồn tại.'];
        }
    }

    private function findMovie(string $title): ?Movie
    {
        $title = trim($title);
        if ($title === '') return null;
        return Movie::where('title', 'like', "%{$title}%")->first();
    }
}
