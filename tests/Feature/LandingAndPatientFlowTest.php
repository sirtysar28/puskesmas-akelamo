<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Alur baru: landing publik → registrasi pasien (akun otomatis) →
 * login pasien → daftar berobat online → verifikasi staf.
 * Serta matriks peran: kepala puskesmas melihat SEMUA, dokter/perawat terbatas.
 */
class LandingAndPatientFlowTest extends TestCase
{
    use RefreshDatabase;

    /** Helper: pasang hash captcha valid di session (driver array). */
    private function withCaptcha(string $code = 'test1'): static
    {
        return $this->session(['captcha_hash' => hash('sha256', strtolower($code))]);
    }

    // ------------------------------------------------------------------
    // 1) Landing page
    // ------------------------------------------------------------------

    public function test_landing_shows_profile_services_and_schedule(): void
    {
        $dokter = Doctor::create(['name' => 'dr. Contoh', 'specialty' => 'Poli Umum']);
        Schedule::create([
            'doctor_id' => $dokter->id, 'day' => 'Senin',
            'time_start' => '08:00', 'time_end' => '12:00', 'room' => 'Poli Umum',
        ]);

        $this->get('/')->assertOk()
            ->assertSee('Profil Puskesmas Akelamo')
            ->assertSee('Cara Berobat')
            ->assertSee('dr. Contoh');
    }

    // ------------------------------------------------------------------
    // 2) Registrasi pasien → akun otomatis (username = RM)
    // ------------------------------------------------------------------

    public function test_public_registration_creates_patient_and_login_account(): void
    {
        $response = $this->withCaptcha()->post('/pasien/daftar', [
            'full_name' => 'Marlina Test',
            'nik' => '8271110101810001',
            'gender' => 'Perempuan',
            'birth_date' => '1990-01-15',
            'phone' => '0812-3456-7890',
            'address' => 'Dusun II Akelamo',
            'password' => 'SandiPasien88',
            'password_confirmation' => 'SandiPasien88',
            'captcha' => 'TEST1',
        ]);

        $response->assertRedirect('/pasien')->assertSessionHas('rm_baru');

        $patient = Patient::first();
        $this->assertMatchesRegularExpression('/^RM-\d{4}-\d{4}$/', $patient->medical_record_no);

        $user = User::where('role', 'pasien')->first();
        $this->assertSame(mb_strtolower($patient->medical_record_no), $user->username);
        $this->assertSame($patient->user_id, $user->id);
        $this->assertTrue(Hash::check('SandiPasien88', $user->password));

        // Sandi tak cocok → ditolak.
        $this->withCaptcha()->post('/pasien/daftar', [
            'full_name' => 'Bedo Salah', 'gender' => 'Laki-laki', 'birth_date' => '1995-05-05',
            'password' => 'SandiSatu88', 'password_confirmation' => 'SandiBeda99', 'captcha' => 'TEST1',
        ])->assertSessionHasErrors('password_confirmation');
        $this->assertDatabaseCount('patients', 1);
    }

    // ------------------------------------------------------------------
    // 3) Login pasien → portal
    // ------------------------------------------------------------------

    public function test_patient_login_lands_on_portal_and_books_appointment(): void
    {
        $user = User::create([
            'full_name' => 'Pasien Portal', 'username' => 'rm-2601-0001',
            'password' => 'SandiPasien88', 'role' => 'pasien', 'is_active' => true,
        ]);
        $patient = Patient::create([
            'full_name' => 'Pasien Portal', 'gender' => 'Perempuan', 'birth_date' => '1993-03-03',
        ]);
        $patient->user_id = $user->id;
        $patient->save();

        // Login benar → portal pasien.
        $this->withCaptcha('login')->post('/login', [
            'username' => 'rm-2601-0001', 'password' => 'SandiPasien88', 'captcha' => 'LOGIN',
        ])->assertRedirect('/akun-pasien');

        // Dashboard staf otomatis dialihkan ke portal pasien.
        $this->actingAs($user)->get('/dashboard')->assertRedirect('/akun-pasien');

        // Portal menampilkan RM & formulir berobat.
        $this->actingAs($user)->get('/akun-pasien')->assertOk()
            ->assertSee($patient->medical_record_no)
            ->assertSee('Daftar Berobat Online');

        // Daftar berobat.
        $dokter = Doctor::create(['name' => 'dr. Jantung', 'specialty' => 'Penyakit Dalam']);
        $this->actingAs($user)->post('/akun-pasien/berobat', [
            'doctor_id' => $dokter->id,
            'visit_date' => now()->addDays(2)->toDateString(),
            'complaint' => 'Nyeri dada sejak kemarin.',
        ])->assertRedirect('/akun-pasien')->assertSessionHas('status');

        $this->assertDatabaseHas('appointments', [
            'patient_id' => $patient->id, 'doctor_id' => $dokter->id, 'status' => 'Menunggu',
        ]);

        // Duplikat tanggal sama → ditolak.
        $this->actingAs($user)->post('/akun-pasien/berobat', [
            'visit_date' => now()->addDays(2)->toDateString(),
        ])->assertSessionHasErrors('berobat');

        // Tanggal lampau → ditolak validasi.
        $this->actingAs($user)->post('/akun-pasien/berobat', [
            'visit_date' => now()->subDay()->toDateString(),
        ])->assertSessionHasErrors('visit_date');

        // Batalkan antrian sendiri.
        $appt = Appointment::first();
        $this->actingAs($user)->post("/akun-pasien/berobat/{$appt->id}/batal")
            ->assertRedirect('/akun-pasien');
        $this->assertSame('Batal', $appt->fresh()->status);
    }

