<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // Danh sách tài khoản (kèm tìm kiếm theo tên/email/sđt + lọc theo trạng thái)
    public function index(Request $request)
    {
        $query = User::query();

        // Tìm kiếm theo Tên / Email / Số điện thoại
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Lọc theo trạng thái hoạt động (active / locked)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Lọc theo vai trò (tùy chọn, tiện cho admin)
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->orderBy('id', 'desc')->get();
        return response()->json(['success' => true, 'data' => $users], 200);
    }

    // Xem chi tiết khách hàng: thông tin + thống kê vé/chi tiêu + lịch sử đánh giá
    public function show($id)
    {
        $user = User::findOrFail($id);

        $bookings = Booking::where('user_id', $id)->get();
        $bookingIds = $bookings->pluck('id');

        $stats = [
            'total_bookings' => $bookings->count(),
            // Số vé = tổng số ghế đã đặt (mỗi booking_detail là 1 ghế = 1 vé)
            'total_tickets'  => BookingDetail::whereIn('booking_id', $bookingIds)->count(),
            // Tổng chi tiêu chỉ tính các đơn đã thanh toán thành công
            'total_spent'    => $bookings->where('payment_status', 'paid')->sum('total_amount'),
        ];

        // Lịch sử đánh giá / bình luận (kèm tên phim)
        $reviews = Review::with('movie:id,title')
            ->where('user_id', $id)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'user'    => $user,
                'stats'   => $stats,
                'reviews' => $reviews,
            ],
        ], 200);
    }

    // Thêm tài khoản mới
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'phone'    => 'nullable|string|max:20',
            'role'     => ['required', Rule::in(['admin', 'staff', 'customer'])],
            'status'   => ['nullable', Rule::in(['active', 'locked'])],
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'phone'    => $request->phone,
            'role'     => $request->role,
            'status'   => $request->status ?? 'active', // mặc định hoạt động
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo tài khoản thành công!',
            'data'    => $user,
        ], 201);
    }

    // Cập nhật thông tin tài khoản
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'password' => 'nullable|string|min:8', // để trống = không đổi mật khẩu
            'phone'    => 'nullable|string|max:20',
            'role'     => ['required', Rule::in(['admin', 'staff', 'customer'])],
            'status'   => ['required', Rule::in(['active', 'locked'])],
        ]);

        $user->name   = $request->name;
        $user->email  = $request->email;
        $user->phone  = $request->phone;
        $user->role   = $request->role;
        $user->status = $request->status;

        // Chỉ đổi mật khẩu khi người dùng nhập mới
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật tài khoản thành công!',
            'data'    => $user,
        ], 200);
    }

    // Khóa / Mở khóa nhanh (đảo trạng thái)
    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        $user->status = $user->status === 'locked' ? 'active' : 'locked';
        $user->save();

        return response()->json([
            'success' => true,
            'message' => $user->status === 'locked'
                ? 'Đã khóa tài khoản!'
                : 'Đã mở khóa tài khoản!',
            'data' => $user,
        ], 200);
    }

    // Phân quyền nhanh: đổi vai trò (vd: Nâng cấp customer -> staff)
    public function updateRole(Request $request, $id)
    {
        $request->validate([
            'role' => ['required', Rule::in(['admin', 'staff', 'customer'])],
        ]);

        $user = User::findOrFail($id);
        $user->role = $request->role;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật vai trò thành công!',
            'data'    => $user,
        ], 200);
    }

    // Xóa tài khoản (tùy chọn)
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['success' => true, 'message' => 'Đã xóa tài khoản!'], 200);
    }
}
