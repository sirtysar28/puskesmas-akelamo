<?php

namespace Database\Seeders;

use App\Models\AppNotification;
use App\Models\Doctor;
use App\Models\Employee;
use App\Models\Finance;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Schedule;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Data dummy untuk pengembangan/demo (BUKAN akun produksi).
 * Jalankan: php artisan db:seed --class=DemoSeeder
 *
 * Akun demo (HANYA untuk lingkungan demo — hapus di produksi):
 *   adminakelamo   → dibuat lewat akelamo:init-admin
 *   dokterakelamo  / DokterAkelamo#2026   (role: dokter)
 *   petugasakelamo / PetugasAkelamo#2026  (role: petugas)
 *   keuanganakelamo/ KeuanganAkelamo#2026 (role: keuangan)
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Patient::exists() || Doctor::exists()) {
            $this->command?->warn('Data contoh sudah ada — seeder dilewati.');

            return;
        }

        $this->seedUsers();
        $this->seedDoctorsAndSchedules();
        $this->seedMedicines();
        $this->seedEmployees();
        $this->seedPatients();
        $this->seedFinances();
        $this->seedSurveys();
        $this->seedPrescriptions();
        $this->seedNotifications();
    }

    private function seedUsers(): void
    {
        $demo = [
            ['dr. Ahmad Hidayat, Sp.PD', 'dokterakelamo', 'dokter', 'DokterAkelamo#2026', 'dokter.akelamo@demo.id'],
            ['Nur Aisyah, S.Tr.Kes', 'petugasakelamo', 'petugas', 'PetugasAkelamo#2026', 'petugas.akelamo@demo.id'],
            ['Rudi Hartono, S.E.', 'keuanganakelamo', 'keuangan', 'KeuanganAkelamo#2026', 'keuangan.akelamo@demo.id'],
        ];
        foreach ($demo as [$name, $username, $role, $pass, $email]) {
            if (! User::where('username', $username)->exists()) {
                User::create([
                    'full_name' => $name, 'username' => $username, 'email' => $email,
                    'password' => $pass, 'role' => $role, 'is_active' => true,
                ]);
            }
        }
        // Email admin (untuk demo notifikasi SMTP — ganti di produksi)
        User::where('username', 'adminakelamo')->whereNull('email')->update(['email' => 'admin.akelamo@demo.id']);
    }

    private function seedDoctorsAndSchedules(): void
    {
        $dokter = [
            ['dr. Ahmad Hidayat, Sp.PD', 'Penyakit Dalam', 'SIP/441/2024/001', '0821-1111-0001'],
            ['dr. Siti Nurhaliza', 'Poli Umum', 'SIP/441/2024/002', '0821-1111-0002'],
            ['drg. Budi Santoso', 'Poli Gigi', 'SIP/441/2024/003', '0821-1111-0003'],
            ['dr. Maya Anggraini', 'KIA/KB', 'SIP/441/2024/004', '0821-1111-0004'],
            ['dr. Rizki Pratama', 'Laboratorium', 'SIP/441/2024/005', '0821-1111-0005'],
            ['dr. Fatimah Sari', 'Poli Gigi', 'SIP/441/2024/006', '0821-1111-0006'],
        ];
        foreach ($dokter as [$name, $sp, $sip, $hp]) {
            $d = Doctor::create(['name' => $name, 'specialty' => $sp, 'sip_number' => $sip, 'phone' => $hp]);

            // Jadwal 2 slot per dokter
            $hari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            $jam = [['08:00', '12:00'], ['13:30', '16:30']];
            $idx = (int) $d->id % count($hari);
            foreach ([0, 1] as $j) {
                Schedule::create([
                    'doctor_id' => $d->id,
                    'day' => $hari[($idx + $j) % count($hari)],
                    'time_start' => $jam[$j][0],
                    'time_end' => $jam[$j][1],
                    'room' => 'Poli '.$sp,
                ]);
            }
        }
    }

    private function seedMedicines(): void
    {
        // [kode, nama, satuan, stok, min, bulan-kedaluwarsa-dari-sekarang]
        $obat = [
            ['OBT-001', 'Paracetamol 500mg', 'Pcs', 1250, 200, 18],
            ['OBT-002', 'Amoxicillin 500mg', 'Pcs', 340, 100, 12],
            ['OBT-003', 'ORS (Oralit)', 'Sachet', 88, 150, 8],
            ['OBT-004', 'Vitamin B Complex', 'Botol', 210, 50, 20],
            ['OBT-005', 'Antasida DOEN', 'Pcs', 60, 80, 6],
            ['OBT-006', 'Cetirizine 10mg', 'Pcs', 430, 100, 15],
            ['OBT-007', 'Amoxicillin Sirup Kering', 'Botol', 75, 90, 10],
            ['OBT-008', 'Dexamethasone 0.5mg', 'Pcs', 520, 120, 22],
            ['OBT-009', 'Metformin 500mg', 'Pcs', 310, 100, 16],
            ['OBT-010', 'OBH Combi Sirup', 'Botol', 145, 60, 9],
            ['OBT-011', 'Salep Gentamicin', 'Tube', 96, 40, 13],
            ['OBT-012', 'Infus Ringer Laktat', 'Kolf', 210, 80, 24],
            ['OBT-013', 'Insulin Soluble', 'Vial', 34, 50, 5],
            ['OBT-014', 'Salbutamol Inhaler', 'Pcs', 58, 30, 11],
            ['OBT-015', 'Amlodipine 5mg', 'Pcs', 380, 100, 19],
        ];
        foreach ($obat as [$code, $name, $unit, $stock, $min, $months]) {
            Medicine::create([
                'code' => $code, 'name' => $name, 'unit' => $unit,
                'stock' => $stock, 'min_stock' => $min,
                'expiry_date' => now()->addMonths($months)->toDateString(),
            ]);
        }
    }

    private function seedEmployees(): void
    {
        $pegawai = [
            ['Nur Aisyah, S.Tr.Kes', '19850312 201001 2 001', 'Kepala Puskesmas', 'Umum', '0822-2222-0001'],
            ['Rudi Hartono, S.E.', '19900520 201501 2 002', 'Bendahara', 'Keuangan', '0822-2222-0002'],
            ['Lilis Suryani', '19880214 201203 2 003', 'Perawat', 'Poli Umum', '0822-2222-0003'],
            ['Joko Susilo', '19910707 201804 2 004', 'Petugas Farmasi', 'Farmasi', '0822-2222-0004'],
            ['Dewi Lestari', '19920923 201906 2 005', 'Petugas Pendaftaran', 'Pendaftaran', '0822-2222-0005'],
            ['Bambang Wijaya', '19860630 201102 2 006', 'Bidan', 'KIA/KB', '0822-2222-0006'],
            ['Rina Marlina', '19931115 202003 2 007', 'Sanitarian', 'Kesehatan Lingkungan', '0822-2222-0007'],
            ['Agus Setiawan', '19880125 201405 2 008', 'Pengemudi Ambulans', 'Umum', '0822-2222-0008'],
            ['Sri Wahyuni', '19950408 202102 2 009', 'Petugas Gizi', 'Kesehatan Gizi', '0822-2222-0009'],
            ['Hendra Gunawan', '19900219 201607 2 010', 'Analis Laboratorium', 'Laboratorium', '0822-2222-0010'],
            ['Maria Fransiska', '19910827 201712 2 011', 'Perawat Gigi', 'Poli Gigi', '0822-2222-0011'],
            ['Yusuf Abdullah', '19841002 200909 2 012', 'Petugas Rekam Medis', 'Pendaftaran', '0822-2222-0012'],
        ];
        foreach ($pegawai as [$n, $nip, $pos, $unit, $hp]) {
            Employee::create(['name' => $n, 'nip' => $nip, 'position' => $pos, 'unit' => $unit, 'phone' => $hp]);
        }
    }

    private function seedPatients(): void
    {
        // 40 pasien tersebar 12 bulan terakhir (untuk grafik tren).
        $depan = ['Andi', 'Fatimah', 'Budi', 'Rina', 'Muhammad', 'Siti', 'Joko', 'Dewi', 'Agus', 'Maria',
            'Yusuf', 'Nur', 'Hendra', 'Sri', 'Bambang', 'Lilis', 'Rizki', 'Fitri', 'Doni', 'Ayu',
            'Rahmat', 'Indah', 'Taufik', 'Nadia', 'Fajar', 'Melati', 'Rudi', 'Zainab', 'Iwan', 'Kartika'];
        $belakang = ['Saputra', 'Zahra', 'Pratama', 'Wati', 'Hidayat', 'Ramadan', 'Santoso', 'Lestari', 'Setiawan',
            'Gunawan', 'Wijaya', 'Marlina', 'Susanti', 'Hartono', 'Nugroho', 'Anggraini', 'Maulana', 'Puspita'];
        $alamat = ['Jl. Merdeka No. %d', 'Jl. Diponegoro No. %d', 'Dusun II Akelamo RT %d', 'Jl. Pahlawan No. %d',
            'Jl. Melati No. %d', 'Kampung Baru Akelamo %d', 'Jl. Kesdam No. %d', 'Pantai Akelamo Blok %d'];

        for ($i = 0; $i < 40; $i++) {
            $monthsAgo = intdiv($i * 12, 40); // 0..11 tersebar merata
            $date = now()->subMonths($monthsAgo)->addDays(random_int(-12, 12));
            $gender = $i % 3 === 0 ? 'Perempuan' : ($i % 2 === 0 ? 'Laki-laki' : 'Perempuan');
            $name = $depan[$i % count($depan)].' '.$belakang[($i * 7) % count($belakang)];

            $p = Patient::create([
                // medical_record_no DIBUAT OTOMATIS SISTEM (RM-YYMM-NNNN)
                'full_name' => $name,
                'nik' => sprintf('8271%02d%02d%02d%d%04d', 11, ($i % 28) + 1, ($i % 12) + 1, $gender === 'Perempuan' ? 4 : 1, $i + 1),
                'birth_date' => now()->subYears(random_int(3, 68))->subDays(random_int(0, 300))->toDateString(),
                'gender' => $gender,
                // ±85% pasien punya HP, ±90% punya alamat lengkap (untuk venn kualitas data)
                'phone' => $i % 7 === 0 ? null : sprintf('08%02d-4444-%04d', 11 + ($i % 40), $i + 100),
                'address' => $i % 10 === 0 ? null : sprintf($alamat[$i % count($alamat)], $i + 1),
                'medical_notes' => $i % 4 === 0 ? 'Riwayat hipertensi, kontrol rutin.' : null,
            ]);
            $p->created_at = $date;
            $p->save();
        }
    }

    private function seedFinances(): void
    {
        // ±70 transaksi selama 8 bulan terakhir — tren naik & pola realistis.
        $pemasukan = [
            ['BOP Puskesmas', 'Dana operasional bulanan dari Dinkes'],
            ['JKN/BPJS', 'Klaim pelayanan JKN bulan berjalan'],
            ['Pendapatan Swasta', 'Pelayanan pasien non-JKN'],
            ['Bantuan Provinsi', 'Bantuan operasional kesehatan'],
            ['Blud/Retribusi', 'Penerimaan tindakan medis'],
        ];
        $pengeluaran = [
            ['Pembelian Obat', 'Restock obat resep & konsinyasi'],
            ['Operasional', 'Listrik, air, dan internet'],
            ['Honor Tenaga Kontrak', 'Honor tenaga kesehatan kontrak'],
            ['Pemeliharaan Alat', 'Kalibrasi & servis alat kesehatan'],
            ['Konsumsi & ATK', 'Keperluan administrasi harian'],
            ['Transport & Perjalanan Dinas', 'Dinas luar wilayah kerja'],
        ];

        for ($m = 7; $m >= 0; $m--) {
            $base = now()->subMonths($m)->startOfMonth();
            $tren = 1 + (7 - $m) * 0.06; // tren naik perlahan

            foreach ($pemasukan as $k => [$cat, $desc]) {
                $amount = match ($k) {
                    0 => 150000000 + random_int(-15000000, 25000000),
                    1 => 95000000 + random_int(-10000000, 22000000),
                    2 => 15000000 + random_int(-3000000, 9000000),
                    3 => 60000000 + random_int(-8000000, 12000000),
                    default => 25000000 + random_int(-5000000, 8000000),
                };
                $f = Finance::create([
                    'transaction_date' => $base->addDays(random_int(0, 27))->toDateString(),
                    'type' => 'Pemasukan', 'category' => $cat, 'description' => $desc,
                    'amount' => round($amount * $tren / 1000) * 1000,
                ]);
                $f->created_at = $base->copy()->addDays(random_int(0, 27));
                $f->save();
            }

            foreach ($pengeluaran as $k => [$cat, $desc]) {
                if ($k === 5 && random_int(0, 1) === 0) {
                    continue; // kadang tidak ada dinas luar
                }
                $amount = match ($k) {
                    0 => 45000000 + random_int(-8000000, 15000000),
                    1 => 2400000 + random_int(-300000, 600000),
                    2 => 38000000 + random_int(-4000000, 7000000),
                    3 => 8500000 + random_int(-2000000, 4000000),
                    default => 6000000 + random_int(-1500000, 3000000),
                };
                $f = Finance::create([
                    'transaction_date' => $base->addDays(random_int(0, 27))->toDateString(),
                    'type' => 'Pengeluaran', 'category' => $cat, 'description' => $desc,
                    'amount' => round($amount * $tren / 1000) * 1000,
                ]);
                $f->created_at = $base->copy()->addDays(random_int(0, 27));
                $f->save();
            }
        }
    }

    /** 12 resep: 8 aktif + 4 diserahkan (stok obat berkurang, transaksi konsisten). */
    private function seedPrescriptions(): void
    {
        $dokterUser = User::where('username', 'dokterakelamo')->first() ?? User::first();
        $petugasUser = User::where('username', 'petugasakelamo')->first() ?? $dokterUser;
        $patients = Patient::orderBy('id')->get();
        $meds = Medicine::all();
        $doses = ['3x1 sesudah makan', '2x1 sesudah makan', '1x1 malam hari', '3x1 sesudah makan', '2x1 pagi & malam', '1x1 saat perlu'];
        $notes = ['Tekanan darah 130/85, kontrol 2 minggu.', 'Demam 2 hari, anjuran istirahat.', 'Kontrol rutin DM.', 'Batuk berdahak, perbanyak air.', null, 'Alergi obat: tidak ada.', null, null, 'Pusing & mual berkurang.', null, 'Asma terkendali, lanjut inhaler.', null];

        for ($i = 0; $i < 12; $i++) {
            $rx = Prescription::create([
                'patient_id' => $patients[$i % $patients->count()]->id,
                'doctor_id' => Doctor::where('name', 'like', '%'.explode(' ', $dokterUser->full_name)[1].'%')->value('id'),
                'user_id' => $dokterUser->id,
                'status' => 'aktif',
                'notes' => $notes[$i],
                'created_at' => now()->subDays(random_int(0, 20))->setTime(random_int(8, 15), random_int(0, 59)),
            ]);

            $count = random_int(1, 3);
            $chosen = $meds->random($count);
            foreach ($chosen as $med) {
                $qty = max(1, min(20, random_int(3, 10), $med->stock));
                PrescriptionItem::create([
                    'prescription_id' => $rx->id,
                    'medicine_id' => $med->id,
                    'dose' => $doses[random_int(0, count($doses) - 1)],
                    'quantity' => $qty,
                ]);
                // 4 resep pertama sudah diserahkan farmasi → stok berkurang
                if ($i < 4) {
                    $med->decrement('stock', $qty);
                }
            }

            if ($i < 4) {
                $rx->update(['status' => 'diserahkan']);
                AppNotification::add('resep', 'Resep #'.$rx->id.' diserahkan', 'Obat telah diserahkan ke pasien & stok dikurangi otomatis.', '/resep', $petugasUser->id);
            } else {
                AppNotification::add('resep', 'Resep baru untuk '.$rx->patient->full_name, 'Resep #'.$rx->id.' menunggu penyerahan obat di farmasi.', '/resep', $dokterUser->id);
            }
        }
    }

    /** Contoh notifikasi lonceng (keuangan terbaru + registrasi pasien). */
    private function seedNotifications(): void
    {
        Finance::latest('id')->limit(4)->get()->each(function (Finance $f) {
            AppNotification::add(
                'keuangan',
                'Transaksi '.$f->type.' Rp '.number_format((float) $f->amount, 0, ',', '.'),
                'Kategori "'.$f->category.'" tercatat pada '.$f->transaction_date->format('d/m/Y').'.',
                '/modul/finances'
            );
        });

        Patient::latest('id')->limit(2)->get()->each(fn (Patient $p) => AppNotification::add(
            'pasien',
            'Registrasi pasien baru: '.$p->full_name,
            'Nomor RM '.$p->medical_record_no.' — silakan verifikasi data di loket pendaftaran.',
            '/modul/patients'
        ));
    }

    private function seedSurveys(): void
    {
        $services = Survey::SERVICES;
        for ($i = 0; $i < 45; $i++) {
            $daysAgo = random_int(0, 59);
            // Distribusi condong puas: 5★=20, 4★=14, 3★=6, 2★=3, 1★=2
            $roll = $i % 45;
            $rating = $roll < 20 ? 5 : ($roll < 34 ? 4 : ($roll < 40 ? 3 : ($roll < 43 ? 2 : 1)));
            $saran = [
                'Pelayanan sudah baik, pertahankan.',
                'Antrean cukup lama di pagi hari.',
                'Petugas ramah dan informatif.',
                'Ruangan tunggu perlu ditambah kipas.',
                'Obat sering kosong, mohon stok ditambah.',
                'Parkir kurang luas.',
            ];
            $s = Survey::create([
                'service' => $services[$i % count($services)],
                'rating' => $rating,
                'suggestion' => $i % 3 === 0 ? $saran[$i % count($saran)] : null,
                'created_at' => now()->subDays($daysAgo)->setTime(random_int(8, 15), random_int(0, 59)),
            ]);
            $s->save();
        }
    }
}
