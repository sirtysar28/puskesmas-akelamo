<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#065d35">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk · SIM Puskesmas Akelamo</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/logo-puskesmas.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/app.css') }}?v={{ filemtime(public_path('assets/app.css')) }}">
    <script src="{{ asset('assets/app.js') }}?v={{ filemtime(public_path('assets/app.js')) }}" defer></script>
</head>
<body class="login-body">
<div class="login-page">

    {{-- ============ PANEL KIRI: branding animasi ============ --}}
    <section class="login-art">
        <div class="art-blob b1"></div>
        <div class="art-blob b2"></div>
        <div class="art-ring"></div>
        <img class="login-logo" src="{{ asset('assets/img/logo-puskesmas.png') }}" alt="Logo Puskesmas Akelamo">
        <h1>Pelayanan kesehatan yang lebih cerdas &amp; terintegrasi.</h1>
        <p>Satu sistem untuk mengelola pasien, obat, SDM, jadwal praktik, keuangan, dan kepuasan masyarakat secara aman.</p>
        <ul class="art-points">
            <li>🔒 Data pasien terenkripsi</li>
            <li>🛡️ Proteksi CSRF, XSS &amp; SQL injection</li>
            <li>📊 Dashboard animasi real-time</li>
        </ul>
        <div class="art-caption">SISTEM INFORMASI MANAJEMEN · PUSKESMAS AKELAMO</div>
    </section>

    {{-- ============ PANEL KANAN: form login ============ --}}
    <section class="login-form-wrap">
        <form class="login-form" method="POST" action="{{ route('login.attempt') }}" id="login-form" autocomplete="on">
            @csrf
            <span class="kicker">SELAMAT DATANG</span>
            <h2>Masuk ke Akun Anda</h2>
            <p>Untuk <b>pasien</b>: masuk dengan username RM &amp; kata sandi Anda.<br>Untuk <b>staf</b> (dokter, perawat, petugas, kepala puskesmas): gunakan akun dari administrator.</p>

            @if ($errors->any())
                <div class="form-error-box" role="alert">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <div class="field">
                <label for="username">Username</label>
                <input id="username" name="username" type="text" autocomplete="username" required
                       placeholder="Pasien: username RM · Staf: akun kantor" maxlength="80" value="{{ old('username') }}">
            </div>

            <div class="field">
                <label for="password">Kata Sandi</label>
                <div class="pw-wrap">
                    <input id="password" name="password" type="password" autocomplete="current-password" required
                           placeholder="Masukkan kata sandi" maxlength="256">
                    <button type="button" class="pw-toggle" data-toggle-password="password"
                            aria-label="Intip kata sandi" aria-pressed="false" title="Intip kata sandi">
                        <svg class="eye-open" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="eye-closed" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                </div>
            </div>

            <div class="field">
                <label for="captcha">Kode Keamanan (5 karakter)</label>
                <div class="captcha-row">
                    <input id="captcha" name="captcha" type="text" inputmode="latin" autocomplete="off" spellcheck="false"
                           required placeholder="5 karakter" maxlength="5" pattern="[A-Za-z0-9]{5}">
                    <img src="{{ route('captcha') }}" alt="Kode captcha" id="captcha-img" width="190" height="64">
                    <button type="button" class="captcha-refresh" id="captcha-refresh" title="Ganti kode" aria-label="Ganti kode captcha">⟳</button>
                </div>
            </div>

            <button class="btn btn-login" type="submit">Masuk Sekarang →</button>

            <p class="login-foot">🔒 Belum terdaftar sebagai pasien? <a href="{{ url('/pasien') }}" style="color:#087944;font-weight:750">Registrasi online di sini</a>.</p>
        </form>

        <footer class="login-footer">
            <a href="{{ url('/') }}" style="color:#087944;font-weight:700">← Kembali ke Beranda</a><br>
            © {{ date('Y') }} Puskesmas Akelamo · Developed by
            <a href="https://ciptakaryadigital.id" target="_blank" rel="noopener noreferrer">Cipta Karya Digital</a>
        </footer>
    </section>
</div>
</body>
</html>
