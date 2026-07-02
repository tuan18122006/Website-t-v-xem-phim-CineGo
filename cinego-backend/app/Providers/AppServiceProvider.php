<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Định nghĩa Gate phân quyền 'admin-only' dựa trên cột 'role' trong database
        Gate::define('admin-only', function (User $user) {
            return $user->role === 'admin';
        });

        // Nhân viên hỗ trợ HOẶC admin: dùng cho các công cụ vận hành như tra cứu đơn hàng
        Gate::define('staff-or-admin', function (User $user) {
            return in_array($user->role, ['staff', 'admin'], true);
        });
    }
}
