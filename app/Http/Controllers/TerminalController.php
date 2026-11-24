<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Terminal web khusus Super Admin (role: admin).
 *
 * Prinsip keamanan:
 * - BUKAN shell sungguhan. Tidak ada proc_open/exec — perintah dieksekusi
 *   lewat Artisan::call() murni di dalam PHP.
 * - WHITELIST KETAT: hanya nama perintah + flag yang terdaftar di COMMANDS.
 *   Argumen posisi tidak diizinkan. Perintah/flag apa pun di luar daftar → ditolak.
 * - Perintah destruktif (migrate:fresh, migrate:rollback) wajib konfirmasi
 *   kata sandi akun (pola "sudo mode") — melindungi dari sesi yang dibajak.
 * - Rate limit 12x/menit + audit log untuk setiap eksekusi.
 * - Bisa dimatikan total lewat TERMINAL_ENABLED=false (.env).
 *
 * Catatan operasional: driver session & cache = database. Perintah migrasi
 * destruktif dapat menghapus tabel sistem (users/sessions/cache), sehingga
 * setelah eksekusi terminal otomatis: (1) menjalankan ulang migrasi SISTEM
 * via --path (no-op bila sudah ada), (2) memulihkan akun admin yang sedang
 * login agar tidak terkunci. Admin tetap diminta login ulang karena data
 * sesinya direset.
 */
class TerminalController extends Controller
{
    /** Migrasi sistem (users+sessions, cache, jobs) — dijalankan ulang bila ikut ter-drop. */
    private const SYSTEM_MIGRATIONS = [
        'database/migrations/0001_01_01_000000_create_users_table.php',
        'database/migrations/0001_01_01_000001_create_cache_table.php',
        'database/migrations/0001_01_01_000002_create_jobs_table.php',
    ];

    /** @var array<string, array{flags: list<string>, danger: bool, desc: string}> */
    private const COMMANDS = [
        'cache:clear' => ['flags' => [], 'danger' => false, 'desc' => 'Hapus cache aplikasi'],
        'config:clear' => ['flags' => [], 'danger' => false, 'desc' => 'Hapus cache konfigurasi'],
        'config:cache' => ['flags' => [], 'danger' => false, 'desc' => 'Buat cache konfigurasi (produksi)'],
        'route:clear' => ['flags' => [], 'danger' => false, 'desc' => 'Hapus cache rute'],
        'route:cache' => ['flags' => [], 'danger' => false, 'desc' => 'Buat cache rute (produksi)'],
        'view:clear' => ['flags' => [], 'danger' => false, 'desc' => 'Hapus cache view Blade'],
        'event:clear' => ['flags' => [], 'danger' => false, 'desc' => 'Hapus cache event/listener'],
        'optimize' => ['flags' => [], 'danger' => false, 'desc' => 'Cache config + rute + view sekaligus'],
        'optimize:clear' => ['flags' => [], 'danger' => false, 'desc' => 'Hapus SEMUA cache optimasi'],
        'migrate' => ['flags' => ['--force'], 'danger' => false, 'desc' => 'Jalankan migrasi database tertunda'],
        'migrate:status' => ['flags' => [], 'danger' => false, 'desc' => 'Lihat status migrasi database'],
        'migrate:rollback' => ['flags' => ['--force'], 'danger' => true, 'desc' => 'Rollback batch migrasi terakhir (konfirmasi sandi)'],
        'migrate:fresh' => ['flags' => ['--force', '--seed'], 'danger' => true, 'desc' => 'DROP semua tabel lalu migrasi ulang (konfirmasi sandi)'],
    ];

    /** Halaman terminal. */
    public function index()
    {
        abort_unless(config('akelamo.terminal_enabled'), 404);

        return view('terminal', [
            'commands' => collect(self::COMMANDS)
                ->map(fn (array $c, string $name) => ['name' => $name] + $c)
                ->values()
                ->all(),
        ]);
    }

