<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\Captcha;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LoginController extends Controller
{
    /** Halaman login: panel branding di KIRI, form di kanan. */
    public function show(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->intended(route('dashboard'));
        }

        return view('auth.login');
    }

    /**
     * Proses login dengan proteksi berlapis:
     * 1. Rate limit per IP (throttle:login — 5x/menit, 20x/jam)
     * 2. Validasi input ketat
     * 3. CAPTCHA 5 karakter (sekali pakai, timing-safe)
     * 4. Anti user-enumeration: hash bonecng dibandingkan walau user tak ada
     * 5. Session fixation: regenerate ID saat sukses
     */
    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:80'],
            'password' => ['required', 'string', 'max:256'],
            'captcha' => ['required', 'string', 'max:10'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
            'captcha.required' => 'Kode captcha wajib diisi.',
        ]);

        // Verifikasi captcha SEBELUM menyentuh database (hemat resource + anti brute force).
        if (! Captcha::verify($data['captcha'])) {
            return back()->withInput($request->only('username'))
                ->withErrors(['captcha' => 'Kode captcha salah. Silakan coba lagi.']);
        }

        // Pencarian case-insensitive & trim — selalu lewat query builder (prepared statement).
        $username = trim($data['username']);
        $user = \App\Models\User::whereRaw('lower(username) = ?', [mb_strtolower($username)])->first();

        // Hash pembanding supaya waktu respons tetap sama walau user tidak ditemukan.
        $ok = Hash::check(
            $data['password'],
            $user?->password ?? '$2y$12$K0.MbYq6SxXbOO.QBzYJl.axXZm9Yy0e5LhKQxJy0mCeOcYbG0R7i'
        );

        if (! $ok || ! $user || ! $user->is_active) {
            // Delay ekstra kecil untuk menyeragamkan timing.
            usleep(random_int(20000, 60000));

            return back()->withInput($request->only('username'))
                ->withErrors(['username' => 'Username atau kata sandi salah.']);
        }

        Auth::login($user, remember: false);
        $request->session()->regenerate(); // anti session fixation
        $request->session()->put('auth_password_hash', $user->password); // untuk logout di perangkat lain saat reset

        AuditLog::tryCreate(['actor_id' => $user->id, 'action' => 'LOGIN', 'entity' => 'auth', 'record_id' => $user->id]);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        if ($user = $request->user()) {
            AuditLog::tryCreate(['actor_id' => $user->id, 'action' => 'LOGOUT', 'entity' => 'auth', 'record_id' => $user->id]);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
