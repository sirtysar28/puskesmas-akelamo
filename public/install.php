<?php

/*
|--------------------------------------------------------------------------
| SIM Puskesmas Akelamo — Web Installer (hosting tanpa terminal/SSH)
|--------------------------------------------------------------------------
| Satu berkas mandiri untuk instalasi di cPanel/shared hosting:
|   1. Cek kebutuhan server (PHP, ekstensi, izin folder)
|   2. Form koneksi MySQL + akun administrator pertama
|   3. Menulis .env lengkap (termasuk APP_KEY acak & APP_URL otomatis)
|   4. Menjalankan migrasi database (artisan migrate --force)
|   5. Membuat akun admin + mengunci installer (storage/app/install.lock)
|
| Berkas ini TIDAK melewati routing/middleware Laravel sehingga tetap bisa
| diakses walau tabel sessions belum ada. SETELAH SELESAI: installer terkunci
| otomatis — hapus berkas ini untuk keamanan maksimal.
*/

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');

$BASE = dirname(__DIR__);
$LOCK = $BASE.'/storage/app/install.lock';

/* ---------------------------- Helper ---------------------------- */

function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

/** Baca file .env sederhana → array. */
function parseEnvFile(string $path): array
{
    $out = [];
    if (! is_file($path)) {
        return $out;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $v = trim($v);
        if (strlen($v) >= 2 && ($v[0] === '"' || $v[0] === "'") && str_ends_with($v, $v[0])) {
            $v = substr($v, 1, -1);
        }
        $out[trim($k)] = $v;
    }

    return $out;
}

/** Nilai .env yang aman (kutip bila mengandung karakter khusus). */
function envValue(?string $v): string
{
    $v = (string) $v;
    if ($v === '' || preg_match('/^[A-Za-z0-9_.\/-]+$/', $v)) {
        return $v;
    }

    return '"'.str_replace(['\\', '"', '$', "\n"], ['\\\\', '\\"', '\$', '\\n'], $v).'"';
}

