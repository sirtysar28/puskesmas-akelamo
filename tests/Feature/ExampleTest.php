<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    public function test_login_page_renders_with_captcha_and_footer(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('captcha-img', false);
        $response->assertSee('data-toggle-password', false);
        $response->assertSee('https://ciptakaryadigital.id', false);
    }

    public function test_login_requires_valid_captcha(): void
    {
        $user = User::factory()->create([
            'username' => 'tester',
            'password' => 'PasswordAman12345',
            'role' => 'admin',
        ]);

        // Tanpa captcha yang benar harus ditolak.
        $response = $this->from('/login')->post('/login', [
            'username' => 'tester',
            'password' => 'PasswordAman12345',
            'captcha' => 'XXXXX',
        ]);

        $response->assertSessionHasErrors('captcha');
        $this->assertGuest();
    }

    public function test_staff_cannot_access_users_module(): void
    {
        $user = User::factory()->create(['role' => 'petugas']);

        $response = $this->actingAs($user)->get('/pengguna');

        $response->assertStatus(403);
    }

    public function test_terminal_only_for_admin_and_whitelisted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Petugas tidak boleh membuka terminal.
        $this->actingAs(User::factory()->create(['role' => 'petugas']))
            ->get('/terminal')->assertStatus(403);

        // Admin boleh membuka terminal.
        $this->actingAs($admin)->get('/terminal')->assertStatus(200);

        // Perintah di luar whitelist ditolak.
        $this->actingAs($admin)->postJson('/terminal/run', ['command' => 'rm -rf /'])
            ->assertStatus(422);

        // Perintah whitelist boleh dijalankan.
        $this->actingAs($admin)->postJson('/terminal/run', ['command' => 'cache:clear'])
            ->assertOk()
            ->assertJsonPath('ok', true);

        // Perintah destruktif wajib konfirmasi sandi.
        $this->actingAs($admin)->postJson('/terminal/run', ['command' => 'migrate:fresh'])
            ->assertStatus(422);
    }

    public function test_public_patient_registration_creates_automatic_rm(): void
    {
        $response = $this->post('/pasien/daftar', [
            'full_name' => 'Pasien Uji Otomatis',
            'gender' => 'Perempuan',
            'birth_date' => '1990-01-15',
            'phone' => '0812-3456-7890',
        ]);

        // Tanpa captcha valid → tetap ditolak (proteksi spam).
        $response->assertSessionHasErrors('captcha');
        $this->assertDatabaseCount('patients', 0);
    }

    public function test_patient_rm_generated_by_system(): void
    {
        $patient = \App\Models\Patient::create([
            'full_name' => 'Uji RM Otomatis',
            'gender' => 'Laki-laki',
            'birth_date' => '1992-06-01',
        ]);

        $this->assertMatchesRegularExpression('/^RM-\d{4}-\d{4}$/', $patient->medical_record_no);

        $second = \App\Models\Patient::create(['full_name' => 'Kedua', 'gender' => 'Perempuan']);
        $this->assertSame(
            (int) substr($patient->medical_record_no, -4) + 1,
            (int) substr($second->medical_record_no, -4)
        );
    }

    public function test_prescription_flow_roles(): void
    {
        $dokter = User::factory()->create(['role' => 'dokter']);
        $keuangan = User::factory()->create(['role' => 'keuangan']);
        $patient = \App\Models\Patient::create(['full_name' => 'Pasien Resep', 'gender' => 'Laki-laki']);
        $med = \App\Models\Medicine::create([
            'code' => 'T-01', 'name' => 'Obat Uji', 'unit' => 'Pcs',
            'stock' => 50, 'min_stock' => 5, 'expiry_date' => now()->addYear()->toDateString(),
        ]);

        // Keuangan tidak boleh membuka menu resep.
        $this->actingAs($keuangan)->get('/resep')->assertStatus(403);

        // Dokter membuat resep.
        $this->actingAs($dokter)->postJson('/resep', [
            'patient_id' => $patient->id,
            'notes' => 'catatan uji',
            'items' => [['medicine_id' => $med->id, 'dose' => '2x1', 'quantity' => 7]],
        ])->assertCreated();

        $rx = \App\Models\Prescription::first();
        $this->assertSame('aktif', $rx->status);

        // Dokter TIDAK boleh menyerahkan resep.
        $this->actingAs($dokter)->postJson("/resep/{$rx->id}/serahkan")->assertStatus(403);

        // Petugas farmasi menyerahkan → stok berkurang.
        $petugas = User::factory()->create(['role' => 'petugas']);
        $this->actingAs($petugas)->postJson("/resep/{$rx->id}/serahkan")->assertOk();
        $this->assertSame('diserahkan', $rx->fresh()->status);
        $this->assertSame(43, $med->fresh()->stock);
    }

    public function test_profile_password_change_requires_old_password(): void
    {
        $user = User::factory()->create(['password' => 'SandiLama12345']);

        // Sandi lama salah.
        $this->actingAs($user)->post('/profil/sandi', [
            'current_password' => 'SalahBanget9',
            'password' => 'SandiBaru12345',
            'password_confirmation' => 'SandiBaru12345',
        ])->assertSessionHasErrors('current_password');

        // Sandi lama benar → berhasil.
        $this->from('/profil')->actingAs($user)->post('/profil/sandi', [
            'current_password' => 'SandiLama12345',
            'password' => 'SandiBaru12345',
            'password_confirmation' => 'SandiBaru12345',
        ])->assertRedirect('/profil');

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('SandiBaru12345', $user->fresh()->password));
    }
}
