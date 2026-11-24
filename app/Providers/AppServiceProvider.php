<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use App\Support\ModuleRegistry;

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
        // ---------- Hardening autentikasi ----------

        // bcrypt cost 12 (nilai aman untuk server modern; naikkan bila hardware memungkinkan).
        config(['hashing.bcrypt.rounds' => 12]);

        // Kebijakan kata sandi global: minimal 12 karakter.
        Password::defaults(fn () => Password::min(12));

        // ---------- Rate limiting ----------

        // Login: 5 percobaan/menit + 20/jam per alamat IP (anti brute-force & credential stuffing).
        RateLimiter::for('login', function (Request $request) {
            return [
                Limit::perMinute(5)->by('login-ip:'.$request->ip()),
                Limit::perHour(20)->by('login-ip:'.$request->ip()),
            ];
        });

        // Submit survei publik: 8 kali per jam per IP (anti spam).
        RateLimiter::for('survey', function (Request $request) {
            return Limit::perHour(8)->by('survey-ip:'.$request->ip());
        });

        // Terminal super admin: 12 eksekusi/menit per akun (anti penyalahgunaan).
        RateLimiter::for('terminal', function (Request $request) {
            return Limit::perMinute(12)->by('terminal-user:'.($request->user()?->id ?: $request->ip()));
        });

        // ---------- View composer ----------

        // Menu sidebar selalu sesuai peran pengguna yang sedang login.
        View::composer('layouts.app', function ($view) {
            $user = auth()->user();
            $view->with('menu', $user ? ModuleRegistry::menuFor($user->role) : []);
        });
    }
}