/** Sudah terinstall? (lock ada ATAU sudah ada admin di database) */
function alreadyInstalled(string $base, string $lock): bool
{
    if (is_file($lock)) {
        return true;
    }
    $env = parseEnvFile($base.'/.env');
    try {
        if (($env['DB_CONNECTION'] ?? '') === 'sqlite') {
            $db = $env['DB_DATABASE'] ?? 'database/database.sqlite';
            $path = str_starts_with($db, '/') ? $db : $base.'/'.$db;
            if (! is_file($path)) {
                return false;
            }
            $pdo = new PDO('sqlite:'.$path);
        } elseif (($env['DB_CONNECTION'] ?? '') === 'mysql') {
            $pdo = new PDO(
                sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                    $env['DB_HOST'] ?? 'localhost', $env['DB_PORT'] ?? '3306', $env['DB_DATABASE'] ?? ''),
                $env['DB_USERNAME'] ?? '',
                $env['DB_PASSWORD'] ?? '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } else {
            return false;
        }
        $q = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");

        return $q !== false && (int) $q->fetchColumn() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

/** Pastikan direktori runtime Laravel tersedia. */
function ensureRuntimeDirs(string $base): void
{
    $dirs = [
        '/storage/app', '/storage/logs', '/storage/framework/cache', '/storage/framework/cache/data',
        '/storage/framework/sessions', '/storage/framework/views', '/bootstrap/cache',
    ];
    foreach ($dirs as $d) {
        $p = $base.$d;
        if (! is_dir($p)) {
            @mkdir($p, 0775, true);
        }
    }
}

/** Susun isi .env produksi lengkap. */
function buildEnv(array $in): string
{
    $key = $in['app_key'];
    $url = $in['app_url'];
    $driver = $in['driver'];
    $mailHost = parse_url($url, PHP_URL_HOST) ?: 'localhost';
    // Cookie Secure hanya bila hosting benar-benar HTTPS (cek skema permintaan).
    $secureCookie = str_starts_with($url, 'https://') ? 'true' : 'false';

    $dbLines = $driver === 'sqlite'
        ? "DB_CONNECTION=sqlite\nDB_DATABASE=database/database.sqlite"
        : "DB_CONNECTION=mysql\nDB_HOST=".envValue($in['db_host'])."\nDB_PORT=".envValue((string) ($in['db_port'] ?? '3306'))."\nDB_DATABASE=".envValue($in['db_database'])."\nDB_USERNAME=".envValue($in['db_username'])."\nDB_PASSWORD=".envValue($in['db_password'] ?? '');

    return <<<ENV
APP_NAME="SIM Puskesmas Akelamo"
APP_ENV=production
APP_KEY={$key}
APP_DEBUG=false
APP_URL={$url}

APP_LOCALE=id
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=id_ID
APP_TIMEZONE=Asia/Jayapura

APP_MAINTENANCE_DRIVER=file
PHP_CLI_SERVER_WORKERS=4
BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

{$dbLines}

SESSION_DRIVER=database
SESSION_LIFETIME=240
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SAME_SITE=strict
SESSION_SECURE_COOKIE={$secureCookie}
SESSION_HTTP_ONLY=true

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
CACHE_STORE=database

MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@{$mailHost}"
MAIL_FROM_NAME="\${APP_NAME}"

TERMINAL_ENABLED=true

ENV;
}

/* ---------------------------- Alur utama ---------------------------- */

$installed = alreadyInstalled($BASE, $LOCK);
$errors = [];
$old = ['full_name' => '', 'username' => ''];
$success = false;
$migrateLog = '';

if (! $installed && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $in = [
        'driver' => trim($_POST['driver'] ?? 'mysql'),
        'db_host' => trim($_POST['db_host'] ?? 'localhost'),
        'db_port' => (int) ($_POST['db_port'] ?? 3306),
        'db_database' => trim($_POST['db_database'] ?? ''),
        'db_username' => trim($_POST['db_username'] ?? ''),
        'db_password' => (string) ($_POST['db_password'] ?? ''),
        'full_name' => trim($_POST['full_name'] ?? ''),
        'username' => trim($_POST['username'] ?? ''),
        'password' => (string) ($_POST['password'] ?? ''),
        'password_confirmation' => (string) ($_POST['password_confirmation'] ?? ''),
    ];
    $old = ['full_name' => $in['full_name'], 'username' => $in['username']];

    // --- Validasi ---
    if (! in_array($in['driver'], ['mysql', 'sqlite'], true)) {
        $errors['driver'] = 'Driver database tidak valid.';
    }
    if ($in['driver'] === 'mysql') {
        if ($in['db_database'] === '') {
            $errors['db_database'] = 'Nama database wajib diisi.';
        }
        if ($in['db_username'] === '') {
            $errors['db_username'] = 'User database wajib diisi.';
        }
        if ($in['db_port'] < 1 || $in['db_port'] > 65535) {
            $errors['db_port'] = 'Port tidak valid.';
        }
    }
    if ($in['full_name'] === '' || mb_strlen($in['full_name']) > 150) {
        $errors['full_name'] = 'Nama lengkap wajib diisi (maks 150 karakter).';
    }
    if (! preg_match('/^[a-zA-Z0-9_.-]{3,80}$/', $in['username'])) {
        $errors['username'] = 'Username 3–80 karakter, hanya huruf/angka/titik/garis bawah/strip.';
    }
    if (strlen($in['password']) < 12) {
        $errors['password'] = 'Kata sandi minimal 12 karakter.';
    } elseif ($in['password'] !== $in['password_confirmation']) {
        $errors['password'] = 'Konfirmasi kata sandi tidak sama.';
    }

    // --- Uji koneksi database ---
    if ($errors === []) {
        try {
            if ($in['driver'] === 'mysql') {
                new PDO(
                    sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                        $in['db_host'], $in['db_port'], $in['db_database']),
                    $in['db_username'],
                    $in['db_password'],
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 8]
                );
            } else {
                $path = $BASE.'/database/database.sqlite';
                if (! is_file($path)) {
                    file_put_contents($path, '');
                }
                new PDO('sqlite:'.$path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            }
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            $errors['db_database'] = 'Koneksi database gagal: '.mb_substr($msg, 0, 220);
        }
    }

    // --- Tulis .env ---
    if ($errors === []) {
        ensureRuntimeDirs($BASE);

        // APP_KEY: pakai yang ada bila valid, buat baru bila kosong.
        $currentEnv = parseEnvFile($BASE.'/.env');
        $key = $currentEnv['APP_KEY'] ?? '';
        if (! str_starts_with($key, 'base64:') || strlen($key) < 50) {
            $key = 'base64:'.base64_encode(random_bytes(32));
        }

        // APP_URL dari permintaan (http di localhost, https di produksi).
        $scheme = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $in['app_url'] = $scheme.'://'.$host;
        $in['app_key'] = $key;

        $envContent = buildEnv($in);
        if (@file_put_contents($BASE.'/.env', $envContent) === false) {
            $errors['db_host'] = 'Gagal menulis .env — set izin folder aplikasi menjadi dapat ditulis (chmod 775) lalu ulangi.';
        }
    }

    // --- Jalankan migrasi & buat admin via Laravel (mode console, tanpa session HTTP) ---
    if ($errors === []) {
        try {
            // Hapus config cache lama agar .env baru dibaca.
            foreach (['config.php', 'packages.php', 'services.php'] as $f) {
                $p = $BASE.'/bootstrap/cache/'.$f;
                if (is_file($p)) {
                    @unlink($p);
                }
            }

            require $BASE.'/vendor/autoload.php';
            $app = require $BASE.'/bootstrap/app.php';
            $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
            $kernel->bootstrap();

            $output = new Symfony\Component\Console\Output\BufferedOutput();
            Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true], $output);
            $migrateLog = $output->fetch();

            $adminCount = App\Models\User::where('role', 'admin')->count();
            if ($adminCount === 0) {
                if (App\Models\User::where('username', $in['username'])->exists()) {
                    throw new RuntimeException('Username sudah dipakai. Pilih username lain.');
                }
                App\Models\User::create([
                    'full_name' => $in['full_name'],
                    'username' => $in['username'],
                    'password' => $in['password'], // di-hash otomatis (bcrypt cost 12)
                    'role' => 'admin',
                    'is_active' => true,
                ]);
            }

            App\Models\AuditLog::tryCreate([
                'actor_id' => App\Models\User::where('role', 'admin')->value('id'),
                'action' => 'INSTALL',
                'entity' => 'app',
                'record_id' => null,
            ]);

            @mkdir(dirname($LOCK), 0775, true);
            @file_put_contents($LOCK, json_encode([
                'installed_at' => date('c'),
                'url' => $in['app_url'] ?? '',
            ]));

            $success = true;
        } catch (Throwable $e) {
            $errors['driver'] = 'Instalasi gagal: '.mb_substr($e->getMessage(), 0, 400);
            if ($migrateLog !== '') {
                $errors['driver'] .= "\nLog migrasi:\n".mb_substr($migrateLog, 0, 600);
            }
        }
    }
}

