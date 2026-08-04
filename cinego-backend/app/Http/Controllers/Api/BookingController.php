<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\BookingCombo;
use App\Models\Showtime;
use App\Models\Seat;
use App\Models\Review;
use App\Helpers\BookingHelper;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Services\BookingService;
use App\Services\LoyaltyService;

class BookingController extends Controller
{
    protected $bookingService;
    protected $loyaltyService;
    public function __construct(BookingService $bookingService, LoyaltyService $loyaltyService)
    {
        $this->bookingService = $bookingService;
        $this->loyaltyService = $loyaltyService;
    }

    





    public function store(Request $request)
    {
        $request->validate([
            'showtime_id'           => 'required|integer|exists:showtimes,id',
            'seat_ids'              => 'required|array|min:1',
            'seat_ids.*'            => 'required|integer|exists:seats,id',
            'combos'                => 'nullable|array',
            'combos.*.id'          => 'required|integer|exists:combos,id',
            'combos.*.quantity'    => 'required|integer|min:1',
            'used_user_combo_ids'   => 'nullable|array',
            'used_user_combo_ids.*' => 'integer',
            'voucher_id'            => 'nullable|integer|exists:vouchers,id',
            'payment_method'        => 'required|string',
            'total_amount'          => 'required|numeric'
        ]);

        try {
            $booking = $this->bookingService->createBooking(
                $request->showtime_id,
                $request->seat_ids,
                $request->combos ?? [],
                $request->payment_method,
                auth()->id(),
                $request->voucher_id,
                'paid',
                null,
                $request->used_user_combo_ids ?? []
            );

            // 1. Trừ combo quà tặng
            if ($request->has('used_user_combo_ids') && count($request->used_user_combo_ids) > 0) {
                DB::table('user_combos')
                    ->whereIn('id', $request->used_user_combo_ids)
                    ->where('user_id', auth()->id())
                    ->update([
                        'is_used'    => true,
                        'booking_id' => $booking->id,
                        'used_at'    => now(),
                        'updated_at' => now()
                    ]);
            }

            // 2. Trừ voucher
            if ($request->voucher_id) {
                DB::table('user_vouchers')
                    ->where('voucher_id', $request->voucher_id)
                    ->where('user_id', auth()->id())
                    ->where('is_used', false)
                    ->limit(1)
                    ->update([
                        'is_used'    => true,
                        'booking_id' => $booking->id,
                        'used_at'    => now(),
                        'updated_at' => now()
                        
                    ]);
            }

        
            if ($booking->user) {
                $this->loyaltyService->processBookingPoints(
                    $booking->user,
                    $booking->total_amount,
                    $booking
                );
            }

            // 3. Gửi email
            try {
                if ($booking->user && $booking->user->email) {
                    \Illuminate\Support\Facades\Mail::to($booking->user->email)
                        ->send(new \App\Mail\BookingSuccessMail($booking));
                }
            } catch (\Exception $mailEx) {
                \Illuminate\Support\Facades\Log::error('Failed to send booking success email: ' . $mailEx->getMessage());
            }

            return response()->json([
                'success'      => true,
                'message'      => 'Đặt vé thành công',
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
    



public function history(Request $request)
    {
        $userId = auth()->id();

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

            $seatsList = [];
            if ($booking->bookingDetails) {
                foreach ($booking->bookingDetails as $detail) {
                    if ($detail && $detail->seat) {
                        $seatsList[] = [
                            'row'    => $detail->seat->row,
                            'number' => $detail->seat->number,
                            'type'   => $detail->seat->type ?? 'standard' 
                        ];
                    }
                }
            }

            $combosList = [];
            if ($booking->bookingCombos) {
                $combosList = $booking->bookingCombos->map(function ($bc) {
                    return $bc->combo ? $bc->combo->name . ' (x' . $bc->quantity . ')' : null;
                })->filter()->values()->toArray();
            }

            $totalTicketPrice = $booking->bookingDetails->sum('price');
            $totalComboPrice = $booking->bookingCombos->sum(function ($bc) {
                return $bc->price_at_purchase * $bc->quantity;
            });
            $totalTicketPrice = $booking->bookingDetails ? $booking->bookingDetails->sum('price') : 0;
            $totalComboPrice = $booking->bookingCombos ? $booking->bookingCombos->sum(function ($bc) {
                return ($bc->price_at_purchase ?? 0) * ($bc->quantity ?? 0);
            }) : 0;

            return [
                'id'             => $booking->id,
                'booking_code'   => $booking->booking_code,
                'movie_title'    => $booking->showtime?->movie?->title ?? 'Phim hệ thống',
                'room_name'      => $booking->showtime?->room?->name ?? 'Phòng chiếu CineGo',
                'start_time'     => $booking->showtime?->start_time ? Carbon::parse($booking->showtime->start_time)->format('H:i') : '00:00',
                'date'           => $booking->showtime?->start_time ? Carbon::parse($booking->showtime->start_time)->format('Y-m-d') : Carbon::now()->format('Y-m-d'),
                'seats'          => $seatsList,
                'combos'         => $combosList,
                'total_ticket_price' => $totalTicketPrice,
                'total_combo_price'  => $totalComboPrice,
                'subtotal'       => $booking->subtotal,
                'discount_amount' => $booking->discount_amount,
                'total_price'    => $booking->total_amount,
                'payment_method' => $booking->payment_method,
                'created_at'     => $booking->created_at ? $booking->created_at->format('H:i d/m/Y') : '',
                'status'         => $booking->payment_status,
                'status_label'   => match($booking->payment_status) {
                    'paid'                 => 'Đã thanh toán',
                    'waiting_confirmation' => 'Đang chờ xác nhận',
                    'cancelled'            => 'Đã hủy',
                    default                => 'Chưa hoàn tất',
                }
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $formattedTickets
        ], 200);
    }

    /**
     * Admin: Lấy danh sách toàn bộ đơn hàng
     */
    public function index(Request $request)
    {
        $query = Booking::with([
            'user:id,name,email,phone',
            'showtime.movie:id,title',
            'showtime.room:id,name'
        ])->orderBy('id', 'desc');

        // Lọc theo trạng thái
        if ($request->has('status') && $request->status !== '') {
            $query->where('payment_status', $request->status);
        }

        // Tìm kiếm theo mã đơn hoặc số điện thoại
        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('booking_code', 'like', '%' . $search . '%')
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('phone', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%');
                  });
            });
        }

