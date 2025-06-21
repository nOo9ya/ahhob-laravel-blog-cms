<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/';
    public const ADMIN_HOME = '/admin';

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        $this->routes(function () {
            // API 라우트
            Route::middleware('api')
                ->prefix('api/v1')
                ->name('api.')
                ->group(base_path('routes/api.php'));

            // 웹 라우트
            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            // 관리자 라우트
            Route::middleware('web')
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        });
    }
}