/* ---------------------------- Cek kebutuhan server ---------------------------- */

$reqs = [
    ['PHP ≥ 8.3 (disarankan 8.4)', PHP_VERSION_ID >= 80300, PHP_VERSION],
    ['Ekstensi OpenSSL', extension_loaded('openssl'), null, true],
    ['Ekstensi mbstring', extension_loaded('mbstring'), null, true],
    ['Ekstensi PDO', extension_loaded('pdo'), null, true],
    ['Ekstensi pdo_mysql', extension_loaded('pdo_mysql'), null, false],
    ['Ekstensi pdo_sqlite', extension_loaded('pdo_sqlite'), null, false],
    ['Ekstensi tokenizer', extension_loaded('tokenizer'), null, true],
    ['Ekstensi ctype & json', extension_loaded('ctype') && extension_loaded('json'), null, true],
    ['Ekstensi fileinfo', extension_loaded('fileinfo'), null, true],
    ['Folder storage/ dapat ditulis', is_writable($BASE.'/storage') || is_writable($BASE.'/storage/logs'), null, true],
    ['Folder bootstrap/cache dapat ditulis', is_writable($BASE.'/bootstrap/cache'), null, true],
    ['File .env dapat ditulis', is_writable($BASE.'/.env') || (! is_file($BASE.'/.env') && is_writable($BASE)), null, true],
    ['Dependensi vendor/ tersedia', is_file($BASE.'/vendor/autoload.php'), null, true],
];
$pdoOk = extension_loaded('pdo_mysql') || extension_loaded('pdo_sqlite');
$criticalOk = true;
foreach ($reqs as $r) {
    [$label, $ok] = $r;
    $crit = $r[3] ?? true;
    if ($crit && ! $ok) {
        $criticalOk = false;
    }
}
if (! $pdoOk) {
    $criticalOk = false; // butuh minimal satu driver DB
}
$oldEnv = parseEnvFile($BASE.'/.env');

