<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#065d35">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Puskesmas Akelamo — Sistem Informasi Manajemen Puskesmas. Profil puskesmas, jadwal dokter, registrasi & pendaftaran berobat online.">
    <title>Beranda · Puskesmas Akelamo</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/logo-puskesmas.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/app.css') }}?v={{ filemtime(public_path('assets/app.css')) }}">
    <script src="{{ asset('assets/app.js') }}?v={{ filemtime(public_path('assets/app.js')) }}" defer></script>
</head>
<body class="landing-body">

{{-- ================= NAVBAR ================= --}}
<header class="landing-nav">
    <div class="landing-container landing-nav-inner">
        <a class="landing-brand" href="{{ url('/') }}">
            <img src="{{ asset('assets/img/logo-puskesmas.png') }}" alt="Logo Puskesmas Akelamo">
            <div>
                <b>Puskesmas Akelamo</b>
                <small>SMART HEALTH SYSTEM</small>
            </div>
        </a>
        <nav class="landing-links">
            <a href="#beranda">Beranda</a>
            <a href="#profil">Profil</a>
            <a href="#layanan">Layanan</a>
            <a href="#jadwal">Jadwal</a>
            <a href="#alur">Cara Berobat</a>
            <a href="#kontak">Kontak</a>
        </nav>
        <div class="landing-nav-actions">
            @auth
                <a class="landing-btn ghost" href="{{ auth()->user()->role === 'pasien' ? route('pasien.home') : route('dashboard') }}">
                    {{ auth()->user()->role === 'pasien' ? 'Portal Pasien' : 'Dashboard' }} →
                </a>
            @else
                <a class="landing-btn ghost" href="{{ url('/login') }}">Login</a>
                <a class="landing-btn solid" href="{{ url('/pasien') }}">Registrasi Pasien</a>
            @endauth
        </div>
    </div>
</header>

{{-- ================= HERO ================= --}}
<section class="landing-hero" id="beranda">
    <div class="landing-hero-bg"></div>
    <div class="landing-container landing-hero-inner">
        <div class="landing-hero-text">
            <span class="eyebrow">Selamat Datang di Puskesmas Akelamo</span>
            <h1>Layanan Kesehatan <em>Lebih Dekat</em>,<br>Pendaftaran Berobat <em>Online</em>.</h1>
            <p>Puskesmas Akelamo — unit pelayanan kesehatan tingkat pertama di Kecamatan Jailolo,
               Kabupaten Halmahera Barat, Maluku Utara. Kini dengan sistem informasi terintegrasi:
               data Anda aman, antrian lebih tertata, dan pelayanan lebih cepat.</p>
            <div class="landing-hero-cta">
                <a class="landing-btn solid lg" href="{{ url('/pasien') }}">✚ Daftar &amp; Berobat Online</a>
                <a class="landing-btn ghost lg" href="{{ url('/login') }}">Masuk Akun</a>
            </div>
            <ul class="landing-hero-points">
                <li>🔒 Data pasien terenkripsi</li>
                <li>🕘 Jadwal dokter transparan</li>
                <li>📶 Antrian tanpa antre lama</li>
            </ul>
        </div>
        <div class="landing-hero-card anim-up">
            <div class="landing-hc-head">
                <img src="{{ asset('assets/img/logo-puskesmas.png') }}" alt="">
                <div><b>Pendaftaran Online</b><small>Buka 24 jam via website</small></div>
            </div>
            <div class="landing-hc-stats">
                <div><strong>{{ $doctorCount }}</strong><small>Dokter Praktik</small></div>
                <div><strong>{{ $scheduleCount }}</strong><small>Slot Jadwal</small></div>
                <div><strong>6</strong><small>Poli Layanan</small></div>
            </div>
            <a class="landing-btn solid" href="{{ url('/pasien') }}">Mulai Daftar Sekarang →</a>
            <small class="landing-hc-note">Hari ini {{ mb_strtolower($today) }} — cek jadwal dokter di bawah.</small>
        </div>
    </div>
</section>

