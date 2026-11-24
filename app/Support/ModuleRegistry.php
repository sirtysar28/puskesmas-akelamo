<?php

namespace App\Support;

use App\Models\Doctor;
use App\Models\Employee;
use App\Models\Finance;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Schedule;
use App\Models\User;

/**
 * Registry modul CRUD — SEMUA nama tabel, kolom, dan aturan validasi
 * didefinisikan eksplisit di sini (whitelist). Input pengguna tidak
 * pernah menyentuh nama tabel/kolom sehingga aman dari SQL injection;
 * seluruh akses data lewat Eloquent/Query Builder (prepared statements).
 */
final class ModuleRegistry
{
    public const DAYS = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

    /**
     * @return array<string, array{
     *   model: class-string,
     *   title: string, icon: string,
     *   roles: list<string>,
     *   readRoles: list<string>,
     *   columns: list<array{0:string,1:string}>,
     *   fields: list<array{0:string,1:string,2:string,3:?bool,4?:list<string>}>,
     *   rules: array<string,string|array>
     * }>
     */
    public static function all(): array
    {
        return [
            'patients' => [
                'model' => Patient::class,
                'title' => 'Data Pasien',
                'icon' => '♙',
                'roles' => ['admin', 'dokter', 'petugas'],
                'readRoles' => ['admin', 'dokter', 'petugas'],
                'columns' => [
                    ['medical_record_no', 'No. RM'], ['full_name', 'Nama Pasien'], ['nik', 'NIK'],
                    ['birth_date', 'Tanggal Lahir'], ['gender', 'Gender'], ['phone', 'Telepon'],
                ],
                'fields' => [
                    ['full_name', 'Nama Lengkap', 'text', true],
                    ['nik', 'NIK (16 digit)', 'text', false],
                    ['birth_date', 'Tanggal Lahir', 'date', false],
                    ['gender', 'Jenis Kelamin', 'select', false, ['Laki-laki', 'Perempuan']],
                    ['phone', 'No. HP', 'text', false],
                    ['address', 'Alamat', 'textarea', false],
                    ['medical_notes', 'Catatan Medis (terbatas)', 'textarea', false],
                ],
                'rules' => [
                    // medical_record_no dibuat OTOMATIS sistem (RM-YYMM-NNNN) — bukan input.
                    'full_name' => ['required', 'string', 'max:160'],
                    'nik' => ['nullable', 'string', 'regex:/^\d{16}$/'],
                    'birth_date' => ['nullable', 'date', 'before:tomorrow'],
                    'gender' => ['nullable', 'in:Laki-laki,Perempuan'],
                    'phone' => ['nullable', 'string', 'max:30'],
                    'address' => ['nullable', 'string', 'max:1000'],
                    'medical_notes' => ['nullable', 'string', 'max:2000'],
                ],
            ],
            'medicines' => [
                'model' => Medicine::class,
                'title' => 'Data Obat',
                'icon' => '✚',
                'roles' => ['admin', 'petugas'],
                'readRoles' => ['admin', 'petugas'],
                'columns' => [
                    ['code', 'Kode'], ['name', 'Nama Obat'], ['unit', 'Satuan'],
                    ['stock', 'Stok'], ['min_stock', 'Minimum'], ['expiry_date', 'Kedaluwarsa'],
                ],
                'fields' => [
                    ['code', 'Kode Obat', 'text', true],
                    ['name', 'Nama Obat', 'text', true],
                    ['unit', 'Satuan', 'text', true],
                    ['stock', 'Jumlah Stok', 'number', true],
                    ['min_stock', 'Stok Minimum', 'number', true],
                    ['expiry_date', 'Tanggal Kedaluwarsa', 'date', false],
                ],
                'rules' => [
                    'code' => ['required', 'string', 'max:50'],
                    'name' => ['required', 'string', 'max:180'],
                    'unit' => ['required', 'string', 'max:50'],
                    'stock' => ['required', 'integer', 'min:0', 'max:100000000'],
                    'min_stock' => ['required', 'integer', 'min:0', 'max:100000000'],
                    'expiry_date' => ['nullable', 'date'],
                ],
            ],
            'finances' => [
                'model' => Finance::class,
                'title' => 'Keuangan',
                'icon' => '▥',
                'roles' => ['admin', 'keuangan'],
                'readRoles' => ['admin', 'keuangan'],
                'columns' => [
                    ['transaction_date', 'Tanggal'], ['type', 'Jenis'],
                    ['category', 'Kategori'], ['description', 'Keterangan'], ['amount', 'Nominal'],
                ],
                'fields' => [
                    ['transaction_date', 'Tanggal Transaksi', 'date', true],
                    ['type', 'Jenis Transaksi', 'select', true, ['Pemasukan', 'Pengeluaran']],
                    ['category', 'Kategori', 'text', true],
                    ['description', 'Keterangan', 'textarea', false],
                    ['amount', 'Nominal (Rp)', 'number', true],
                ],
                'rules' => [
                    'transaction_date' => ['required', 'date', 'before_or_equal:today'],
                    'type' => ['required', 'in:Pemasukan,Pengeluaran'],
                    'category' => ['required', 'string', 'max:120'],
                    'description' => ['nullable', 'string', 'max:1000'],
                    'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999999999'],
                ],
            ],
            'employees' => [
                'model' => Employee::class,
                'title' => 'Data Pegawai',
                'icon' => '♧',
                'roles' => ['admin'],
                'readRoles' => ['admin'],
                'columns' => [
                    ['name', 'Nama Pegawai'], ['nip', 'NIP'], ['position', 'Jabatan'], ['unit', 'Unit'],
                ],
                'fields' => [
                    ['name', 'Nama Pegawai', 'text', true],
                    ['nip', 'NIP', 'text', false],
                    ['position', 'Jabatan', 'text', true],
                    ['unit', 'Unit Kerja', 'text', false],
                    ['phone', 'Nomor HP', 'text', false],
                ],
                'rules' => [
                    'name' => ['required', 'string', 'max:160'],
                    'nip' => ['nullable', 'string', 'max:32'],
                    'position' => ['required', 'string', 'max:160'],
                    'unit' => ['nullable', 'string', 'max:100'],
                    'phone' => ['nullable', 'string', 'max:30'],
                ],
            ],
            'doctors' => [
                'model' => Doctor::class,
                'title' => 'Data Dokter',
                'icon' => '♜',
                'roles' => ['admin', 'petugas'],
                'readRoles' => ['admin', 'petugas'],
                'columns' => [
                    ['name', 'Dokter'], ['specialty', 'Spesialisasi/Poli'], ['sip_number', 'SIP'],
                ],
                'fields' => [
                    ['name', 'Nama Dokter', 'text', true],
                    ['specialty', 'Poli / Spesialisasi', 'text', true],
                    ['sip_number', 'Nomor SIP', 'text', false],
                    ['phone', 'Nomor HP', 'text', false],
                ],
                'rules' => [
                    'name' => ['required', 'string', 'max:160'],
                    'specialty' => ['required', 'string', 'max:100'],
                    'sip_number' => ['nullable', 'string', 'max:100'],
                    'phone' => ['nullable', 'string', 'max:30'],
                ],
            ],
            'schedules' => [
                'model' => Schedule::class,
                'title' => 'Jadwal Praktik',
                'icon' => '▦',
                'roles' => ['admin', 'petugas'],
                'readRoles' => ['admin', 'petugas', 'dokter'], // dokter hanya baca
                'columns' => [
                    ['doctor_name', 'Dokter'], ['day', 'Hari'],
                    ['time_start', 'Mulai'], ['time_end', 'Selesai'], ['room', 'Ruang'],
                ],
                'fields' => [
                    ['doctor_id', 'Dokter', 'doctor', true],
                    ['day', 'Hari Praktik', 'select', true, self::DAYS],
                    ['time_start', 'Jam Mulai', 'time', true],
                    ['time_end', 'Jam Selesai', 'time', true],
                    ['room', 'Ruang / Poli', 'text', false],
                ],
                'rules' => [
                    'doctor_id' => ['required', 'integer', 'exists:doctors,id'],
                    'day' => ['required', 'in:'.implode(',', self::DAYS)],
                    'time_start' => ['required', 'date_format:H:i'],
                    'time_end' => ['required', 'date_format:H:i', 'after:time_start'],
                    'room' => ['nullable', 'string', 'max:80'],
                ],
            ],
        ];
    }

    public static function find(string $module): ?array
    {
        return self::all()[$module] ?? null;
    }

    /** URL untuk tombol menu sidebar. */
    public static function urlFor(string $key): string
    {
        return match ($key) {
            'dashboard' => route('dashboard'),
            'surveys' => route('surveys.index'),
            'users' => route('users.index'),
            'terminal' => route('terminal.index'),
            'laporan' => route('reports.index'),
            'resep' => url('/resep'),
            'notifikasi' => url('/notifikasi'),
            'log' => url('/log-aktivitas'),
            'pengaturan' => url('/pengaturan/smtp'),
            'profil' => url('/profil'),
            default => url('/modul/'.$key),
        };
    }

    /** Menu sidebar sesuai peran (semua modul + halaman khusus). */
    public static function menuFor(string $role): array
    {
        $menu = [
            'dashboard' => ['◫', 'Dashboard'],
            'surveys' => ['☷', 'Survei Kepuasan'],
            'users' => ['⚙', 'Pengguna & Akses'],
        ];

        $items = ['dashboard' => $menu['dashboard']];
        foreach (self::all() as $key => $m) {
            if (in_array($role, $m['readRoles'], true)) {
                $items[$key] = [$m['icon'], $m['title']];
            }
        }
        if (in_array($role, ['admin', 'dokter', 'petugas'], true)) {
            $items['resep'] = ['℞', 'Resep & Obat'];
        }
        if (in_array($role, ['admin', 'petugas'], true)) {
            $items['surveys'] = $menu['surveys'];
        }
        if (\App\Support\Reports::allowedFor($role) !== []) {
            $items['laporan'] = ['▤', 'Laporan'];
        }
        if ($role === 'admin') {
            $items['users'] = $menu['users'];
            $items['log'] = ['☰', 'Log Aktivitas'];
            $items['pengaturan'] = ['✉', 'Pengaturan SMTP'];
            $items['terminal'] = ['⌨', 'Terminal'];
        }

        return $items;
    }
}
