# SIM Puskesmas Akelamo — Laravel 13 + PHP 8.4

Sistem Informasi Manajemen **Puskesmas Akelamo** yang dibangun ulang di atas **Laravel terbaru (13.x)** dengan **PHP 8.4+**, dengan fitur, proteksi keamanan berlapis, dan antarmuka beranimasi.

Dikembangkan oleh **[Cipta Karya Digital](https://ciptakaryadigital.id)**.

---

## ✨ Fitur

| Modul | Keterangan |
|---|---|
| **Dashboard beranimasi** | Hero gradien bergerak, kartu statistik *count-up* yang **bisa diklik ke modul datanya**, **bar chart** animasi (arus kas & pasien per bulan), **diagram venn** animasi (kelayakan stok obat / kelengkapan data / kepuasan) |
| **Login** | Panel branding di **sebelah kiri**, captcha **5 karakter**, tombol **intip password** 👁 di **semua** input password (login, buat akun, reset sandi) |
| **Data Pasien** | CRUD + **enkripsi** telepon, alamat, dan catatan medis (AES-256-GCM) |
| **Data Obat** | CRUD + penanda stok menipis (≤ stok minimum) |
| **Keuangan** | CRUD pemasukan/pengeluaran + saldo otomatis |
| **Data Pegawai & Dokter** | CRUD + NIP/SIP unik |
| **Jadwal Praktik** | CRUD terkait dokter; dokter hanya bisa melihat |
| **Survei Kepuasan** | QR Code (server-side SVG) + halaman survei publik anonim + rekap statistik |
| **Laporan & Ekspor** | 4 jenis laporan (keuangan/pasien/obat/survei) dengan filter tanggal → **Excel .xlsx** (openspout) & **PDF** (dompdf), pratinjau langsung, dibatasi per peran, tercatat di audit log |
| **Resep & Obat** ℞ | Integrasi dokter↔pasien↔farmasi: dokter menulis resep (pasien + obat + dosis), petugas/admin menyerahkan obat — **stok berkurang otomatis** dalam transaksi DB |
| **Area Pasien Publik** | `/pasien` tanpa login: jadwal praktik dokter + registrasi mandiri dengan captcha → **No. RM dibuat otomatis sistem** (format `RM-YYMM-NNNN`) |
| **No. RM Otomatis** | Nomor rekam medis dibuat sistem per bulan berjalan — bukan input manual, tidak bisa duplikat |
| **Notifikasi & Email** | 🔔 Lonceng notifikasi di topbar (transaksi keuangan, registrasi pasien, resep) + **pengaturan SMTP admin** dengan email HTML ber-branding & tombol kirim uji |
| **Profil & Ganti Sandi** | `/profil`: perbarui nama/email, ganti sandi (verifikasi sandi lama, peek 👁, aturan kuat) |
| **Log Aktivitas** | `/log-aktivitas` (admin): jejak audit semua aksi penting — login, CRUD, ekspor, ganti sandi, penyerahan resep, terminal |
| **Pengguna & Akses** | RBAC 4 peran: `admin`, `dokter`, `petugas`, `keuangan`; reset sandi memutus semua sesi target |
| **Terminal Super Admin** | Konsol artisan di dashboard (khusus `admin`): migrasi database & clear cache tanpa SSH — whitelist ketat, konfirmasi sandi untuk perintah destruktif |
| **Audit Log** | Semua LOGIN/LOGOUT/CREATE/UPDATE/DELETE/RESET_PASSWORD tercatat |
| **Ekspor CSV** | Per modul, langsung dari browser (BOM UTF-8) |
| **Footer** | **Developed by [Cipta Karya Digital](https://ciptakaryadigital.id)** di semua halaman |

## 🛡️ Keamanan berlapis (hardening)

| Vektor Serangan | Proteksi |
|---|---|
| **SQL Injection** | 100% Eloquent/Query Builder (prepared statements); nama tabel/kolom TIDAK PERNAH dari input — semua lewat *whitelist* `App\Support\ModuleRegistry` |
| **XSS** | Semua output di-escape Blade `{{ }}` + `esc()` JS; **CSP ketat** `script-src 'self'` (tanpa inline script), `object-src 'none'` |
| **CSRF** | Laravel `VerifyCsrfToken` pada semua form + header `X-CSRF-TOKEN` untuk fetch |
| **Clickjacking** | `X-Frame-Options: DENY` + `frame-ancestors 'none'` |
| **Brute Force Login** | Rate limit **5x/menit + 20x/jam per IP** + **captcha 5 karakter** (SVG, sekali pakai, hash SHA-256 di session, perbandingan `hash_equals`) |
| **User Enumeration** | Hash pembanding tetap dieksekusi walau username tidak ditemukan + pesan error generik + jitter waktu acak |
| **Session Fixation** | `session()->regenerate()` saat login, invalidate + regenerate token saat logout |
| **Session Hijacking** | Cookie `HttpOnly`, `SameSite=Strict`, `Secure` saat produksi (HTTPS), driver database |
| **Password Cracking** | bcrypt cost **12**, minimal **12 karakter**, tidak ada kredensial default di repo |
| **Spam Survei** | Rate limit 8x/jam per IP + honeypot tersembunyi |
| **Command Injection (terminal)** | Terminal web BUKAN shell — hanya `Artisan::call()` + whitelist perintah/flag; `rm -rf`, `tinker`, dll. ditolak; perintah destruktif wajib konfirmasi sandi; rate limit 12x/menit; audit log; bisa dimatikan via `TERMINAL_ENABLED=false` |
| **Mass Assignment** | `$fillable` eksplisit di semua model + validasi ketat whitelist |
| **Data Pasien Sensitif** | Enkripsi aplikasi (AES-256-GCM via `APP_KEY`) untuk telepon/alamat/catatan medis |
| **MIME Sniffing / Referrer Leak** | `X-Content-Type-Options: nosniff`, `Referrer-Policy: no-referrer`, `Permissions-Policy` |
| **Transport** | HSTS `max-age=31536000` otomatis saat `APP_ENV=production` |

> Headers diatur terpusat di `App\Http\Middleware\SecurityHeaders`.

## 🚀 Instalasi

```bash
# 1. Dependensi
composer install

# 2. Konfigurasi
cp .env.example .env
php artisan key:generate

# 3. Database (SQLite default; ubah ke pgsql di .env untuk produksi)
touch database/database.sqlite
php artisan migrate --force

# 4. Buat akun administrator PERTAMA (tidak ada akun default!)
ADMIN_USERNAME=adminakelamo \
ADMIN_PASSWORD='PasswordKuat12Plus!' \
ADMIN_NAME='Administrator' \
php artisan akelamo:init-admin

# 5. (Opsional, hanya dev) Data contoh: dokter, obat, pasien, keuangan, survei
php artisan db:seed --class=DemoSeeder

# 6. Jalankan
php artisan serve
```

Buka `http://localhost:8000`. Halaman survei publik: `http://localhost:8000/survey`.

> QR Code dibuat dari `APP_URL`. Saat produksi, set `APP_URL=https://domain-puskesmas.id` agar QR bisa dipindai pengunjung dari HP.

## 🖥️ Instalasi cPanel TANPA Terminal/SSH

Paket siap-upload: **`puskesmas-akelamo-cpanel.zip`** (sudah berisi `vendor/`, web installer, dan `.env` awal).
Error umum `Table 'xxx.sessions' doesn't exist` = **migrasi belum dijalankan** — selesaikan dengan Metode 1 atau 2 berikut.

### Langkah awal (kedua metode)
1. **cPanel → Select PHP Version** → pilih **PHP 8.4**, aktifkan ekstensi: `pdo_mysql, openssl, mbstring, tokenizer, fileinfo, ctype, xml, gd`.
2. **cPanel → MySQL® Databases** → buat database + user, hubungkan (ALL PRIVILEGES). Contoh: `toranggo_pkm-akelamo`.
3. Upload & ekstrak `puskesmas-akelamo-cpanel.zip` ke folder hosting (mis. `/home/user/puskesmas-akelamo` — **bukan** di `public_html`).
4. Arahkan document root domain ke **`puskesmas-akelamo/public`** (menu *Domains* / *Addon Domains*). Bila docroot tak bisa diubah: pindahkan isi `public/` ke `public_html`, salin `index.php` & `.htaccess` ke `public_html`, lalu edit `public_html/index.php` — ubah `../vendor/autoload.php` dan `../bootstrap/app.php` menjadi `../puskesmas-akelamo/vendor/autoload.php` dan `../puskesmas-akelamo/bootstrap/app.php`.

### Metode 1 — Web Installer (disarankan)
1. Buka `https://domain-anda.id/install.php` (installer berdiri sendiri, tidak terpengaruh error tabel yang belum ada).
2. Cek hasil *Pemeriksaan Server* → isi koneksi MySQL + akun administrator pertama (sandi min. 12 karakter).
3. Klik **Mulai Instalasi** → sistem otomatis: menulis `.env` lengkap (APP_KEY acak, APP_URL otomatis), menjalankan seluruh migrasi, membuat akun admin, lalu **mengunci installer**.
4. Masuk via `/login`, lalu **hapus `public/install.php`** dari server.

### Metode 2 — Import SQL via phpMyAdmin
1. **phpMyAdmin** → pilih database → tab **Import** → unggah `database/schema-mysql.sql` → Go.
2. Isi `.env` secara manual (lihat contoh di bawah).
3. Login: `adminakelamo` / `AkelamoR4hasia#2026` → **SEGERA ganti sandi** (menu Pengguna & Akses → Reset Sandi).

```env
# .env minimal untuk cPanel (Metode 2)
APP_NAME="SIM Puskesmas Akelamo"
APP_ENV=production
APP_KEY=base64:...   # hasil `php artisan key:generate` di komputer lokal
APP_DEBUG=false
APP_URL=https://domain-anda.id
APP_LOCALE=id
APP_TIMEZONE=Asia/Jayapura
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=toranggo_pkm-akelamo
DB_USERNAME=toranggo_user
DB_PASSWORD=****
SESSION_DRIVER=database
SESSION_LIFETIME=240
SESSION_SAME_SITE=strict
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
CACHE_STORE=database
QUEUE_CONNECTION=sync
LOG_CHANNEL=stack
LOG_LEVEL=error
MAIL_MAILER=log
TERMINAL_ENABLED=true
```

> Khusus Metode 2 tanpa akses CLI: `APP_KEY` bisa dibuat di komputer lokal mana pun yang punya PHP —
> `php -r "echo 'base64:'.base64_encode(random_bytes(32));"` — lalu tempel di `.env`.

### Setelah instalasi
- Pasang SSL (cPanel → SSL/TLS Status → AutoSSL). Cookie sesi `Secure` aktif bila diakses via HTTPS.
- Migrasi & cache selanjutnya cukup lewat menu **Terminal Super Admin** di dashboard (tanpa SSH).
- Kunci installer sudah otomatis; hapus `install.php` untuk keamanan maksimal.

## 🧑‍💻 Hak Akses (RBAC)

| Modul | Admin | Dokter | Petugas | Keuangan |
|---|---|---|---|---|
| Dashboard ringkasan sesuai izin | ✔ | ✔ | ✔ | ✔ |
| Data Pasien | CRUD | CRUD | CRUD | ✖ |
| Data Obat | CRUD | ✖ | CRUD | ✖ |
| Keuangan | CRUD | ✖ | ✖ | CRUD |
| Pegawai | CRUD | ✖ | ✖ | ✖ |
| Dokter | CRUD | ✖ | CRUD | ✖ |
| Jadwal Praktik | CRUD | Baca | CRUD | ✖ |
| Survei + QR | ✔ | ✖ | ✔ | ✖ |
| Pengguna & Akses | ✔ | ✖ | ✖ | ✖ |

## ⌨ Terminal Super Admin

Menu **Terminal** (sidebar, hanya peran `admin`) menyediakan konsol artisan untuk operasional harian tanpa SSH:

- **Migrasi**: `migrate`, `migrate:status`, `migrate:rollback`, `migrate:fresh` (+`--seed`, `--force`)
- **Cache**: `cache:clear`, `config:clear/cache`, `route:clear/cache`, `view:clear`, `event:clear`, `optimize`, `optimize:clear`

Keamanan terminal:
- Bukan shell — semua lewat `Artisan::call()`, whitelist nama perintah & flag, tanpa argumen posisi
- `migrate:rollback` / `migrate:fresh` **wajib konfirmasi kata sandi akun** (sudo mode) + tombol intip sandi 👁
- Rate limit 12 eksekusi/menit + setiap eksekusi tercatat di audit log (`action: TERMINAL`)
- **Anti lock-out**: setelah migrasi destruktif, terminal otomatis (1) menjalankan ulang migrasi sistem (users/sessions/cache/jobs) bila ikut ter-drop, dan (2) memulihkan akun admin yang sedang login — admin hanya perlu login ulang
- Nonaktifkan seluruh terminal di produksi ketat: `TERMINAL_ENABLED=false`

## 🗂️ Struktur Penting

```
app/
├── Console/Commands/InitAdmin.php      # CLI pembuat admin pertama
├── Http/
│   ├── Controllers/
│   │   ├── Auth/LoginController.php    # login + captcha + anti-enumeration
│   │   ├── CaptchaController.php       # SVG captcha 5 karakter
│   │   ├── DashboardController.php     # statistik per peran
│   │   ├── ModuleController.php        # CRUD generik via registry
│   │   ├── SurveyController.php        # publik + staf + QR
│   │   ├── TerminalController.php      # konsol artisan whitelist (super admin)
│   │   └── UserController.php          # manajemen akun (admin)
│   └── Middleware/
│       ├── EnsureRole.php              # RBAC
│       └── SecurityHeaders.php         # CSP & header keamanan
└── Support/
    ├── Captcha.php                     # generator + verifikasi captcha
    └── ModuleRegistry.php              # whitelist modul kolom/rule/role

resources/views/                        # Blade (login, dashboard, modul, survei, pengguna)
public/assets/                          # app.css + app.js (vanilla, kompatibel CSP)
database/migrations/                    # skema users..audit_logs
```

## ⚙️ Produksi (checklist)

1. `APP_ENV=production`, `APP_DEBUG=false` (wajib — jangan bocorkan stack trace)
2. HTTPS + set `SESSION_SECURE_COOKIE=true` (HSTS otomatis aktif)
3. Ganti DB ke PostgreSQL: isi blok `DB_CONNECTION=pgsql` di `.env`
4. Simpan `APP_KEY` & backup database di secret manager/terenkripsi
5. `php artisan config:cache && php artisan route:cache`
6. Jadwalkan pembersihan sesi kadaluarsa: `php artisan schedule:work` (default Laravel)
7. Backup database teruji + rotasi kredensial berkala
8. Nama & NIK pasien belum terenkripsi di level aplikasi — gunakan enkripsi volume/pembatasan akses DB untuk produksi penuh

## 🧪 Testing

```bash
php artisan test   # 6 test: redirect login, render captcha/footer, captcha wajib, RBAC, terminal
```

## 🎭 Data Dummy & Akun Demo

```bash
php artisan db:seed --class=DemoSeeder
```

Menghasilkan: **40 pasien** (No. RM otomatis `RM-YYMM-NNNN`, tersebar 12 bulan utk grafik tren), **85 transaksi keuangan** (8 bulan, tren naik), **45 survei**, **15 obat** (sebagian stok menipis/kedaluwarsa utk venn), **6 dokter + 12 jadwal**, **12 pegawai**, **12 resep** (4 sudah diserahkan → stok obat konsisten), **18+ notifikasi lonceng**, plus akun demo berikut (HANYA utk lingkungan demo — hapus di produksi):

| Username | Sandi | Peran |
|---|---|---|
| `dokterakelamo` | `DokterAkelamo#2026` | dokter |
| `petugasakelamo` | `PetugasAkelamo#2026` | petugas |
| `keuanganakelamo` | `KeuanganAkelamo#2026` | keuangan |

## 📊 Grafik Dashboard

- **Bar chart animasi** — batang tumbuh dengan *stagger delay* + label nilai muncul: *Arus Kas 6 Bulan* (pemasukan vs pengeluaran, admin/keuangan) & *Pasien Baru per Bulan* (admin/petugas/dokter)
- **Venn diagram animasi** — lingkaran *pop* masuk + angka *count-up*, konten menyesuaikan peran: admin/petugas → kelayakan stok obat; keuangan → kepuasan & partisipasi survei; dokter → kelengkapan data pasien (NIK ∩ kontak HP)
- **Kartu statistik interaktif** — hover lift + indikator "Lihat detail →", klik menuju modul terkait (hanya bila peran punya akses)

## 📑 Laporan (Excel & PDF)

Menu **Laporan** (peran: admin, keuangan, petugas, dokter — tipe dibatasi per peran):

| Jenis | Isi | Peran |
|---|---|---|
| Laporan Keuangan | Pemasukan/pengeluaran/selisih + detail transaksi | admin, keuangan |
| Laporan Registrasi Pasien | Pasien baru, gender, rata-rata usia + detail | admin, petugas, dokter |
| Laporan Stok & Kedaluwarsa Obat | Obat kedaluwarsa pada periode + status stok | admin, petugas |
| Laporan Survei Kepuasan | Distribusi rating, rata-rata, saran | admin, petugas |

Fitur: filter tanggal (maks. 2 tahun), pratinjau AJAX (200 baris pertama), **export Excel .xlsx** & **PDF** berisi seluruh data periode, nama file otomatis `laporan_{tipe}_{mulai}_sd_{akhir}`, tiap ekspor tercatat di audit log.

---

© Puskesmas Akelamo · **Developed by [Cipta Karya Digital](https://ciptakaryadigital.id)**
# puskesmas-akelamo