        $bookings = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $bookings
        ]);
    }

    /**
     * Admin: Cập nhật trạng thái đơn hàng (để duyệt đơn VietQR)
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:pending,waiting_confirmation,paid,cancelled'
        ]);

        $booking = Booking::with('user')->findOrFail($id);

        $oldStatus = $booking->payment_status;
        $newStatus = $request->status;

        $booking->payment_status = $newStatus;
        $booking->save();

        // Nếu trạng thái từ pending -> paid, cộng điểm loyalty và gửi mail
        if (($oldStatus === 'pending' || $oldStatus === 'waiting_confirmation') && $newStatus === 'paid') {
            if ($booking->user) {
                $booking->user->notify(new \App\Notifications\BookingConfirmedNotification(
                    $booking->booking_code,
                    "Đơn hàng " . $booking->booking_code . " đã được thanh toán thành công. Chúc bạn xem phim vui vẻ!"
                ));

                $this->loyaltyService->processBookingPoints(
                    $booking->user,
                    $booking->total_amount,
                    $booking
                );

                try {
                    if ($booking->user->email) {
                        \Illuminate\Support\Facades\Mail::to($booking->user->email)
                            ->send(new \App\Mail\BookingSuccessMail($booking));
                    }
                } catch (\Exception $mailEx) {
                    \Illuminate\Support\Facades\Log::error('Failed to send booking success email on manual approval: ' . $mailEx->getMessage());
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái thành công',
            'data'    => $booking
        ]);
    }

    /**
     * User: Lấy chi tiết 1 đơn hàng (để hiển thị mã QR)
     */
    public function show($id)
    {
        $booking = Booking::with([
            'showtime.movie:id,title',
            'showtime.room:id,name',
            'bookingDetails.seat:id,row,number',
            'bookingCombos.combo:id,name'
        ])->where('user_id', auth()->id())
          ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $booking
        ]);
    }

    /**
     * User: Xác nhận đã chuyển khoản
     */
    public function confirmTransfer(Request $request, $id)
    {
        $booking = Booking::where('user_id', auth()->id())->findOrFail($id);

        if ($booking->payment_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Trạng thái đơn hàng không hợp lệ.'
            ], 400);
        }

        $booking->payment_status = 'waiting_confirmation';
        $booking->save();

        $admins = \App\Models\User::where('role', 'admin')->get();
        \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\QrPaymentPendingNotification(
            $booking->booking_code,
            "Khách hàng " . (auth()->user()->name ?? 'Ẩn danh') . " vừa báo cáo đã chuyển khoản cho mã vé " . $booking->booking_code . ". Cần bạn kiểm duyệt!"
        ));

        return response()->json([
            'success' => true,
            'message' => 'Đã báo cáo chuyển khoản thành công',
            'data'    => $booking
        ]);
    }
}
