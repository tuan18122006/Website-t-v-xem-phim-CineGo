<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RefundRequest;
use App\Models\Booking;
use App\Models\ActionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RefundController extends Controller
{
    // Nhân viên gửi yêu cầu hoàn tiền / hủy vé lên hệ thống
    public function requestRefund(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'reason' => 'required|string|max:1000',
        ]);

        $user = Auth::user();

        // Kiểm tra xem vé này đã được thanh toán thành công chưa
        $booking = Booking::findOrFail($request->booking_id);
        if ($booking->payment_status !== 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có thể hoàn tiền cho những hóa đơn đã thanh toán thành công!'
            ], 400);
        }

        // Kiểm tra xem đã có yêu cầu hoàn tiền nào cho hóa đơn này chưa
        $exists = RefundRequest::where('booking_id', $booking->id)->exists();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Hóa đơn này đã có yêu cầu hoàn tiền đang xử lý hoặc đã xử lý rồi!'
            ], 400);
        }

        $refund = RefundRequest::create([
            'booking_id' => $booking->id,
            'requested_by' => $user->id,
            'status' => 'pending',
            'reason' => $request->reason,
        ]);

        // Ghi lại log bảo mật hành động của nhân viên
        ActionLog::create([
            'user_id' => $user->id,
            'action' => 'request_refund',
            'target_type' => 'bookings',
            'target_id' => $booking->id,
            'details' => ['reason' => $request->reason],
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Yêu cầu hoàn tiền đã được gửi và đang chờ Quản lý phê duyệt!',
            'data' => $refund
        ], 201);
    }

    // Nhân viên / Admin gửi yêu cầu hoàn tiền theo ID đơn (route: POST /admin/orders/{id}/refund)
    public function requestRefundById(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $user = Auth::user();

        $booking = Booking::findOrFail($id);
        if ($booking->payment_status !== 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có thể hoàn tiền cho những hóa đơn đã thanh toán thành công!'
            ], 400);
        }

        $exists = RefundRequest::where('booking_id', $booking->id)->exists();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Hóa đơn này đã có yêu cầu hoàn tiền đang xử lý hoặc đã xử lý rồi!'
            ], 400);
        }

        $refund = RefundRequest::create([
            'booking_id' => $booking->id,
            'requested_by' => $user->id,
            'status' => 'pending',
            'reason' => $request->reason,
        ]);

        ActionLog::create([
            'user_id' => $user->id,
            'action' => 'request_refund',
            'target_type' => 'bookings',
            'target_id' => $booking->id,
            'details' => ['reason' => $request->reason],
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Yêu cầu hoàn tiền đã được gửi và đang chờ Quản lý phê duyệt!',
            'data' => $refund
        ], 201);
    }

    // Xem danh sách yêu cầu hoàn tiền đang chờ phê duyệt
    public function pendingRefunds()
    {
        $refunds = RefundRequest::with([
            'booking:id,booking_code,total_amount,payment_method',
            'requester:id,name,email',
        ])
        ->where('status', 'pending')
        ->orderBy('id', 'desc')
        ->get();

        return response()->json([
            'success' => true,
            'data' => $refunds
        ], 200);
    }

    // Quản lý phê duyệt hoặc từ chối hoàn tiền
    public function approveRefund(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $refund = RefundRequest::findOrFail($id);
        $manager = Auth::user();

        if ($refund->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Yêu cầu hoàn tiền này đã được xử lý từ trước!'
            ], 400);
        }

        $booking = Booking::findOrFail($refund->booking_id);

        if ($request->status === 'approved') {
            // Cập nhật trạng thái hóa đơn đặt vé về HỦY và HOÀN TIỀN
            $booking->update([
                'booking_status' => 'cancelled',
                'payment_status' => 'failed', // Coi như thanh toán thất bại/đã hủy để giải phóng ghế
            ]);

            $refund->update([
                'status' => 'approved',
                'approved_by' => $manager->id,
            ]);

            // Ghi nhận log phê duyệt
            ActionLog::create([
                'user_id' => $manager->id,
                'action' => 'approve_refund',
                'target_type' => 'bookings',
                'target_id' => $booking->id,
                'details' => ['status' => 'approved', 'refund_request_id' => $id],
                'ip_address' => $request->ip(),
            ]);
        } else {
            // Từ chối hoàn vé
            $refund->update([
                'status' => 'rejected',
                'approved_by' => $manager->id,
            ]);

            // Ghi nhận log từ chối
            ActionLog::create([
                'user_id' => $manager->id,
                'action' => 'reject_refund',
                'target_type' => 'bookings',
                'target_id' => $booking->id,
                'details' => ['status' => 'rejected', 'refund_request_id' => $id],
                'ip_address' => $request->ip(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $request->status === 'approved' ? 'Đã phê duyệt hoàn vé thành công! Ghế ngồi đã được giải phóng.' : 'Đã từ chối hoàn vé!',
            'data' => $refund
        ], 200);
    }
}
