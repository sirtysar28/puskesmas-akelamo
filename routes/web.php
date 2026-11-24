<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CaptchaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicPatientController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\TerminalController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute Web — SIM Puskesmas Akelamo
|--------------------------------------------------------------------------
| Semua rute mutasi state (POST/PUT/PATCH/DELETE) otomatis dilindungi
| middleware `web` (CSRF token verification + session).
*/

// ---------- Publik ----------
Route::get('/login', [LoginController::class, 'show'])->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login')->name('login.attempt');
Route::get('/captcha', [CaptchaController::class, 'show'])->name('captcha');

Route::get('/survey', [SurveyController::class, 'publicForm'])->name('survey.public');
Route::post('/survey', [SurveyController::class, 'submit'])->middleware('throttle:survey')->name('survey.submit');

// Area pasien PUBLIK (tanpa login): jadwal dokter + registrasi mandiri (RM otomatis)
Route::get('/pasien', [PublicPatientController::class, 'index'])->name('pasien.public');
Route::post('/pasien/daftar', [PublicPatientController::class, 'register'])->middleware('throttle:survey')->name('pasien.register');

// ---------- Staf (wajib login) ----------
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Profil + ganti sandi
    Route::get('/profil', [ProfileController::class, 'show'])->name('profil');
    Route::post('/profil', [ProfileController::class, 'update'])->name('profil.update');
    Route::post('/profil/sandi', [ProfileController::class, 'password'])->middleware('throttle:login')->name('profil.password');

    // Notifikasi (lonceng topbar)
    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifikasi.index');
    Route::get('/notifikasi/data', [NotificationController::class, 'data'])->name('notifikasi.data');
    Route::post('/notifikasi/baca', [NotificationController::class, 'readAll'])->name('notifikasi.read');

    // Resep & Obat — dokter menulis, admin/petugas menyerahkan
    Route::get('/resep', [PrescriptionController::class, 'index'])->name('resep.index');
    Route::post('/resep', [PrescriptionController::class, 'store'])->middleware('throttle:terminal')->name('resep.store');
    Route::post('/resep/{id}/serahkan', [PrescriptionController::class, 'dispense'])->middleware('role:admin,petugas')->whereNumber('id')->name('resep.dispense');

    // CRUD modul data (pasien, obat, keuangan, pegawai, dokter, jadwal)
    Route::get('/modul/{module}', [ModuleController::class, 'index'])->name('modules.index');
    Route::post('/modul/{module}', [ModuleController::class, 'store'])->name('modules.store');
    Route::match(['put', 'patch'], '/modul/{module}/{id}', [ModuleController::class, 'update'])->whereNumber('id')->name('modules.update');
    Route::delete('/modul/{module}/{id}', [ModuleController::class, 'destroy'])->whereNumber('id')->name('modules.destroy');

    // Survei kepuasan
    Route::get('/survei', [SurveyController::class, 'index'])->middleware('role:admin,petugas')->name('surveys.index');
    Route::delete('/survei/{id}', [SurveyController::class, 'destroy'])->middleware('role:admin,petugas')->whereNumber('id')->name('surveys.destroy');
    Route::get('/survei/qr', [SurveyController::class, 'qr'])->middleware('role:admin,petugas')->name('surveys.qr');

    // Pengguna & hak akses (admin)
    Route::prefix('pengguna')->middleware('role:admin')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('users.index');
        Route::post('/', [UserController::class, 'store'])->name('users.store');
        Route::patch('/{id}', [UserController::class, 'toggleActive'])->whereNumber('id')->name('users.toggle');
        Route::post('/{id}/reset-password', [UserController::class, 'resetPassword'])->whereNumber('id')->name('users.reset');
    });

    // Laporan (filter tanggal + export Excel/PDF) — tipe dibatasi per peran
    Route::prefix('laporan')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/data', [ReportController::class, 'data'])->name('reports.data');
        Route::get('/export/excel', [ReportController::class, 'exportExcel'])->middleware('throttle:terminal')->name('reports.excel');
        Route::get('/export/pdf', [ReportController::class, 'exportPdf'])->middleware('throttle:terminal')->name('reports.pdf');
    });

    // Terminal Super Admin (whitelist artisan: migrasi & cache)
    Route::prefix('terminal')->middleware('role:admin')->group(function () {
        Route::get('/', [TerminalController::class, 'index'])->name('terminal.index');
        Route::post('/run', [TerminalController::class, 'run'])->middleware('throttle:terminal')->name('terminal.run');
    });

    // Log aktivitas & pengaturan SMTP (admin)
    Route::get('/log-aktivitas', [AuditLogController::class, 'index'])->middleware('role:admin')->name('log.index');
    Route::prefix('pengaturan')->middleware('role:admin')->group(function () {
        Route::get('/smtp', [SettingController::class, 'smtp'])->name('settings.smtp');
        Route::post('/smtp', [SettingController::class, 'save'])->name('settings.smtp.save');
        Route::post('/smtp/test', [SettingController::class, 'test'])->middleware('throttle:terminal')->name('settings.smtp.test');
    });
});
