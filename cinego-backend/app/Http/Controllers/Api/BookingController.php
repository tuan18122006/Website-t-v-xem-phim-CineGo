<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\BookingCombo;
use App\Models\Showtime;
use App\Models\Seat;
use App\Helpers\BookingHelper;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Services\BookingService;

class BookingController extends Controller
{
    protected $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    public function store(Request $request)
    {
        $request->validate([
            'showtime_id' => 'required|integer|exists:showtimes,id',
            'seat_ids' => 'required|array|min:1',
            'seat_ids.*' => 'required|integer|exists:seats,id',
            'combos' => 'nullable|array',
            'combos.*.id' => 'required|integer|exists:combos,id',
            'combos.*.quantity' => 'required|integer|min:1',
            'voucher_id' => 'nullable|integer|exists:vouchers,id',
            'payment_method' => 'required|string',
            'total_amount' => 'required|numeric'
        ]);

        try {
            $booking = $this->bookingService->createBooking(
                $request->showtime_id,
                $request->seat_ids,
                $request->combos ?? [],
                $request->payment_method,
                auth()->id(),
                $request->voucher_id,
                'paid' // Thanh toán trực tiếp tại quầy hoặc mã QR tĩnh coi như paid ngay cho demo
            );

            // Gửi Email xác nhận đặt vé thành công
            try {
                if ($booking->user && $booking->user->email) {
                    \Illuminate\Support\Facades\Mail::to($booking->user->email)->send(new \App\Mail\BookingSuccessMail($booking));
                }
            } catch (\Exception $mailEx) {
                \Illuminate\Support\Facades\Log::error('Failed to send booking success email: ' . $mailEx->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Đặt vé thành công',
                'booking_code' => $booking->booking_code
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Lấy lịch sử đặt vé của user đang đăng nhập
     */
    public function index(Request $request)
    {
        $userId = auth()->id();

        // Eager load các mối quan hệ
        $bookings = Booking::with([
            'showtime.movie:id,title',
            'showtime.room:id,name',
            'bookingDetails.seat',
            'bookingCombos.combo'
        ])
        ->where('user_id', $userId)
        ->orderBy('id', 'desc')
        ->get();

        $formattedTickets = $bookings->map(function ($booking) {
            $seatsList = $booking->bookingDetails->map(function ($detail) {
                return $detail->seat ? $detail->seat->row . $detail->seat->number : null;
            })->filter()->values()->toArray();

            $combosList = $booking->bookingCombos->map(function ($bc) {
                return $bc->combo ? $bc->combo->name . ' (x' . $bc->quantity . ')' : null;
            })->filter()->values()->toArray();

            $totalTicketPrice = $booking->bookingDetails->sum('price');
            $totalComboPrice = $booking->bookingCombos->sum(function($bc) {
                return $bc->price_at_purchase * $bc->quantity;
            });

            return [
                'id'             => $booking->id,
                'booking_code'   => $booking->booking_code,
                'movie_title'    => $booking->showtime?->movie?->title ?? 'Phim hệ thống',
                'room_name'      => $booking->showtime?->room?->name ?? 'Phòng chiếu CineGo',
                'start_time'     => $booking->showtime?->start_time ? Carbon::parse($booking->showtime->start_time)->format('H:i') : '00:00',
                'date'           => $booking->showtime?->start_time ? Carbon::parse($booking->showtime->start_time)->format('Y-m-d') : Carbon::now()->format('Y-m-d'),
                'seats'          => count($seatsList) > 0 ? $seatsList : ['Không rõ'],
                'combos'         => $combosList,
                'total_ticket_price' => $totalTicketPrice,
                'total_combo_price'  => $totalComboPrice,
                'subtotal'       => $booking->subtotal,
                'discount_amount'=> $booking->discount_amount,
                'total_price'    => $booking->total_amount,
                'payment_method' => $booking->payment_method,
                'created_at'     => $booking->created_at ? $booking->created_at->format('H:i d/m/Y') : '',
                'status'         => $booking->payment_status,
                'status_label'   => $booking->payment_status === 'paid' ? 'Đã thanh toán' : 'Chưa hoàn tất'
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $formattedTickets
        ], 200);
    }
}
