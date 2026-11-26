<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Schedule;
use Illuminate\View\View;

/**
 * Halaman depan (landing) PUBLIK — profil puskesmas, layanan,
 * jadwal dokter, & pintu masuk registrasi/login pasien online.
 */
class HomeController extends Controller
{
    public function index(): View
    {
        $schedules = Schedule::with('doctor:id,name,specialty')
            ->orderByRaw("CASE day WHEN 'Senin' THEN 1 WHEN 'Selasa' THEN 2 WHEN 'Rabu' THEN 3 WHEN 'Kamis' THEN 4 WHEN 'Jumat' THEN 5 WHEN 'Sabtu' THEN 6 ELSE 7 END")
            ->orderBy('time_start')
            ->limit(24)
            ->get(['id', 'doctor_id', 'day', 'time_start', 'time_end', 'room'])
            ->map(fn ($s) => [
                'doctor' => $s->doctor?->name ?? '-',
                'specialty' => $s->doctor?->specialty ?? '-',
                'day' => $s->day,
                'time' => substr((string) $s->time_start, 0, 5).'–'.substr((string) $s->time_end, 0, 5),
                'room' => $s->room ?: 'Poli',
            ]);

        return view('home', [
            'doctorCount' => Doctor::distinct('name')->count('name'),
            'scheduleCount' => Schedule::count(),
            'today' => now()->locale('id')->translatedFormat('l'),
            'schedules' => $schedules->groupBy('day'),
        ]);
    }
}
