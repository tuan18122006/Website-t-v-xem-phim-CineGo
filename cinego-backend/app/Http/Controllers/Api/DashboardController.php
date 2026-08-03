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
    public function overview(Request $request)
    {
        $query = Booking::where('payment_status', 'paid');
        
        $start = $request->query('start_date');
        $end = $request->query('end_date');
        
        if ($start) $query->whereDate('created_at', '>=', Carbon::parse($start));
        if ($end) $query->whereDate('created_at', '<=', Carbon::parse($end));

        $paidBookingIds = $query->pluck('id');

        $totalRevenue = (float) (clone $query)->sum('total_amount');
        $ticketRevenue = (float) BookingDetail::whereIn('booking_id', $paidBookingIds)->sum('price');
        $comboRevenue = (float) BookingCombo::whereIn('booking_id', $paidBookingIds)->sum(DB::raw('price_at_purchase * quantity'));
        $totalTickets = (int) BookingDetail::whereIn('booking_id', $paidBookingIds)->count();
        $totalCombos  = (int) BookingCombo::whereIn('booking_id', $paidBookingIds)->sum('quantity');
        $totalBookings = $paidBookingIds->count();

        $moviesCount    = (int) Movie::count();
        $stQuery = Showtime::query();
        if ($start) {
            $stQuery->whereDate('start_time', '>=', Carbon::parse($start));
        } else {
            $stQuery->whereDate('start_time', Carbon::today());
        }
        
        if ($end) {
            $stQuery->whereDate('start_time', '<=', Carbon::parse($end));
        } else if (!$start) {
            $stQuery->whereDate('start_time', Carbon::today());
        }

        $todayShowtimes = (int) $stQuery->count();
        $todayShowtimeIds = $stQuery->pluck('id');
        
        $totalSeatsAvailable = DB::table('showtimes')
            ->join('seats', 'showtimes.room_id', '=', 'seats.room_id')
            ->whereIn('showtimes.id', $todayShowtimeIds)
            ->where('seats.status', '!=', 'maintenance')
            ->count();
            
        $totalSeatsSoldToday = DB::table('booking_details')
            ->join('bookings', 'booking_details.booking_id', '=', 'bookings.id')
            ->whereIn('bookings.showtime_id', $todayShowtimeIds)
            ->where('bookings.payment_status', 'paid')
            ->count();
            
        $todayOccupancyRate = $totalSeatsAvailable > 0 
            ? round(($totalSeatsSoldToday / $totalSeatsAvailable) * 100, 1) 
            : 0;

        // === TOP PHIM BÁN CHẠY ===
        // Doanh thu vé theo phim = tổng giá vé (booking_details.price) của đơn đã thanh toán.
        $topRaw = DB::table('booking_details')
            ->join('bookings', 'booking_details.booking_id', '=', 'bookings.id')
            ->join('showtimes', 'bookings.showtime_id', '=', 'showtimes.id')
            ->join('movies', 'showtimes.movie_id', '=', 'movies.id')
            ->where('bookings.payment_status', 'paid');
            
        if ($start) $topRaw->whereDate('bookings.created_at', '>=', Carbon::parse($start));
        if ($end) $topRaw->whereDate('bookings.created_at', '<=', Carbon::parse($end));

        $topRaw = $topRaw->groupBy('movies.id', 'movies.title', 'movies.poster_url')
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
            'total_revenue'      => $totalRevenue,
            'ticket_revenue'     => $ticketRevenue,
            'combo_revenue'      => $comboRevenue,
            'total_tickets'      => $totalTickets,
            'total_combos'       => $totalCombos,
            'total_bookings'     => $totalBookings,
            'movies_count'       => $moviesCount,
            'today_showtimes'    => $todayShowtimes,
            'today_occupancy_rate'=> $todayOccupancyRate,
            'top_movies'         => $topMovies,
        ], 200);
    }

    /**
     * Chuỗi doanh thu theo thời gian cho biểu đồ.
     * ?period=day  -> 7 ngày gần nhất
     * ?period=month -> 6 tháng gần nhất
     */
    public function revenue(Request $request)
    {
        $start_date = $request->query('start_date');
        $end_date = $request->query('end_date');

        if ($start_date && $end_date) {
            $period = 'custom';
            $startDate = Carbon::parse($start_date);
            $endDate = Carbon::parse($end_date);
            
            $rows = Booking::where('payment_status', 'paid')
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->select(
                    DB::raw('DATE(created_at) as bucket'),
                    DB::raw('SUM(total_amount) as revenue')
                )
                ->groupBy('bucket')
                ->pluck('revenue', 'bucket');

            $series = [];
            // Loop from start_date to end_date
            for ($date = clone $startDate; $date->lte($endDate); $date->addDay()) {
                $key = $date->format('Y-m-d');
                $series[] = [
                    'label'   => $date->format('d/m'),
                    'key'     => $key,
                    'revenue' => (float) ($rows[$key] ?? 0),
                ];
            }
        } else {
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
        }

        return response()->json([
            'period' => $period,
            'series' => $series,
            'total'  => array_sum(array_column($series, 'revenue')),
        ], 200);
    }

}
