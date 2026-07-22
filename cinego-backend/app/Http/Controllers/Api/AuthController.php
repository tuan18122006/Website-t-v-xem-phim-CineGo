<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Services\LoyaltyService;

class AuthController extends Controller
{

    protected $loyaltyService;

    public function __construct(LoyaltyService $loyaltyService)
    {
        $this->loyaltyService = $loyaltyService;
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'name.required'      => 'Vui lòng nhập họ tên.',
            'email.required'     => 'Vui lòng nhập email.',
            'email.email'        => 'Email không đúng định dạng.',
            'email.unique'       => 'Email này đã được đăng ký. Vui lòng dùng email khác hoặc đăng nhập.',
            'password.required'  => 'Vui lòng nhập mật khẩu.',
            'password.min'       => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
        ]);

        // 1. Tạo user mới
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'customer',
            'loyalty_points' => 0, // Đảm bảo khởi tạo 0 điểm
            'total_spent' => 0,
            'membership_tier' => 'Bronze',
        ]);

        // 2. Tự động cộng 20 điểm chào mừng
        try {
            $this->loyaltyService->addPoints(
                $user,
                20,
                'Cộng 20 điểm thưởng tạo tài khoản mới CineGo'
            );
        } catch (\Exception $e) {
            \Log::error('Lỗi tặng điểm đăng ký: ' . $e->getMessage());
        }

        // 3. Tạo Token
        $token = $user->createToken('auth_token')->plainTextToken;

        // 4. BẮT BUỘC dùng fresh() để lấy dữ liệu mới nhất (đã cộng 20 điểm) trả về cho Frontend
        $updatedUser = $user->fresh();

        return response()->json([
            'token' => $token,
            'user' => $updatedUser,
            'message' => 'Đăng ký tài khoản thành công! Bạn nhận được 20 điểm CinePoint chào mừng.'
        ], 201);
    }
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required'    => 'Vui lòng nhập email.',
            'email.email'       => 'Email không đúng định dạng.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        $user = User::where('email', $request->email)->first();

        // Sai email hoặc sai mật khẩu -> báo lỗi 422
        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email hoặc mật khẩu không chính xác!'],
            ]);
        }

        // Tài khoản bị khóa thì chặn đăng nhập
        if ($user->status === 'locked') {
            throw ValidationException::withMessages([
                'email' => ['Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên!'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
            'message' => 'Đăng nhập thành công!'
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Đăng xuất tài khoản thành công!'
        ]);
    }

    public function userProfile(Request $request)
    {
        // Lấy thông tin user mới nhất từ Database để đảm bảo có cột avatar_url
        $user = \App\Models\User::findOrFail($request->user()->id);

        // Nếu có avatar, tự động biến đổi thành link HTTP đầy đủ cho Frontend hiển thị
        if ($user->avatar_url) {
            $user->avatar_url = str_starts_with($user->avatar_url, 'http')
                ? $user->avatar_url
                : url($user->avatar_url);
        } else {
            // Nếu chưa up ảnh, trả về ảnh mặc định hệ thống
            $user->avatar_url = url('/storage/avatars/default.png');
        }

        // Trả về đúng cấu trúc gói data giống như UserController
        return response()->json([
            'success' => true,
            'data' => $user
        ], 200);
    }
}
