<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActionLog;
use App\Models\Showtime;
use App\Models\Seat;
use App\Models\SeatHold;
use App\Models\BookingDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ShowtimeController extends Controller
{
    public function index()
    {
        $showtimes = Showtime::with(['movie', 'room'])
            ->orderByDesc('start_time')
            ->orderByDesc('id')
            ->get()
            ->map(function ($st) {
            $snapshot = $st->pricing_snapshot ?? [];
            $prices = [
                'standard' => $snapshot['standard_price'] ?? $snapshot['standard'] ?? 50000,
                'vip' => $snapshot['vip_price'] ?? $snapshot['vip'] ?? 70000,
                'couple' => $snapshot['couple_price'] ?? $snapshot['couple'] ?? 120000,
            ];

            return [
                'id' => $st->id,
                'movie_id' => $st->movie_id,
                'room_id' => $st->room_id,
                'start_time' => $st->start_time ? $st->start_time->toIso8601String() : null,
                'end_time' => $st->end_time ? $st->end_time->toIso8601String() : null,
                'format' => $st->format,
                'translation' => $st->translation,
                'status' => $st->status ?? 'active',
                'movie_title' => $st->movie ? $st->movie->title : 'Không xác định',
                'room_name' => $st->room ? $st->room->name : 'Không xác định',
                'prices' => $prices,
                'pricing_snapshot' => $snapshot,
            ];
        });

        return response()->json($showtimes, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'movie_id' => 'required|exists:movies,id',
            'room_id' => 'required|exists:rooms,id',
            'start_date' => 'required|date',
            'times' => 'required|array',
            'times.*' => 'required|string',
            'end_date' => 'nullable|date',
            'format' => 'required|string',
            'translation' => 'required|string',
        ]);

        $movie = \App\Models\Movie::findOrFail($request->movie_id);
        $duration = $movie->duration + 15; 

        $startDate = \Carbon\Carbon::parse($request->start_date)->startOfDay();
        $endDate = $request->has('end_date') && $request->end_date ? \Carbon\Carbon::parse($request->end_date)->startOfDay() : $startDate->copy();

        $showtimeDates = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            foreach ($request->times as $timeStr) {
                if (empty($timeStr)) continue;
                $timeParts = explode(':', $timeStr);
                $currentStart = $currentDate->copy()->setHour((int)$timeParts[0])->setMinute((int)$timeParts[1]);
                $currentEnd = $currentStart->copy()->addMinutes($duration);
                
                $conflict = \App\Models\Showtime::where('room_id', $request->room_id)
                    ->where('status', 'active')
                    ->where('start_time', '<', $currentEnd)
                    ->where('end_time', '>', $currentStart)
                    ->with('movie:id,title')
                    ->first();

                // Kiểm tra trùng lịch với các suất vừa được thêm vào mảng tạm (chưa save DB)
                foreach ($showtimeDates as $temp) {
                    if ($currentEnd > $temp['start'] && $currentStart < $temp['end']) {
                        return response()->json([
                            'success' => false,
                            'message' => "Phòng bị trùng lịch trong mảng đang tạo! Ngày " . $currentStart->format('d/m/Y') . " lúc " . $currentStart->format('H:i') . " vướng với suất " . $temp['start']->format('H:i') . " - " . $temp['end']->format('H:i') . " vừa chọn.",
                        ], 422);
                    }
                }

                if ($conflict) {
                    $clashName = $conflict->movie ? $conflict->movie->title : 'một suất chiếu khác';

                    return response()->json([
                        'success' => false,
                        'message' => "Phòng kín lịch ngày " . $currentStart->format('d/m/Y') . " lúc " . $currentStart->format('H:i') . "! Đang vướng suất \"{$clashName}\" từ "
                            . Carbon::parse($conflict->start_time)->format('H:i') . ' đến '
                            . Carbon::parse($conflict->end_time)->format('H:i')
                            . '. Vui lòng kiểm tra lại!',
                        'conflict' => [
                            'id'         => $conflict->id,
                            'movie'      => $clashName,
                            'start_time' => Carbon::parse($conflict->start_time)->toIso8601String(),
                            'end_time'   => Carbon::parse($conflict->end_time)->toIso8601String(),
                        ],
                    ], 422);
                }
                
                $showtimeDates[] = [
                    'start' => $currentStart->copy(),
                    'end' => $currentEnd->copy()
                ];
            }
            $currentDate->addDay();
        }

        $movie = \App\Models\Movie::find($request->movie_id);

        $insertedShows = [];
        foreach ($showtimeDates as $dates) {
            $isSneakShow = false;
            if ($movie && $movie->release_date && $dates['start']->copy()->startOfDay()->lt(Carbon::parse($movie->release_date)->startOfDay())) {
                $isSneakShow = true;
            }

            $snapshot = \App\Services\PricingService::createPricingSnapshot($dates['start'], $request->movie_id);

            $insertedShows[] = Showtime::create([
                'movie_id' => $request->movie_id,
                'room_id' => $request->room_id,
                'start_time' => $dates['start'],
                'end_time' => $dates['end'],
                'format' => $request->format,
                'translation' => $request->translation,
                'status' => 'active',
                'is_sneak_show' => $isSneakShow,
                'pricing_snapshot' => $snapshot
            ]);
        }

        ActionLog::create([
            'user_id' => Auth::id(),
            'action' => 'create_showtime',
            'target_type' => 'showtimes',
            'target_id' => $insertedShows[0]->id ?? null,
            'details' => ['movie_id' => $request->movie_id, 'room_id' => $request->room_id, 'count' => count($insertedShows)],
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thêm suất chiếu thành công',
            'data' => count($insertedShows) === 1 ? $insertedShows[0] : $insertedShows
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $showtime = Showtime::find($id);
        if (!$showtime) {
            return response()->json(['message' => 'Không tìm thấy suất chiếu'], 404);
        }

        $request->validate([
            'movie_id' => 'required|exists:movies,id',
            'room_id' => 'required|exists:rooms,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'format' => 'required|string',
            'translation' => 'required|string',
        ]);

        $start = Carbon::parse($request->start_time);
        $end   = Carbon::parse($request->end_time);

        // Kiểm tra trùng lịch bỏ qua suất chiếu hiện tại
        $conflict = Showtime::where('room_id', $request->room_id)
            ->where('status', 'active')
            ->where('id', '!=', $id)
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->with('movie:id,title')
            ->first();

        if ($conflict) {
            $clashName = $conflict->movie ? $conflict->movie->title : 'một suất chiếu khác';

            return response()->json([
                'success' => false,
                'message' => "Phòng đang chiếu \"{$clashName}\" từ "
                    . $conflict->start_time->format('H:i d/m/Y') . ' đến '
                    . $conflict->end_time->format('H:i d/m/Y')
                    . '. Vui lòng chọn khung giờ khác!',
            ], 422);
        }

        $movie = \App\Models\Movie::find($request->movie_id);
        $isSneakShow = false;
        if ($movie && $movie->release_date && $start->startOfDay()->lt(Carbon::parse($movie->release_date)->startOfDay())) {
            $isSneakShow = true;
        }

        $showtime->update([
            'movie_id' => $request->movie_id,
            'room_id' => $request->room_id,
            'start_time' => $start,
            'end_time' => $end,
            'format' => $request->format,
            'translation' => $request->translation,
            'is_sneak_show' => $isSneakShow
        ]);



        ActionLog::create([
            'user_id' => Auth::id(),
            'action' => 'edit_showtime',
            'target_type' => 'showtimes',
            'target_id' => $showtime->id,
            'details' => ['movie_id' => $request->movie_id, 'room_id' => $request->room_id, 'start_time' => $request->start_time],
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật suất chiếu thành công',
            'data' => $showtime
        ], 200);
    }

    public function destroy($id)
    {
        $showtime = Showtime::find($id);
        if (!$showtime) {
            return response()->json(['message' => 'Không tìm thấy suất chiếu'], 404);
        }

        $showtime->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa suất chiếu thành công'
        ], 200);
    }

    /**
     * Lấy danh sách ghế của suất chiếu kèm trạng thái khả dụng thực tế.
     */
    public function getSeats($id)
    {
        $showtime = Showtime::with('priceConfigs')->find($id);
        if (!$showtime) {
            return response()->json(['message' => 'Không tìm thấy suất chiếu'], 404);
        }

        // Đọc giá từ pricing_snapshot (chốt lúc tạo suất chiếu)
        $snapshot = $showtime->pricing_snapshot ?? [];
        $prices = [
            'standard' => (float) ($snapshot['standard_price'] ?? 0),
            'vip'      => (float) ($snapshot['vip_price'] ?? 0),
            'couple'   => (float) ($snapshot['couple_price'] ?? 0),
        ];

        // Nếu suất chiếu cũ chưa có snapshot, fallback về PricingRule hệ thống
        if (!$prices['standard'] && !$prices['vip'] && !$prices['couple']) {
            $rule = \App\Models\PricingRule::first();
            $prices = [
                'standard' => (float) ($rule?->standard_price ?? 50000),
                'vip'      => (float) ($rule?->vip_price ?? 70000),
                'couple'   => (float) ($rule?->couple_price ?? 120000),
            ];
        }

        $now = Carbon::now();

        // 1. Dọn dẹp tất cả các giữ ghế đã hết hạn trên hệ thống
        SeatHold::where('expires_at', '<=', $now)->delete();

        

        // 3. Lấy toàn bộ ghế của phòng chiếu
        $seats = Seat::where('room_id', $showtime->room_id)->get();

        // 3.1. Dọn các hàng giữ ghế đã hết hạn của suất chiếu này
        SeatHold::where('showtime_id', $showtime->id)
            ->where('expires_at', '<=', $now)
            ->delete();

        // 4. Lấy danh sách ghế đã được đặt mua thành công (payment_status = paid)
        $bookedSeatIds = BookingDetail::join('bookings', 'booking_details.booking_id', '=', 'bookings.id')
            ->where('bookings.showtime_id', $showtime->id)
            ->where('bookings.payment_status', 'paid')
            ->pluck('booking_details.seat_id')
            ->toArray();

        $bookedDetails = BookingDetail::with('booking.user')
            ->join('bookings', 'booking_details.booking_id', '=', 'bookings.id')
            ->where('bookings.showtime_id', $showtime->id)
            ->where('bookings.payment_status', 'paid')
            ->select('booking_details.*', 'bookings.booking_code', 'bookings.user_id')
            ->get()
            ->keyBy('seat_id');

        // 5. Lấy danh sách ghế đang bị giữ bởi người khác (expires_at > now)
        $heldDetails = SeatHold::with('user')
            ->where('showtime_id', $showtime->id)
            ->where('expires_at', '>', $now)
            ->get()
            ->keyBy('seat_id');
            
        $heldSeatIds = $heldDetails->keys()->toArray();

        // Lấy user hiện tại (có thể null nếu chưa đăng nhập)
        $currentUserId = auth('sanctum')->id();

        // Lấy danh sách ghế đang bị khóa (bảo trì/sự cố) trong khoảng thời gian diễn ra suất chiếu
        $lockedSeatIds = \App\Models\SeatLock::where('room_id', $showtime->room_id)
            ->where('start_time', '<', $showtime->end_time)
            ->where('end_time', '>', $showtime->start_time)
            ->pluck('seat_id')
            ->toArray();

        // 6. Định dạng đầu ra khớp chính xác với frontend yêu cầu
        $formattedSeats = $seats->map(function ($seat) use ($bookedSeatIds, $heldSeatIds, $lockedSeatIds, $prices, $bookedDetails, $heldDetails, $currentUserId) {
            $status = 'available';
            if ($seat->status === 'broken' || in_array($seat->id, $lockedSeatIds)) {
                $status = 'broken';
            } elseif (in_array($seat->id, $bookedSeatIds)) {
                $status = 'sold';
            } elseif (in_array($seat->id, $heldSeatIds)) {
                $status = 'holding';
            }

            $result = [
                'id' => $seat->id,
                'row_name' => $seat->row,
                'seat_number' => $seat->number,
                'status' => $status,
                'type' => $seat->type, 
                'price' => $prices[$seat->type] ?? 0, 
            ];

            if (isset($bookedDetails[$seat->id])) {
                $detail = $bookedDetails[$seat->id];
                $result['booking_detail_id'] = $detail->id;
                $result['booking_id'] = $detail->booking_id;
                $result['booking_code'] = $detail->booking_code;
                $result['customer_name'] = $detail->booking->user->name ?? 'N/A';
                $result['customer_email'] = $detail->booking->user->email ?? 'N/A';
                $result['customer_phone'] = $detail->booking->user->phone ?? 'N/A';
            } elseif ($status === 'holding' && isset($heldDetails[$seat->id])) {
                $hold = $heldDetails[$seat->id];
                $isMyHold = $currentUserId && $hold->user_id == $currentUserId;
                $result['is_held_by_me']   = $isMyHold;
                $result['hold_expires_at'] = $hold->expires_at; // luôn trả về để FE restore timer
                if (!$isMyHold) {
                    // Chỉ lộ thông tin holder nếu không phải chính mình (dùng cho admin/nhân viên)
                    $result['holder_name']  = $hold->user->name  ?? 'Khách';
                    $result['holder_email'] = $hold->user->email ?? 'N/A';
                    $result['holder_phone'] = $hold->user->phone ?? 'N/A';
                }
            }

            return $result;
        });

        return response()->json([
            'seats' => $formattedSeats,
            'prices' => $prices,
        ], 200);
    }

    public function getShowtimesByMovie(Request $request, $id)
    {
        $date = $request->query('date');

        $query = Showtime::with('room')
            ->where('movie_id', $id)
            ->where('status', 'active');

        if ($date) {
            // Khi có ngày cụ thể: lấy TẤT CẢ suất của ngày đó (kể cả đã qua giờ)
            // để nhân viên/khách vẫn thấy toàn bộ lịch của ngày hôm đó
            $query->whereDate('start_time', $date);
        } else {
            // Khi không có ngày: chỉ lấy suất từ bây giờ trở đi
            $query->where('start_time', '>=', now());
        }

        $showtimes = $query->get();

        $grouped = $showtimes->groupBy('room_id')->map(function ($items) {
            $room = $items->first()->room;

            // Tổng số ghế thực tế của phòng (loại bỏ ghế ẩn/xóa — không phải ghế thật)
            $totalSeats = Seat::where('room_id', $room->id)
                ->whereNotIn('type', ['hidden', 'deleted', 'couple_hidden'])
                ->where('status', '!=', 'broken')
                ->count();

            return [
                'roomId' => $room->id,
                'roomName' => $room->name,
                'room_description' => $room->description,
                'showtimes' => $items->map(function ($showtime) use ($totalSeats) {
                    $now = Carbon::now();

                    // Ghế đã đặt thành công (payment_status = paid)
                    $bookedCount = BookingDetail::join('bookings', 'booking_details.booking_id', '=', 'bookings.id')
                        ->where('bookings.showtime_id', $showtime->id)
                        ->where('bookings.payment_status', 'paid')
                        ->count();

                    // Ghế đang bị giữ bởi người khác (chưa hết hạn)
                    $heldCount = SeatHold::where('showtime_id', $showtime->id)
                        ->where('expires_at', '>', $now)
                        ->count();

                    $available = max($totalSeats - $bookedCount - $heldCount, 0);

                    return [
                        'id' => $showtime->id,
                        'start_time' => \Carbon\Carbon::parse($showtime->start_time)->format('H:i'),
                        'start_date' => \Carbon\Carbon::parse($showtime->start_time)->format('Y-m-d'),
                        'start_time_full' => \Carbon\Carbon::parse($showtime->start_time)->toISOString(),
                        'available_seats' => $available,
                        'room_name' => $showtime->room->name,
                        'room_description' => $showtime->room->description,
                        'is_sneak_show' => $showtime->is_sneak_show,
                    ];
                })->values()
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $grouped
        ]);
    }

    public function getAvailableDates($id = null)
    {
        $query = Showtime::whereDate('start_time', '>=', now()->toDateString());
        
        if ($id) {
            $query->where('movie_id', $id);
        }

        $dates = $query->orderBy('start_time')
            ->pluck('start_time')
            ->map(function ($date) {
                return \Carbon\Carbon::parse($date)->toDateString();
            })
            ->unique()
            ->values()
            ->take(7);

        return response()->json([
            'success' => true,
            'data' => $dates
        ]);
    }

    public function getShowtimesByDate(Request $request)
    {
        $date = $request->query('date', now()->toDateString());
        $includeAll = $request->boolean('all', false);

        $query = Showtime::with(['movie.genres', 'room'])
            ->whereDate('start_time', $date)
            ->where('status', 'active')
            ->orderBy('start_time', 'asc');

        if (!$includeAll) {
            $query->where('start_time', '>=', now());
        }

        $showtimes = $query->get();

        $grouped = $showtimes->groupBy('movie_id')->map(function ($items) {
            $movie = $items->first()->movie;

            return [
                'movie_id' => $movie->id,
                'title' => $movie->title,
                'poster_url' => $movie->poster_url,
                'rating' => $movie->rating,
                'genres' => $movie->genres->pluck('name'),
                'showtimes' => $items->map(function ($st) {
                    return [
                        'id' => $st->id,
                        'start_time' => \Carbon\Carbon::parse($st->start_time)->format('H:i'),
                        'end_time' => \Carbon\Carbon::parse($st->end_time)->format('H:i'),
                        'format' => $st->format,
                        'translation' => $st->translation,
                        'room_id' => $st->room_id,
                        'room_name' => $st->room->name,
                        'room_description' => $st->room->description,
                        'is_sneak_show' => $st->is_sneak_show
                    ];
                })->values()
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $grouped
        ]);
    }

    public function suggestPrice(Request $request)
    {
        $request->validate([
            'movie_id' => 'required|exists:movies,id',
            'start_time' => 'required|date',
            'format' => 'required|string'
        ]);

        $movie = \App\Models\Movie::find($request->movie_id);
        $start = Carbon::parse($request->start_time);
        
        $rule = \App\Models\PricingRule::first();
        if (!$rule) {
            $rule = new \App\Models\PricingRule([
                'standard_price' => 50000,
                'vip_price' => 70000,
                'couple_price' => 120000,
                'weekend_surcharge' => 10000,
                'happy_hour_discount' => 10000,
                'format_3d_surcharge' => 30000,
                'sneak_show_surcharge' => 20000
            ]);
        }

        $basePrices = [
            'standard' => $rule->standard_price,
            'vip' => $rule->vip_price,
            'couple' => $rule->couple_price
        ];

        $appliedRules = [];
        $isSneakShow = false;

        if ($movie && $movie->release_date && $start->startOfDay()->lt(Carbon::parse($movie->release_date)->startOfDay())) {
            $isSneakShow = true;
            $appliedRules[] = 'Suất chiếu sớm (+' . number_format($rule->sneak_show_surcharge) . 'đ)';
            foreach ($basePrices as $k => $v) $basePrices[$k] += $rule->sneak_show_surcharge;
        }

        if ($start->isWeekend()) {
            $appliedRules[] = 'Cuối tuần (+' . number_format($rule->weekend_surcharge) . 'đ)';
            foreach ($basePrices as $k => $v) $basePrices[$k] += $rule->weekend_surcharge;
        }

        if ($start->hour < 17) {
            $appliedRules[] = 'Giờ vàng trước 17h (-' . number_format($rule->happy_hour_discount) . 'đ)';
            foreach ($basePrices as $k => $v) $basePrices[$k] -= $rule->happy_hour_discount;
        }

        if (strtoupper($request->format) === '3D') {
            $appliedRules[] = 'Định dạng 3D (+' . number_format($rule->format_3d_surcharge) . 'đ)';
            foreach ($basePrices as $k => $v) $basePrices[$k] += $rule->format_3d_surcharge;
        }

        if (empty($appliedRules)) {
            $appliedRules[] = 'Giá tiêu chuẩn';
        }

        return response()->json([
            'success' => true,
            'is_sneak_show' => $isSneakShow,
            'suggested_prices' => $basePrices,
            'applied_rules' => $appliedRules
        ]);
    }
}