{{-- ================= PROFIL PUSKESMAS ================= --}}
<section class="landing-section" id="profil">
    <div class="landing-container">
        <div class="landing-section-head">
            <span class="eyebrow">TENTANG KAMI</span>
            <h2>Profil Puskesmas Akelamo</h2>
            <p>Pelayanan kesehatan berkualitas untuk masyarakat wilayah kerja Akelamo dan sekitarnya.</p>
        </div>
        <div class="landing-profile">
            <div class="landing-profile-card">
                <h3>🗒️ Identitas</h3>
                <dl>
                    <div><dt>Nama</dt><dd>Puskesmas Akelamo</dd></div>
                    <div><dt>Jenis</dt><dd>Puskesmas Non Rawat Inap (Non RI)</dd></div>
                    <div><dt>Alamat</dt><dd>Jl. Raya Akelamo, Kec. Jailolo, Kab. Halmahera Barat, Maluku Utara</dd></div>
                    <div><dt>Pelayanan</dt><dd>Senin – Sabtu, 07.30 – 16.00 WIT</dd></div>
                    <div><dt>Gawat Darurat</dt><dd>Setiap hari via UGD / ambulans puskesmas</dd></div>
                </dl>
            </div>
            <div class="landing-profile-card">
                <h3>🎯 Visi</h3>
                <p>“Menjadi puskesmas pelayanan kesehatan prima yang unggul, mandiri, dan berorientasi
                   pada kepuasan masyarakat wilayah kerja Puskesmas Akelamo.”</p>
                <h3 style="margin-top:18px">🧭 Misi</h3>
                <ol>
                    <li>Memberikan pelayanan kesehatan yang berkualitas dan merata.</li>
                    <li>Meningkatkan derajat kesehatan melalui promosi &amp; pencegahan.</li>
                    <li>Menggandakan masyarakat dan lintas sektor dalam pembangunan kesehatan.</li>
                    <li>Mengembangkan tata kelola puskesmas berbasis sistem informasi digital.</li>
                </ol>
            </div>
        </div>
    </div>
</section>

{{-- ================= LAYANAN ================= --}}
<section class="landing-section alt" id="layanan">
    <div class="landing-container">
        <div class="landing-section-head">
            <span class="eyebrow">LAYANAN KAMI</span>
            <h2>Layanan Unggulan Puskesmas</h2>
            <p>Berbagai poli dan layanan kesehatan untuk Anda dan keluarga.</p>
        </div>
        <div class="landing-services">
            <div class="landing-service"><span>🩺</span><b>Poli Umum</b><small>Pemeriksaan &amp; pengobatan penyakit umum untuk semua usia.</small></div>
            <div class="landing-service"><span>🦷</span><b>Poli Gigi</b><small>Perawatan gigi &amp; mulut, pencabutan, dan scaling.</small></div>
            <div class="landing-service"><span>🤰</span><b>KIA &amp; KB</b><small>Antenatal, imunisasi, puskesmas keliling &amp; konseling KB.</small></div>
            <div class="landing-service"><span>🧪</span><b>Laboratorium</b><small>Pemeriksaan darah, urine, dan pemeriksaan penunjang lain.</small></div>
            <div class="landing-service"><span>💊</span><b>Farmasi</b><small>Layanan obat gratis/terjangkau dengan stok terpantau sistem.</small></div>
            <div class="landing-service"><span>🩹</span><b>Perawatan &amp; Gizi</b><small>Perawatan luka, penyuluhan gizi, dan kunjungan rumah.</small></div>
        </div>
    </div>
</section>

{{-- ================= JADWAL DOKTER ================= --}}
<section class="landing-section" id="jadwal">
    <div class="landing-container">
        <div class="landing-section-head">
            <span class="eyebrow">INFORMASI</span>
            <h2>Jadwal Praktik Dokter</h2>
            <p>Jadwal dapat berubah — silakan konfirmasi melalui petugas pendaftaran.</p>
        </div>
        <div class="schedule-days">
            @forelse ($schedules as $day => $rows)
                <div class="day-card">
                    <div class="day-title">{{ $day }}</div>
                    @foreach ($rows as $s)
                        <div class="day-doc">
                            <b>{{ $s['doctor'] }}</b>
                            <small>{{ $s['specialty'] }} · {{ $s['time'] }} · {{ $s['room'] }}</small>
                        </div>
                    @endforeach
                </div>
            @empty
                <p class="empty">Jadwal belum tersedia.</p>
            @endforelse
        </div>
    </div>
