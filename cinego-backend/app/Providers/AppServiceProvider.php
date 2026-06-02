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
    }
}