    /** Eksekusi perintah (POST, wajib CSRF + role admin + throttle). */
    public function run(Request $request): JsonResponse
    {
        abort_unless(config('akelamo.terminal_enabled'), 404);

        $data = $request->validate([
            'command' => ['required', 'string', 'max:120'],
            'password' => ['nullable', 'string', 'max:256'],
        ]);

        $parsed = $this->parse($data['command']);

        if ($parsed === null) {
            return response()->json([
                'error' => 'Perintah tidak dikenali atau tidak diizinkan. Terminal ini hanya menerima perintah artisan pada daftar cepat.',
            ], 422);
        }

        [$name, $flags, $danger] = $parsed;

        // Snapshot akun admin sebelum perintah destruktif (untuk pemulihan).
        $adminBackup = null;

        // Perintah destruktif → wajib konfirmasi kata sandi akun sendiri.
        if ($danger) {
            if (empty($data['password'])) {
                return response()->json([
                    'error' => 'Perintah ini bersifat destruktif. Masukkan kata sandi akun Anda sebagai konfirmasi.',
                    'need_password' => true,
                ], 422);
            }

            if (! Hash::check($data['password'], $request->user()->password)) {
                usleep(random_int(150000, 350000)); // seragamkan waktu respons

                return response()->json(['error' => 'Kata sandi konfirmasi salah.'], 403);
            }

            $u = $request->user();
            $adminBackup = [
                'id' => $u->id, 'full_name' => $u->full_name, 'username' => $u->username,
                'password' => $u->password, 'role' => $u->role, 'is_active' => $u->is_active,
                'created_at' => now(), 'updated_at' => now(),
            ];
        }

        $started = microtime(true);
        $buffer = new BufferedOutput;

        try {
            $exitCode = Artisan::call($name, $flags, $buffer);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['error' => 'Perintah gagal dijalankan: '.$e->getMessage()], 500);
        }

        $duration = (int) round((microtime(true) - $started) * 1000);

        // ---------- Pemulihan pasca-migrasi ----------
        $sessionsDropped = false;
        if (str_starts_with($name, 'migrate') && $name !== 'migrate:status') {
            $sessionsDropped = ! Schema::hasTable('sessions');
            $this->restoreSystemTables();
            $this->restoreAdmin($adminBackup);
        }

        $this->auditSafely($request->user()->id, $name, array_keys($flags));

        return response()->json([
            'ok' => $exitCode === 0,
            'command' => $name.' '.implode(' ', array_keys($flags)),
            'output' => mb_substr($buffer->fetch(), 0, 20000),
            'duration_ms' => $duration,
            'notice' => $sessionsDropped
                ? 'Tabel sesi ikut direset — Anda akan diminta login ulang setelah ini.'
                : null,
        ]);
    }

    // ---------------------------------------------------------------------

    /** Jalankan ulang migrasi sistem; no-op bila sudah berjalan. */
    private function restoreSystemTables(): void
    {
        try {
            foreach (self::SYSTEM_MIGRATIONS as $path) {
                Artisan::call('migrate', ['--force' => true, '--path' => $path]);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Pulihkan akun admin bila tabel users ikut ter-reset (anti lock-out). */
    private function restoreAdmin(?array $admin): void
    {
        if ($admin === null) {
            return;
        }

        try {
            if (! Schema::hasTable('users')) {
                return;
            }
            if (! DB::table('users')->where('id', $admin['id'])->exists()) {
                DB::table('users')->insert($admin);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Audit log yang aman: tabel users bisa saja baru direset. */
    private function auditSafely(int $actorId, string $name, array $flags): void
    {
        try {
            AuditLog::create([
                'actor_id' => $actorId,
                'action' => 'TERMINAL',
                'entity' => $name.' '.implode(' ', $flags),
                'record_id' => null,
            ]);
        } catch (\Throwable $e) {
            // dibiarkan — database mungkin baru saja direset
        }
    }

    /**
     * Parsing & validasi ketat.
     * Mengembalikan [nama, flags, danger] atau null bila ditolak.
     *
     * @return array{0:string,1:array<string,true>,2:bool}|null
     */
    private function parse(string $raw): ?array
    {
        // Buang prefiks yang lazim diketik pengguna: "php artisan", "artisan", "sudo".
        $cmd = trim((string) preg_replace('/^(sudo\s+)?(php\s+)?artisan\s+/i', '', trim($raw)));

        if ($cmd === '' || mb_strlen($cmd) > 120) {
            return null;
        }

        $parts = preg_split('/\s+/', $cmd) ?: [];
        $name = strtolower((string) array_shift($parts));

        if (! isset(self::COMMANDS[$name])) {
            return null; // tidak ada di whitelist
        }

        $allowed = self::COMMANDS[$name]['flags'];
        $flags = [];

        foreach ($parts as $part) {
            // Argumen posisi DILARANG — hanya flag terdaftar.
            if (! str_starts_with($part, '--') || ! in_array($part, $allowed, true)) {
                return null;
            }
            $flags[$part] = true;
        }

        return [$name, $flags, self::COMMANDS[$name]['danger']];
    }
}
