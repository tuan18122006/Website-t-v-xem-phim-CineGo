<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Review;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class UserController extends Controller
{
    // Danh sách tài khoản (kèm tìm kiếm theo tên/email/sđt + lọc theo trạng thái, vai trò, hạng thành viên)
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

        // Lọc theo vai trò (admin / staff / customer)
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Lọc theo hạng thành viên (Bronze, Silver, Gold, Diamond)
        if ($request->filled('membership_tier')) {
            $query->where('membership_tier', $request->membership_tier);
        }

        $users = $query->orderBy('id', 'desc')->get()->map(function ($u) {
            if ($u->avatar_url) {
                // Nếu path lưu trong DB chưa có http, tự tạo URL đầy đủ
                $u->avatar_url = str_starts_with($u->avatar_url, 'http') ? $u->avatar_url : url($u->avatar_url);
            } else {
                $u->avatar_url = url('/storage/avatars/default.png');
            }
            return $u;
        });

        return response()->json(['success' => true, 'data' => $users], 200);
    }

    // Xem chi tiết khách hàng: thông tin + thống kê vé/chi tiêu + lịch sử đánh giá + log thiết bị + ví voucher
    public function show($id)
    {
        $user = User::with('bookings')->findOrFail($id);

        if ($user->avatar_url) {
            $user->avatar_url = str_starts_with($user->avatar_url, 'http') ? $user->avatar_url : url($user->avatar_url);
        } else {
            $user->avatar_url = url('/storage/avatars/default.png');
        }

        $bookings = $user->bookings;
        $bookingIds = $bookings->pluck('id');

        $stats = [
            'total_bookings' => $bookings->count(),
            'total_tickets'  => BookingDetail::whereIn('booking_id', $bookingIds)->count(),
            'total_spent'    => $bookings->where('payment_status', 'paid')->sum('total_amount'),
        ];

        // Lịch sử đánh giá / bình luận (kèm tên phim)
        $reviews = Review::with('movie:id,title')
            ->where('user_id', $id)
            ->orderBy('id', 'desc')
            ->get();

        // Lịch sử thiết bị đăng nhập gần đây (10 bản ghi gần nhất)
        $deviceLogs = $user->deviceLogs()->orderBy('last_login_at', 'desc')->take(10)->get();

        // Danh sách vouchers đang có trong ví
        $vouchers = $user->vouchers()->get();

        return response()->json([
            'success' => true,
            'data' => [
                'user'        => $user,
                'stats'       => $stats,
                'reviews'     => $reviews,
                'device_logs' => $deviceLogs,
                'vouchers'    => $vouchers,
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
            'age'      => 'nullable|integer|min:0|max:120',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'phone'    => $request->phone,
            'role'     => $request->role,
            'status'   => $request->status ?? 'active',
            'age'      => $request->age,
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
            'password' => 'nullable|string|min:8',
            'phone'    => 'nullable|string|max:20',
            'role'     => ['required', Rule::in(['admin', 'staff', 'customer'])],
            'status'   => ['required', Rule::in(['active', 'locked'])],
            'age'      => 'nullable|integer|min:0|max:120',
        ]);

        $user->name   = $request->name;
        $user->email  = $request->email;
        $user->phone  = $request->phone;
        $user->role   = $request->role;
        $user->status = $request->status;
        $user->age    = $request->age;

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

    // Khóa / Mở khóa (đảo trạng thái kèm lý do khóa)
    public function toggleStatus(Request $request, $id)
    {
        $user = User::findOrFail($id);
        if ($user->status === 'locked') {
            $user->status = 'active';
            $user->lock_reason = null;
        } else {
            $user->status = 'locked';
            $user->lock_reason = $request->input('lock_reason', 'Bị khóa bởi quản lý');
        }
        $user->save();

        return response()->json([
            'success' => true,
            'message' => $user->status === 'locked'
                ? 'Đã khóa tài khoản!'
                : 'Đã mở khóa tài khoản!',
            'data' => $user,
        ], 200);
    }

    // Phân quyền nhanh: đổi vai trò
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

    // Ẩn danh tính tài khoản khách hàng để bảo mật thông tin (GDPR compliance)
    public function anonymize($id)
    {
        $user = User::findOrFail($id);
        if ($user->role === 'admin') {
            return response()->json(['success' => false, 'message' => 'Không thể ẩn danh tài khoản quản trị viên!'], 400);
        }

        $user->name = 'Customer_Ref_' . $user->id;
        $user->email = 'anonymized_' . $user->id . '@cinego.test';
        $user->phone = null;
        $user->password = Hash::make(Str::random(16));
        $user->is_anonymized = true;
        $user->status = 'locked';
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Đã ẩn danh tính tài khoản và lưu trữ an toàn!'
        ], 200);
    }

    // Thủ công thay đổi hạng thành viên
    public function updateTier(Request $request, $id)
    {
        $request->validate([
            'membership_tier' => ['required', Rule::in(['Bronze', 'Silver', 'Gold', 'Diamond'])],
        ]);

        $user = User::findOrFail($id);
        $user->membership_tier = $request->membership_tier;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật hạng thành viên thành công!',
            'data'    => $user
        ], 200);
    }

    // Tặng voucher cho khách hàng
    public function giftVoucher(Request $request, $id)
    {
        $request->validate([
            'voucher_code' => 'required|string',
        ]);

        $user = User::findOrFail($id);
        $voucher = Voucher::where('code', $request->voucher_code)->first();

        if (!$voucher) {
            return response()->json(['success' => false, 'message' => 'Mã giảm giá không tồn tại!'], 404);
        }

        if ($user->vouchers()->where('voucher_id', $voucher->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Khách hàng đã sở hữu voucher này rồi!'], 400);
        }

        $user->vouchers()->attach($voucher->id);

        return response()->json([
            'success' => true,
            'message' => 'Đã tặng mã giảm giá thành công!',
            'data' => $voucher
        ], 200);
    }

    // Thu hồi voucher
    public function revokeVoucher(Request $request, $id)
    {
        $request->validate([
            'voucher_id' => 'required|integer',
        ]);

        $user = User::findOrFail($id);
        $user->vouchers()->detach($request->voucher_id);

        return response()->json([
            'success' => true,
            'message' => 'Đã thu hồi voucher thành công!'
        ], 200);
    }

    // Xóa tài khoản vĩnh viễn khỏi DB
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['success' => true, 'message' => 'Đã xóa tài khoản!'], 200);
    }

    // Cập nhật thông tin profile cá nhân
    public function updateProfile(Request $request)
    {
        $user = clone $request->user();

        $request->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $user->name = $request->name;
        $user->phone = $request->phone;

        if ($request->has('birthday')) {
            $user->birthday = $request->birthday;
        }

        $user->save();

        if ($user->avatar_url) {
            $user->avatar_url = str_starts_with($user->avatar_url, 'http') ? $user->avatar_url : url($user->avatar_url);
        } else {
            $user->avatar_url = url('/storage/avatars/default.png');
        }

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thông tin tài khoản thành công!',
            'data'    => $user
        ], 200);
    }

    // Thay đổi mật khẩu (có chặn trùng mật khẩu cũ)
    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:8',
        ]);

        $user = clone $request->user();

        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mật khẩu hiện tại nhập không chính xác!'
            ], 422);
        }

        if (Hash::check($request->new_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mật khẩu mới không được trùng với mật khẩu hiện tại!'
            ], 422);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Thay đổi mật khẩu tài khoản thành công!'
        ], 200);
    }

    // Tải ảnh đại diện
    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = clone $request->user();

        if ($request->hasFile('avatar')) {
            if ($user->avatar_url) {
                $fileName = basename($user->avatar_url);
                \Illuminate\Support\Facades\Storage::disk('public')->delete('avatars/' . $fileName);
            }

            $path = $request->file('avatar')->store('avatars', 'public');

            $user->avatar_url = '/storage/' . $path;
            $user->save();

            return response()->json([
                'success' => true,
                'avatar_url' => url($user->avatar_url)
            ], 200);
        }

        return response()->json(['success' => false, 'message' => 'Không tìm thấy file ảnh tải lên!'], 400);
    }
}
