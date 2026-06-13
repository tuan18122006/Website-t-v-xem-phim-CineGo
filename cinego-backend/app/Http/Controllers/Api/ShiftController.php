<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShiftLog;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShiftController extends Controller
{
    // Bắt đầu ca trực (Check-in)
    public function checkIn(Request $request)
    {
        $request->validate([
            'shift_name' => 'required|string|max:50',
            'workstation' => 'required|string|max:50',
        ]);

        $user = Auth::user();

        // Kiểm tra xem nhân viên đã có ca trực nào đang mở chưa
        $activeShift = ShiftLog::where('user_id', $user->id)->where('status', 'open')->first();
        if ($activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn đã có một ca trực đang mở, vui lòng chốt ca cũ trước!',
                'data' => $activeShift
            ], 400);
        }

        $shift = ShiftLog::create([
            'user_id' => $user->id,
            'shift_name' => $request->shift_name,
            'workstation' => $request->workstation,
            'checkin_time' => now(),
            'status' => 'open',
        ]);

        // Cập nhật trạng thái làm việc của nhân viên
        $user->update(['work_status' => 'on_shift']);

        return response()->json([
            'success' => true,
            'message' => 'Check-in ca trực thành công!',
            'data' => $shift
        ], 201);
    }

    // Kết thúc ca trực (Check-out) - Gửi số liệu đối soát doanh thu
    public function checkOut(Request $request)
    {
        $request->validate([
            'reported_cash' => 'required|numeric|min:0',
            'reported_transfer' => 'required|numeric|min:0',
        ]);

        $user = Auth::user();

        // Lấy ca trực đang mở của nhân viên
        $shift = ShiftLog::where('user_id', $user->id)->where('status', 'open')->first();
        if (!$shift) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy ca trực nào đang mở của bạn!'
            ], 404);
        }

        // Tính toán doanh thu của hệ thống ghi nhận từ khi nhân viên check-in đến hiện tại
        // Đếm doanh số từ tất cả hóa đơn đã thanh toán thành công
        $systemRevenue = Booking::where('created_at', '>=', $shift->checkin_time)
            ->where('payment_status', 'paid')
            ->sum('total_amount');

        $shift->update([
            'checkout_time' => now(),
            'reported_cash' => $request->reported_cash,
            'reported_transfer' => $request->reported_transfer,
            'system_revenue' => $systemRevenue,
            'status' => 'pending_audit', // Chờ quản lý đối soát và đóng ca
        ]);

        // Cập nhật trạng thái làm việc
        $user->update(['work_status' => 'off_shift']);

        return response()->json([
            'success' => true,
            'message' => 'Gửi yêu cầu chốt ca trực thành công! Vui lòng đợi Quản lý đối soát.',
            'data' => $shift
        ], 200);
    }

    // Lấy thông tin ca trực hiện tại
    public function activeShift()
    {
        $user = Auth::user();
        $shift = ShiftLog::where('user_id', $user->id)->where('status', 'open')->first();

        return response()->json([
            'success' => true,
            'data' => $shift
        ], 200);
    }

    // Xem danh sách các ca trực đang chờ Quản lý đối soát & duyệt
    public function pendingAudits()
    {
        $shifts = ShiftLog::with('user:id,name,email')
            ->where('status', 'pending_audit')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $shifts
        ], 200);
    }

    // Quản lý phê duyệt đối soát chốt ca trực
    public function audit(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:closed,rejected',
            'audit_note' => 'nullable|string',
        ]);

        $shift = ShiftLog::findOrFail($id);
        $manager = Auth::user();

        $shift->update([
            'status' => $request->status === 'closed' ? 'closed' : 'open', // Trả về open nếu bị từ chối chốt
            'audited_by' => $manager->id,
            'audit_note' => $request->audit_note,
        ]);

        return response()->json([
            'success' => true,
            'message' => $request->status === 'closed' ? 'Đã duyệt chốt đối soát ca trực!' : 'Đã trả lại ca trực để nhân viên khai báo lại!',
            'data' => $shift
        ], 200);
    }
}