    // ------------------------------------------------------------------
    // 4) Kepala puskesmas — akses SEMUA halaman & fitur
    // ------------------------------------------------------------------

    public function test_kepala_puskesmas_can_access_everything(): void
    {
        $kepala = User::factory()->create(['role' => 'kepala']);

        foreach ([
            '/dashboard', '/modul/patients', '/modul/medicines', '/modul/finances',
            '/modul/employees', '/modul/doctors', '/modul/schedules', '/modul/appointments',
            '/resep', '/survei', '/laporan', '/pengguna', '/log-aktivitas',
            '/pengaturan/smtp', '/terminal',
        ] as $url) {
            $this->actingAs($kepala)->get($url)->assertOk($url);
        }

        // Kepala boleh menulis modul (mis. ubah status janji temu).
        $patient = Patient::create(['full_name' => 'Pasien Kepala', 'gender' => 'Laki-laki']);
        $this->actingAs($kepala)->postJson('/modul/appointments', [
            'patient_id' => $patient->id,
            'visit_date' => now()->addDay()->toDateString(),
            'complaint' => 'Kontrol',
            'status' => 'Menunggu',
        ])->assertCreated();
    }

    // ------------------------------------------------------------------
    // 5) Dokter & perawat — akses terbatas
    // ------------------------------------------------------------------

    public function test_dokter_reads_but_cannot_manage_everything(): void
    {
        $dokter = User::factory()->create(['role' => 'dokter']);

        $this->actingAs($dokter)->get('/modul/appointments')->assertOk(); // baca jadwal pasien
        $this->actingAs($dokter)->get('/resep')->assertOk();

        $this->actingAs($dokter)->get('/modul/medicines')->assertStatus(403);
        $this->actingAs($dokter)->get('/modul/finances')->assertStatus(403);
        $this->actingAs($dokter)->get('/pengguna')->assertStatus(403);
        $this->actingAs($dokter)->get('/terminal')->assertStatus(403);

        // Dokter tidak boleh mengubah antrian (hanya baca).
        $patient = Patient::create(['full_name' => 'Pasien Dokter', 'gender' => 'Perempuan']);
        $this->actingAs($dokter)->postJson('/modul/appointments', [
            'patient_id' => $patient->id, 'visit_date' => now()->addDay()->toDateString(), 'status' => 'Menunggu',
        ])->assertStatus(403);
    }

    public function test_perawat_handles_patients_resep_and_appointments(): void
    {
        $perawat = User::factory()->create(['role' => 'perawat']);

        foreach (['/modul/patients', '/modul/appointments', '/modul/schedules', '/resep', '/survei'] as $url) {
            $this->actingAs($perawat)->get($url)->assertOk($url);
        }

        // Obat hanya boleh baca, keuangan & pengguna terlarang.
        $this->actingAs($perawat)->get('/modul/medicines')->assertOk();
        $this->actingAs($perawat)->postJson('/modul/medicines', [
            'code' => 'X-1', 'name' => 'Obat Baru', 'unit' => 'Pcs', 'stock' => 1, 'min_stock' => 1,
        ])->assertStatus(403);
        $this->actingAs($perawat)->get('/modul/finances')->assertStatus(403);
        $this->actingAs($perawat)->get('/pengguna')->assertStatus(403);
    }

    // ------------------------------------------------------------------
    // 6) Portal pasien tidak bisa diakses staf & sebaliknya
    // ------------------------------------------------------------------

    public function test_staff_cannot_open_patient_portal(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'petugas']))
            ->get('/akun-pasien')->assertStatus(403);
    }
}
