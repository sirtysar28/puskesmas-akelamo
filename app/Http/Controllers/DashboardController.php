<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Employee;
use App\Models\Finance;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Schedule;
use App\Models\Survey;
use App\Models\User;
use App\Support\ModuleRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** Dashboard dengan ringkasan + grafik animasi sesuai hak akses peran. */
    public function index(Request $request): View|RedirectResponse
    {
        $role = $request->user()->role;

        // Pasien tidak memakai dashboard staf — dialihkan ke portal pasien.
        if ($role === 'pasien') {
            return redirect()->route('pasien.home');
        }

        $data = ['role' => $role];

        if (in_array($role, ['admin', 'kepala', 'petugas', 'perawat', 'dokter'], true)) {
            $data['patients'] = Patient::count();
            $data['doctors'] = Doctor::count();
            $data['schedules'] = Schedule::with('doctor:id,name')
                ->latest('id')->limit(5)->get()
                ->map(fn ($s) => [
                    'doctor' => $s->doctor?->name,
                    'day' => $s->day,
                    'time_start' => substr((string) $s->time_start, 0, 5),
                    'time_end' => substr((string) $s->time_end, 0, 5),
                    'room' => $s->room ?: 'Poli',
                ]);
        }

        if (in_array($role, ['admin', 'kepala', 'petugas', 'perawat'], true)) {
            $data['medicines'] = [
                'total' => Medicine::count(),
                'low_stock' => Medicine::whereColumn('stock', '<=', 'min_stock')->count(),
            ];
        }

        if (in_array($role, ['admin', 'kepala', 'keuangan'], true)) {
            $data['balance'] = (float) Finance::selectRaw(
                "COALESCE(SUM(CASE WHEN type = 'Pemasukan' THEN amount ELSE -amount END), 0) AS balance"
            )->value('balance');
        }

        if (in_array($role, User::FULL_ACCESS, true)) {
            $data['employees'] = Employee::count();
        }

        if (in_array($role, ['admin', 'kepala', 'petugas', 'perawat'], true)) {
            $data['appointments'] = [
                'today' => Appointment::whereDate('visit_date', now()->toDateString())->count(),
                'waiting' => Appointment::where('status', 'Menunggu')->count(),
            ];
        }

        $data['survey'] = [
            'total' => Survey::count(),
            'average' => (float) Survey::avg('rating'),
        ];

        return view('dashboard', [
            'tiles' => $this->tiles($role, $data),
            'schedules' => $data['schedules'] ?? [],
            'quickLinks' => collect(ModuleRegistry::menuFor($role))->except('dashboard'),
            'barChart' => $this->barChart($role),
            'venn' => $this->venn($role),
        ]);
    }

    /** Kartu statistik — dapat diklik bila peran boleh membuka modulnya. */
    private function tiles(string $role, array $data): array
    {
        $icons = ['pawn', 'med', 'emp', 'doc', 'fin', 'star'];
        $tiles = [];

        $push = function (int $i, string $title, float $value, string $sub, string $format = 'number', ?string $module = null) use (&$tiles, $role, $icons) {
            // Kartu hanya bisa diklik bila peran punya akses baca modul tujuan.
            $link = null;
            if ($module !== null) {
                $def = ModuleRegistry::find($module);
                if ($module === 'surveys') {
                    $link = in_array($role, ['admin', 'kepala', 'petugas', 'perawat'], true) ? route('surveys.index') : null;
                } elseif ($def !== null && in_array($role, $def['readRoles'], true)) {
                    $link = url('/modul/'.$module);
                } elseif ($module === 'finances') {
                    $link = in_array($role, ['admin', 'kepala', 'keuangan'], true) ? url('/modul/finances') : null;
                }
            }
            $tiles[] = [
                'icon' => ['♙', '✚', '♧', '♜', '▥', '★'][$i] ?? '◈',
                'key' => $icons[$i] ?? 'misc',
                'title' => $title, 'value' => $value, 'sub' => $sub,
                'format' => $format, 'link' => $link,
            ];
        };

        $i = 0;
        if (isset($data['patients'])) {
            $push($i++, 'Total Pasien', $data['patients'], 'Rekam medis terdaftar', 'number', 'patients');
        }
        if (isset($data['medicines'])) {
            $push($i++, 'Jenis Obat', $data['medicines']['total'], $data['medicines']['low_stock'].' stok perlu perhatian', 'number', 'medicines');
        }
        if (isset($data['employees'])) {
            $push($i++, 'Total Pegawai', $data['employees'], 'SDM puskesmas', 'number', 'employees');
        }
        if (isset($data['doctors'])) {
            $push($i++, 'Dokter', $data['doctors'], 'Tenaga medis terdaftar', 'number', 'doctors');
        }
        if (isset($data['balance'])) {
            $push($i++, 'Saldo Keuangan', $data['balance'], 'Akumulasi transaksi', 'currency', 'finances');
        }
        if (isset($data['appointments'])) {
            $push($i++, 'Berobat Hari Ini', $data['appointments']['today'], $data['appointments']['waiting'].' menunggu konfirmasi', 'number', 'appointments');
        }
        if (isset($data['survey'])) {
            $avg = round($data['survey']['average'], 2);
            $push($i++, 'Kepuasan Masyarakat', $avg, $data['survey']['total'].' penilaian masuk', 'rating', 'surveys');
        }

        return $tiles;
    }

    /** Data bar chart per bulan (6 bulan terakhir) sesuai peran. */
    private function barChart(string $role): array
    {
        $out = [];
        $expr = fn (string $col) => $this->monthExpr($col);

        if (in_array($role, ['admin', 'kepala', 'petugas', 'perawat', 'dokter'], true)) {
            $rows = Patient::where('created_at', '>=', now()->subMonths(6)->startOfMonth())
                ->selectRaw("{$expr('created_at')} AS ym, COUNT(*) AS total")
                ->groupBy('ym')->orderBy('ym')->get()->keyBy('ym');

            $out['patients'] = $this->fillMonths($rows, fn ($r) => (int) $r->total, 'Pasien Baru');
        }

        if (in_array($role, ['admin', 'kepala', 'keuangan'], true)) {
            $in = Finance::where('type', 'Pemasukan')->where('transaction_date', '>=', now()->subMonths(6)->startOfMonth()->toDateString())
                ->selectRaw("{$expr('transaction_date')} AS ym, SUM(amount) AS total")
                ->groupBy('ym')->orderBy('ym')->get()->keyBy('ym');
            $outQ = Finance::where('type', 'Pengeluaran')->where('transaction_date', '>=', now()->subMonths(6)->startOfMonth()->toDateString())
                ->selectRaw("{$expr('transaction_date')} AS ym, SUM(amount) AS total")
                ->groupBy('ym')->orderBy('ym')->get()->keyBy('ym');

            $labels = [];
            $inData = [];
            $outData = [];
            for ($i = 5; $i >= 0; $i--) {
                $m = now()->subMonths($i)->startOfMonth();
                $ym = $m->format('Y-m');
                $labels[] = $m->locale('id')->translatedFormat('M');
                $inData[] = (float) ($in[$ym]->total ?? 0);
                $outData[] = (float) ($outQ[$ym]->total ?? 0);
            }
            $out['finance'] = [
                'labels' => $labels, 'in' => $inData, 'out' => $outData,
                'inLabel' => 'Pemasukan', 'outLabel' => 'Pengeluaran', 'title' => 'Arus Kas 6 Bulan Terakhir',
            ];
        }

        return $out;
    }

    /** Ekspresi kolom bulan (YYYY-MM) portabel antar driver. */
    private function monthExpr(string $column): string
    {
        $driver = Patient::query()->getConnection()->getDriverName();

        return match ($driver) {
            'mysql' => "DATE_FORMAT({$column}, '%Y-%m')",
            'pgsql' => "TO_CHAR({$column}, 'YYYY-MM')",
            default => "STRFTIME('%Y-%m', {$column})", // sqlite
        };
    }

    private function fillMonths($rows, callable $val, string $title): array
    {
        $labels = [];
        $values = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i)->startOfMonth();
            $ym = $m->format('Y-m');
            $labels[] = $m->locale('id')->translatedFormat('M');
            $values[] = isset($rows[$ym]) ? $val($rows[$ym]) : 0;
        }

        return ['labels' => $labels, 'values' => $values, 'title' => $title.' per Bulan'];
    }

    /** Data venn diagram (2 himpunan beririsan) sesuai peran. */
    private function venn(string $role): array
    {
        if (in_array($role, ['admin', 'kepala', 'petugas', 'perawat'], true)) {
            $total = Medicine::count();
            $a = Medicine::whereColumn('stock', '>', 'min_stock')->count(); // stok aman
            $b = Medicine::where(fn ($q) => $q->whereNull('expiry_date')->orWhere('expiry_date', '>', now()))->count(); // belum kedaluwarsa
            $both = Medicine::whereColumn('stock', '>', 'min_stock')
                ->where(fn ($q) => $q->whereNull('expiry_date')->orWhere('expiry_date', '>', now()))->count();

            return [
                'title' => 'Kelayakan Stok Obat',
                'aLabel' => 'Stok Aman', 'bLabel' => 'Belum Kedaluwarsa',
                'a' => $a, 'b' => $b, 'both' => $both, 'total' => $total,
                'caption' => $both.' dari '.$total.' jenis obat ideal (stok aman & belum kedaluwarsa).',
            ];
        }

        if (in_array($role, ['admin', 'kepala', 'keuangan'], true) && $role === 'keuangan') {
            return $this->vennSurvei();
        }

        if ($role === 'dokter') {
            $total = Patient::count();
            $a = Patient::whereNotNull('nik')->count();
            $b = Patient::whereNotNull('phone')->count();
            $both = Patient::whereNotNull('nik')->whereNotNull('phone')->count();

            return [
                'title' => 'Kelengkapan Data Pasien',
                'aLabel' => 'NIK Terdata', 'bLabel' => 'Kontak HP Terdata',
                'a' => $a, 'b' => $b, 'both' => $both, 'total' => $total,
                'caption' => $both.' dari '.$total.' pasien memiliki NIK & kontak lengkap.',
            ];
        }

        return $this->vennSurvei(); // admin (fallback) & lainnya
    }

    private function vennSurvei(): array
    {
        $total = Survey::count();
        $a = Survey::where('rating', '>=', 4)->count(); // puas
        $b = Survey::whereNotNull('suggestion')->count(); // memberi saran
        $both = Survey::where('rating', '>=', 4)->whereNotNull('suggestion')->count();

        return [
            'title' => 'Kepuasan & Partisipasi Survei',
            'aLabel' => 'Puas (4–5★)', 'bLabel' => 'Memberi Saran',
            'a' => $a, 'b' => $b, 'both' => $both, 'total' => $total,
            'caption' => $a.' responden puas; '.$both.' di antaranya juga menyampaikan saran tertulis.',
        ];
    }
}