/* ---------------------------- Tampilan ---------------------------- */
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Instalasi · SIM Puskesmas Akelamo</title>
<style>
:root{font-family:Inter,ui-sans-serif,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;color:#162d2c;background:#f5f8f7}
*{box-sizing:border-box}body{margin:0;padding:40px 16px;background:linear-gradient(140deg,#edf6ef,#f7fbf8)}
a{color:#087944}
.wrap{max-width:640px;margin:0 auto}
.logo{display:flex;gap:14px;align-items:center;margin-bottom:26px}
.logo img{width:56px;height:56px;background:#fff;border-radius:14px;padding:6px}
.logo b{font-size:18px;color:#0b3d2a}.logo small{display:block;color:#7d938a;font-size:11px;letter-spacing:1.5px;font-weight:800}
.card{background:#fff;border:1px solid #dcece1;border-radius:18px;padding:28px;box-shadow:0 25px 65px #235e2a12;margin-bottom:18px}
h1{font-size:22px;margin:0 0 6px;color:#0b3d2a}
.sub{color:#7d938a;font-size:13px;margin:0 0 18px;line-height:1.6}
h2{font-size:14px;margin:0 0 12px;color:#0b3d2a}
ul.reqs{list-style:none;padding:0;margin:0}
ul.reqs li{display:flex;justify-content:space-between;gap:10px;padding:9px 12px;border-bottom:1px solid #eef4f0;font-size:13px}
ul.reqs li:last-child{border:0}
.ok{color:#0b7741;font-weight:700}.bad{color:#bf5145;font-weight:700}
.field{margin:14px 0}
.field label{display:block;font-size:11px;color:#657f6e;font-weight:750;margin-bottom:6px}
.field input{width:100%;padding:12px 13px;border:1px solid #dce8df;border-radius:9px;font-size:14px;outline:none}
.field input:focus{border-color:#0b9552;box-shadow:0 0 0 3px #0983481a}
.field .err{color:#b94636;font-size:12px;margin-top:5px}
.field small{color:#8a9b91;font-size:11px}
.btn{width:100%;border:none;background:#087c45;color:#fff;border-radius:10px;padding:15px;font-weight:750;font-size:14px;cursor:pointer}
.btn:hover{background:#065e35}
.note{background:#fff8e8;border:1px solid #f2e2b6;color:#8a6410;border-radius:10px;padding:12px 14px;font-size:12px;line-height:1.6;margin-top:16px}
.done{background:#e9f8ef;border:1px solid #bfe6cf;border-radius:14px;padding:22px;text-align:center}
.done .big{font-size:52px}
pre.log{background:#081510;color:#c9e8d5;padding:14px;border-radius:10px;font-size:11.5px;overflow:auto;max-height:220px;white-space:pre-wrap}
hr.sep{border:0;border-top:1px dashed #dce8df;margin:22px 0}
.foot{text-align:center;color:#9ba9a1;font-size:11px;margin-top:8px}
.pw-wrap{position:relative}.pw-wrap input{padding-right:46px}
.pw-toggle{position:absolute;right:6px;top:50%;transform:translateY(-50%);border:0;background:transparent;color:#7d938a;width:36px;height:36px;border-radius:8px;cursor:pointer;font-size:15px}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
@media(max-width:560px){.grid2{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="wrap">
    <div class="logo">
        <img src="assets/img/logo-puskesmas.png" alt="Logo">
        <div><b>SIM Puskesmas Akelamo</b><small>INSTALASI WEB · CIPTA KARYA DIGITAL</small></div>
    </div>

<?php if ($installed && ! $success): ?>
    <div class="card">
        <h1>Aplikasi sudah terinstall 🔒</h1>
        <p class="sub">Installer terkunci demi keamanan. Bila Anda perlu mengulang instalasi, hapus file
        <code>storage/app/install.lock</code> (dan kosongkan database) lalu muat ulang halaman ini.</p>
        <p class="sub">Setelah yakin semuanya berjalan, <b>hapus file <code>public/install.php</code></b> dari server.</p>
        <a class="btn" style="display:block;text-align:center;text-decoration:none" href="login">Buka Halaman Login →</a>
    </div>

<?php elseif ($success): ?>
    <div class="card">
        <div class="done">
            <div class="big">✅</div>
            <h1>Instalasi Berhasil!</h1>
            <p class="sub">Database telah dimigrasi dan akun administrator dibuat.<br>
            Masuk menggunakan username &amp; kata sandi yang baru Anda buat.</p>
            <a class="btn" style="display:block;text-align:center;text-decoration:none" href="login">Masuk ke Sistem →</a>
        </div>
        <?php if ($migrateLog !== ''): ?><pre class="log"><?= h($migrateLog) ?></pre><?php endif; ?>
        <div class="note">
            <b>Langkah keamanan wajib:</b><br>
            1. Hapus file <code>public/install.php</code> dari server (via File Manager).<br>
            2. Pastikan folder aplikasi kembali ke izin 755 dan file .env ke 640.<br>
            3. Unggah sertifikat SSL aktif — cookie sesi diatur <code>Secure</code> sehingga wajib HTTPS.
        </div>
    </div>

<?php else: ?>
    <div class="card">
        <h1>Instalasi Aplikasi</h1>
        <p class="sub">Isi koneksi database MySQL dari cPanel (menu <b>MySQL® Databases</b> / kredensial yang diberikan hosting)
        beserta akun administrator pertama. Sistem akan membuat seluruh tabel, mengunci installer, dan menyiapkan .env secara otomatis.</p>

        <h2>1 · Pemeriksaan Server</h2>
        <ul class="reqs">
            <?php foreach ($reqs as [$label, $ok, $ver, ]): ?>
                <li><span><?= h(($ver ? $label.' — '.PHP_VERSION : $label)) ?></span><span class="<?= $ok ? 'ok' : 'bad' ?>"><?= $ok ? '✓ OK' : '✗ Tidak ada' ?></span></li>
            <?php endforeach; ?>
            <?php if (! $pdoOk): ?>
                <li><span>Driver database (pdo_mysql / pdo_sqlite)</span><span class="bad">✗ Tidak ada</span></li>
            <?php endif; ?>
        </ul>
        <?php if (! $criticalOk): ?>
            <div class="note">Ada syarat wajib belum terpenuhi — aktifkan lewat cPanel → <b>Select PHP Version → Extensions</b>,
            lalu muat ulang halaman ini.</div>
        <?php endif; ?>
    </div>

    <?php if ($errors !== []): ?>
        <div class="card" style="border-color:#f0c9c2;background:#fff6f4">
            <h2 style="color:#b34636">Perlu Diperbaiki</h2>
            <ul class="reqs">
                <?php foreach ($errors as $msg): ?>
                    <li style="white-space:pre-wrap"><span class="bad">✗</span> <?= h($msg) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form class="card" method="post" autocomplete="off">
        <h2>2 · Koneksi Database</h2>
        <div class="field">
            <label>Jenis Database</label>
            <div class="grid2">
                <label style="font-weight:400;font-size:13px"><input type="radio" name="driver" value="mysql" checked> MySQL / MariaDB (cPanel)</label>
                <label style="font-weight:400;font-size:13px"><input type="radio" name="driver" value="sqlite"> SQLite (tanpa MySQL)</label>
            </div>
        </div>
        <div class="grid2">
            <div class="field"><label>Host Database</label>
                <input name="db_host" value="<?= h($oldEnv['DB_HOST'] ?? 'localhost') ?>" required>
            </div>
            <div class="field"><label>Port</label>
                <input name="db_port" value="<?= h($oldEnv['DB_PORT'] ?? '3306') ?>" inputmode="numeric" required>
            </div>
        </div>
        <div class="field"><label>Nama Database <small>(contoh: toranggo_pkm-akelamo)</small></label>
            <input name="db_database" value="<?= h($oldEnv['DB_DATABASE'] ?? '') ?>" required>
            <?php if (isset($errors['db_database'])): ?><div class="err"><?= h($errors['db_database']) ?></div><?php endif; ?>
        </div>
        <div class="grid2">
            <div class="field"><label>User Database</label>
                <input name="db_username" value="<?= h($oldEnv['DB_USERNAME'] ?? '') ?>" required>
            </div>
            <div class="field"><label>Password Database</label>
                <input name="db_password" type="password" value="">
            </div>
        </div>

        <hr class="sep">
        <h2>3 · Akun Administrator</h2>
        <div class="field"><label>Nama Lengkap</label>
            <input name="full_name" value="<?= h($old['full_name']) ?>" required maxlength="150">
            <?php if (isset($errors['full_name'])): ?><div class="err"><?= h($errors['full_name']) ?></div><?php endif; ?>
        </div>
        <div class="field"><label>Username Admin</label>
            <input name="username" value="<?= h($old['username']) ?>" required maxlength="80">
            <?php if (isset($errors['username'])): ?><div class="err"><?= h($errors['username']) ?></div><?php endif; ?>
        </div>
        <div class="grid2">
            <div class="field"><label>Kata Sandi (min. 12 karakter)</label>
                <div class="pw-wrap"><input id="pw1" name="password" type="password" minlength="12" required>
                <button type="button" class="pw-toggle" onclick="var i=document.getElementById('pw1');i.type=i.type==='password'?'text':'password'">👁</button></div>
                <?php if (isset($errors['password'])): ?><div class="err"><?= h($errors['password']) ?></div><?php endif; ?>
            </div>
            <div class="field"><label>Ulangi Kata Sandi</label>
                <div class="pw-wrap"><input id="pw2" name="password_confirmation" type="password" minlength="12" required>
                <button type="button" class="pw-toggle" onclick="var i=document.getElementById('pw2');i.type=i.type==='password'?'text':'password'">👁</button></div>
            </div>
        </div>

        <button class="btn" type="submit" <?= $criticalOk ? '' : 'disabled' ?>>Mulai Instalasi ⚙</button>
        <div class="note">Pastikan database sudah dibuat kosong di cPanel (tidak perlu tabel apa pun — sistem membuat semuanya).
        Installer akan menonaktifkan diri setelah selesai.</div>
    </form>
<?php endif; ?>

    <div class="foot">© <?= date('Y') ?> Puskesmas Akelamo · Developed by <a href="https://ciptakaryadigital.id" target="_blank" rel="noopener">Cipta Karya Digital</a></div>
</div>
</body>
</html>
