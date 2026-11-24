<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#076e3b">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow">
    <title>Layanan Pasien | Puskesmas Akelamo</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/logo-puskesmas.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/app.css') }}">
    <script src="{{ asset('assets/app.js') }}" defer></script>
</head>
<body class="survey-body">
<div class="survey-shell survey-shell-wide">
    <div class="survey-box anim-up">
        <div class="survey-brand">
            <img src="{{ asset('assets/img/logo-puskesmas.png') }}" alt="Logo Puskesmas Akelamo">
            <div>
                <strong>Puskesmas Akelamo</strong>
                <small>Layanan Pasien Online</small>
            </div>
        </div>

        @if ($errors->any())
            <div class="form-error-box">
                @foreach ($errors->all() as $err)
                    {{ $err }}<br>
                @endforeach
            </div>
        @endif

        @if ($success)
            <div class="regis-success">
                <div class="regis-check">✓</div>
                <h2>Registrasi Berhasil!</h2>
                <p>Terima kasih, <b>{{ $successName }}</b>. Nomor rekam medis Anda:</p>
                <div class="rm-box">{{ $success }}</div>
                <p class="muted">Simpan nomor ini &amp; tunjukkan di loket pendaftaran saat berobat. Data Anda terlindungi dan terenkripsi.</p>
                <a class="btn" href="{{ url('/pasien') }}">Daftarkan Pasien Lain</a>
            </div>
        @else
            <div id="pt-area">
                <h1>Jadwal Praktik Dokter</h1>
                <p>Pilih jadwal dokter sesuai kebutuhan, lalu daftarkan diri Anda sebagai pasien — nomor rekam medis dibuat otomatis oleh sistem.</p>

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

                <h1 style="margin-top:30px">Registrasi Pasien Baru</h1>
                <p>Isi data dengan benar. Nomor <b>Rekam Medis (RM)</b> akan dibuat otomatis setelah simpan.</p>

                <form method="POST" action="{{ url('/pasien/daftar') }}">
                    @csrf
                    <div class="rx-grid">
                        <div class="field">
                            <label for="full_name">Nama Lengkap *</label>
                            <input type="text" id="full_name" name="full_name" value="{{ old('full_name') }}" required maxlength="160" placeholder="Nama sesuai KTP">
                        </div>
                        <div class="field">
                            <label for="nik">NIK (16 digit)</label>
                            <input type="text" id="nik" name="nik" value="{{ old('nik') }}" maxlength="16" pattern="\d{16}" placeholder="16 digit angka (opsional)">
                        </div>
                    </div>
                    <div class="rx-grid">
                        <div class="field">
                            <label for="gender">Jenis Kelamin *</label>
                            <select id="gender" name="gender" required>
                                <option value="Laki-laki" @selected(old('gender') === 'Laki-laki')>Laki-laki</option>
                                <option value="Perempuan" @selected(old('gender') === 'Perempuan')>Perempuan</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="birth_date">Tanggal Lahir *</label>
                            <input type="date" id="birth_date" name="birth_date" value="{{ old('birth_date') }}" required max="{{ now()->subDay()->toDateString() }}">
                        </div>
                    </div>
                    <div class="rx-grid">
                        <div class="field">
                            <label for="phone">No. HP</label>
                            <input type="text" id="phone" name="phone" value="{{ old('phone') }}" maxlength="30" placeholder="08xx-xxxx-xxxx (opsional)">
                        </div>
                        <div class="field">
                            <label for="address">Alamat</label>
                            <input type="text" id="address" name="address" value="{{ old('address') }}" maxlength="500" placeholder="Alamat domisili (opsional)">
                        </div>
                    </div>
                    <div class="field">
                        <label for="captcha">Kode Keamanan *</label>
                        <div class="captcha-row">
                            <img id="captcha-img" src="{{ url('/captcha') }}" alt="Kode captcha" width="150" height="46">
                            <button class="captcha-refresh" type="button" id="captcha-refresh" title="Ganti kode">↻</button>
                            <input type="text" id="captcha" name="captcha" required maxlength="8" autocomplete="off" placeholder="5 karakter">
                        </div>
                    </div>
                    <button class="btn btn-wide" type="submit">✓ Daftar &amp; Dapatkan Nomor RM</button>
                </form>
            </div>
        @endif

        <div class="survey-foot">
            <a href="{{ url('/login') }}">Login Staf</a> ·
            <a href="{{ url('/survey') }}">Isi Survei Kepuasan</a>
        </div>
    </div>
    <div class="survey-credit">© {{ date('Y') }} Puskesmas Akelamo · Developed by <a href="https://ciptakaryadigital.id" target="_blank" rel="noopener noreferrer">Cipta Karya Digital</a></div>
</div>
</body>
</html>
