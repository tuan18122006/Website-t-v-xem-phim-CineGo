<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingCombo;
use App\Models\BookingDetail;
use App\Models\Movie;
use App\Models\Showtime;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Tổng quan Dashboard: các thẻ số liệu + Top phim bán chạy.
     * Chỉ tính trên các đơn đã thanh toán (payment_status = 'paid').
     */
    public function overview()
    {
        $paidBookingIds = Booking::where('payment_status', 'paid')->pluck('id');

        $totalRevenue = (float) Booking::where('payment_status', 'paid')->sum('total_amount');
        $totalTickets = (int) BookingDetail::whereIn('booking_id', $paidBookingIds)->count();
        $totalCombos  = (int) BookingCombo::whereIn('booking_id', $paidBookingIds)->sum('quantity');
        $totalBookings = $paidBookingIds->count();

        $moviesCount    = (int) Movie::count();
        $todayShowtimes = (int) Showtime::whereDate('start_time', Carbon::today())->count();

        // === TOP PHIM BÁN CHẠY ===
        // Doanh thu vé theo phim = tổng giá vé (booking_details.price) của đơn đã thanh toán.
        $topRaw = DB::table('booking_details')
            ->join('bookings', 'booking_details.booking_id', '=', 'bookings.id')
            ->join('showtimes', 'bookings.showtime_id', '=', 'showtimes.id')
            ->join('movies', 'showtimes.movie_id', '=', 'movies.id')
            ->where('bookings.payment_status', 'paid')
            ->groupBy('movies.id', 'movies.title', 'movies.poster_url')
            ->select(
                'movies.id',
                'movies.title',
                'movies.poster_url',
                DB::raw('COUNT(booking_details.id) as tickets'),
                DB::raw('SUM(booking_details.price) as revenue')
            )
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        // Gắn thể loại cho từng phim top
        $movies = Movie::with('genres:id,name')
            ->whereIn('id', $topRaw->pluck('id'))
            ->get()
            ->keyBy('id');

        $topMovies = $topRaw->map(function ($row) use ($movies) {
            $mv = $movies->get($row->id);
            return [
                'id'         => $row->id,
                'title'      => $row->title,
                'poster_url' => $row->poster_url,
                'tickets'    => (int) $row->tickets,
                'revenue'    => (float) $row->revenue,
                'genres'     => $mv ? $mv->genres->pluck('name')->take(2)->implode(', ') : '',
            ];
        });

        return response()->json([
            'total_revenue'   => $totalRevenue,
            'total_tickets'   => $totalTickets,
            'total_combos'    => $totalCombos,
            'total_bookings'  => $totalBookings,
            'movies_count'    => $moviesCount,
            'today_showtimes' => $todayShowtimes,
            'top_movies'      => $topMovies,
        ], 200);
    }

    /**
     * Chuỗi doanh thu theo thời gian cho biểu đồ.
     * ?period=day  -> 7 ngày gần nhất
     * ?period=month -> 6 tháng gần nhất
     */
    public function revenue(Request $request)
    {
        $period = $request->query('period', 'day');

        if ($period === 'month') {
            $start = Carbon::now()->startOfMonth()->subMonths(5);

            $rows = Booking::where('payment_status', 'paid')
                ->where('created_at', '>=', $start)
                ->select(
                    DB::raw("DATE_FORMAT(created_at, '%Y-%m') as bucket"),
                    DB::raw('SUM(total_amount) as revenue')
                )
                ->groupBy('bucket')
                ->pluck('revenue', 'bucket');

            $series = [];
            for ($i = 5; $i >= 0; $i--) {
                $m = Carbon::now()->startOfMonth()->subMonths($i);
                $key = $m->format('Y-m');
                $series[] = [
                    'label'   => 'Th' . $m->month,
                    'key'     => $key,
                    'revenue' => (float) ($rows[$key] ?? 0),
                ];
            }
        } else {
            $period = 'day';
            $start = Carbon::today()->subDays(6);

            $rows = Booking::where('payment_status', 'paid')
                ->where('created_at', '>=', $start)
                ->select(
                    DB::raw('DATE(created_at) as bucket'),
                    DB::raw('SUM(total_amount) as revenue')
                )
                ->groupBy('bucket')
                ->pluck('revenue', 'bucket');

            $series = [];
            for ($i = 6; $i >= 0; $i--) {
                $day = Carbon::today()->subDays($i);
                $key = $day->format('Y-m-d');
                $series[] = [
                    'label'   => $day->format('d/m'),
                    'key'     => $key,
                    'revenue' => (float) ($rows[$key] ?? 0),
                ];
            }
        }

        return response()->json([
            'period' => $period,
            'series' => $series,
            'total'  => array_sum(array_column($series, 'revenue')),
        ], 200);
    }
}
