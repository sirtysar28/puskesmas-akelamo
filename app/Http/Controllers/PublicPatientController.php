<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\Schedule;
use App\Models\User;
use App\Support\Captcha;
use App\Support\MailSender;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman PUBLIK khusus pasien (tanpa login):
 * - melihat jadwal praktik dokter,
 * - registrasi mandiri → nomor rekam medis dibuat otomatis sistem.
 */
class PublicPatientController extends Controller
{
    public function index(): View
    {
        $schedules = Schedule::with('doctor:id,name,specialty')
            ->orderByRaw("CASE day WHEN 'Senin' THEN 1 WHEN 'Selasa' THEN 2 WHEN 'Rabu' THEN 3 WHEN 'Kamis' THEN 4 WHEN 'Jumat' THEN 5 WHEN 'Sabtu' THEN 6 ELSE 7 END")
            ->orderBy('time_start')
            ->get(['id', 'doctor_id', 'day', 'time_start', 'time_end', 'room'])
            ->map(fn ($s) => [
                'doctor' => $s->doctor?->name ?? '-',
                'specialty' => $s->doctor?->specialty ?? '-',
                'day' => $s->day,
                'time' => substr((string) $s->time_start, 0, 5).'–'.substr((string) $s->time_end, 0, 5),
                'room' => $s->room ?: 'Poli',
            ]);

        return view('pasien-publik', [
            'schedules' => $schedules->groupBy('day'),
            'success' => session('rm_baru'),
            'successName' => session('nama_baru'),
            'successUsername' => session('username_baru'),
        ]);
    }

    /** Registrasi pasien baru — RM otomatis + akun login pasien, dilindungi captcha + rate limit. */
    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:160', 'regex:/^[\pL\s.\,\-\' ]+$/u'],
            'nik' => ['nullable', 'string', 'regex:/^\d{16}$/'],
            'gender' => ['required', 'in:Laki-laki,Perempuan'],
            'birth_date' => ['required', 'date', 'before:today', 'after:1900-01-01'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-() ]+$/'],
            'address' => ['nullable', 'string', 'max:500'],
            'password' => ['required', 'string', 'min:8', 'max:72'],
            'password_confirmation' => ['required', 'same:password'],
            'captcha' => ['required', 'string', 'max:8'],
        ], [
            'full_name.regex' => 'Nama hanya boleh berisi huruf.',
            'nik.regex' => 'NIK harus 16 digit angka.',
            'birth_date.before' => 'Tanggal lahir harus di masa lalu.',
            'captcha.required' => 'Kode keamanan wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password_confirmation.same' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        // Verifikasi captcha sekali pakai.
        if (! Captcha::verify($data['captcha'])) {
            return back()->withErrors(['captcha' => 'Kode keamanan salah.'])->withInput($request->except('captcha', 'password', 'password_confirmation'));
        }

        $patient = Patient::create([
            'full_name' => $data['full_name'],
            'nik' => $data['nik'] ?? null,
            'gender' => $data['gender'],
            'birth_date' => $data['birth_date'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'medical_notes' => null,
        ]);

        // Akun login pasien — username mengikuti nomor RM (unik, dibuat sistem).
        $user = User::create([
            'full_name' => $patient->full_name,
            'username' => mb_strtolower($patient->medical_record_no),
            'password' => $data['password'],
            'role' => 'pasien',
            'is_active' => true,
        ]);
        $patient->user_id = $user->id;
        $patient->save();

        AuditLog::tryCreate(['actor_id' => null, 'action' => 'REGISTER_PASIEN', 'entity' => 'patients', 'record_id' => $patient->id]);

        $title = 'Registrasi pasien baru: '.$patient->full_name;
        AppNotification::add('pasien', $title, 'Nomor RM '.$patient->medical_record_no.' — silakan verifikasi data di loket pendaftaran.', '/modul/patients');
        MailSender::notifyRoles(['admin', 'kepala', 'petugas'], $title, 'Pasien mendaftar mandiri melalui website dengan nomor RM '.$patient->medical_record_no.'.', '/modul/patients');

        return redirect('/pasien')->with([
            'rm_baru' => $patient->medical_record_no,
            'nama_baru' => $patient->full_name,
            'username_baru' => $user->username,
        ]);
    }
}