</section>

{{-- ================= ALUR BEROBAT ================= --}}
<section class="landing-section alt" id="alur">
    <div class="landing-container">
        <div class="landing-section-head">
            <span class="eyebrow">SEMUDAH 1-2-3-4</span>
            <h2>Cara Berobat ke Puskesmas Akelamo</h2>
            <p>Belum punya akun? Registrasi sekali, login kapan saja untuk mendaftar berobat.</p>
        </div>
        <div class="landing-steps">
            <div class="landing-step"><span class="step-no">1</span><b>Registrasi Online</b><small>Isi data diri di halaman registrasi — nomor rekam medis (RM) dibuat otomatis.</small></div>
            <div class="landing-step"><span class="step-no">2</span><b>Login Pasien</b><small>Masuk dengan nomor RM &amp; kata sandi Anda lewat tombol Login.</small></div>
            <div class="landing-step"><span class="step-no">3</span><b>Daftar Berobat</b><small>Pilih tanggal kunjungan, dokter tujuan, dan tuliskan keluhan Anda.</small></div>
            <div class="landing-step"><span class="step-no">4</span><b>Datang &amp; Dilayani</b><small>Petugas mengonfirmasi antrian — cukup tunjukkan nomor RM Anda.</small></div>
        </div>
        <div class="landing-cta-band">
            <div>
                <b>Siap berobat tanpa antre lama?</b>
                <small>Daftar sekarang atau masuk ke akun pasien Anda.</small>
            </div>
            <div class="landing-hero-cta">
                <a class="landing-btn solid" href="{{ url('/pasien') }}">Registrasi Pasien</a>
                <a class="landing-btn ghost" href="{{ url('/login') }}">Login Pasien / Staf</a>
            </div>
        </div>
    </div>
</section>

{{-- ================= KONTAK & FOOTER ================= --}}
<footer class="landing-footer" id="kontak">
    <div class="landing-container landing-footer-grid">
        <div>
            <div class="landing-brand">
                <img src="{{ asset('assets/img/logo-puskesmas.png') }}" alt="">
                <div><b>Puskesmas Akelamo</b><small>SMART HEALTH SYSTEM</small></div>
            </div>
            <p class="muted" style="margin-top:14px">
                Jl. Raya Akelamo, Kec. Jailolo,<br>Kab. Halmahera Barat, Maluku Utara<br><br>
                📞 (0921) 000-000 · ✉️ puskesmas.akelamo@dinkes.go.id
            </p>
        </div>
        <div>
            <h4>Menu Publik</h4>
            <a href="{{ url('/pasien') }}">Registrasi Pasien Online</a>
            <a href="{{ url('/login') }}">Login Pasien / Staf</a>
            <a href="{{ url('/survey') }}">Survei Kepuasan Masyarakat</a>
        </div>
        <div>
            <h4>Layanan</h4>
            <a href="#layanan">Poli Umum &amp; Gigi</a>
            <a href="#layanan">KIA, KB &amp; Gizi</a>
            <a href="#layanan">Laboratorium &amp; Farmasi</a>
        </div>
        <div>
            <h4>Jam Pelayanan</h4>
            <a>Senin – Jumat · 07.30 – 16.00 WIT</a>
            <a>Sabtu · 07.30 – 13.00 WIT</a>
            <a>Gawat darurat setiap hari</a>
        </div>
    </div>
    <div class="landing-container landing-footer-bottom">
        <span>© {{ date('Y') }} Puskesmas Akelamo — Sistem Informasi Manajemen Puskesmas.</span>
        <span>Developed by <a href="https://ciptakaryadigital.id" target="_blank" rel="noopener noreferrer">Cipta Karya Digital</a></span>
    </div>
</footer>

</body>
</html>
