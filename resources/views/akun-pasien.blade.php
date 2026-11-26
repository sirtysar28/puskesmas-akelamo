<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#065d35">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow">
    <title>Portal Pasien · Puskesmas Akelamo</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/logo-puskesmas.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/app.css') }}?v={{ filemtime(public_path('assets/app.css')) }}">
    <script src="{{ asset('assets/app.js') }}?v={{ filemtime(public_path('assets/app.js')) }}" defer></script>
</head>
<body class="portal-body">

<header class="landing-nav portal-nav">
    <div class="landing-container landing-nav-inner">
        <a class="landing-brand" href="{{ route('pasien.home') }}">
            <img src="{{ asset('assets/img/logo-puskesmas.png') }}" alt="Logo Puskesmas Akelamo">
            <div>
                <b>Portal Pasien</b>
                <small>PUSKESMAS AKELAMO</small>
            </div>
        </a>
        <nav class="landing-links">
            <a href="{{ route('pasien.home') }}#profil-saya">Profil Saya</a>
            <a href="{{ route('pasien.home') }}#jadwal-dokter">Jadwal Dokter</a>
            <a href="{{ route('pasien.home') }}#daftar-berobat">Daftar Berobat</a>
            <a href="{{ route('pasien.home') }}#riwayat">Riwayat Antrian</a>
        </nav>
        <div class="landing-nav-actions">
            <span class="portal-user"><span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->full_name, 0, 1)) }}</span>
                <b>{{ auth()->user()->full_name }}</b></span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="landing-btn ghost" type="submit">Keluar</button>
            </form>
        </div>
    </div>
</header>

<main class="portal-main">
    <div class="landing-container">

        @if ($errors->any())
            <div class="form-error-box">
                @foreach ($errors->all() as $err)
                    {{ $err }}<br>
                @endforeach
            </div>
        @endif

        @if ($success)
            <div class="flash-ok">{{ $success }}</div>
        @endif

        {{-- ============ PROFIL SAYA ============ --}}
        <section id="profil-saya">
            <div class="section-head">
                <div>
                    <span class="eyebrow">PORTAL PASIEN</span>
                    <h2>Halo, {{ auth()->user()->full_name }} 👋</h2>
                    <p>Berikut data rekam medis Anda. Tunjukkan nomor RM ini saat datang ke puskesmas.</p>
                </div>
                <span class="tag tag-pulse">Akun Pasien Aktif</span>
            </div>

            @if ($patient)
                <div class="portal-profile">
                    <div class="rm-box">{{ $patient->medical_record_no }}</div>
                    <div class="portal-profile-rows">
                        <div><small>Nama Lengkap</small><b>{{ $patient->full_name }}</b></div>
                        <div><small>NIK</small><b>{{ $patient->nik ?? '–' }}</b></div>
                        <div><small>Jenis Kelamin</small><b>{{ $patient->gender ?? '–' }}</b></div>
                        <div><small>Tanggal Lahir</small><b>{{ $patient->birth_date?->locale('id')->translatedFormat('d F Y') ?? '–' }}</b></div>
                        <div><small>No. HP</small><b>{{ $patient->phone ?? '–' }}</b></div>
                        <div><small>Alamat</small><b>{{ $patient->address ?? '–' }}</b></div>
                    </div>
                </div>
            @else
                <div class="panel" style="padding:23px">
                    <p class="muted">Rekam medis Anda belum tertaut ke akun ini. Silakan hubungi petugas
                        pendaftaran puskesmas untuk aktivasi, atau daftar ulang
                        <a href="{{ url('/pasien') }}" style="color:#087944;font-weight:700">di sini</a>.</p>
                </div>
            @endif
        </section>

        {{-- ============ FORM DAFTAR BEROBAT ============ --}}
        <section id="daftar-berobat">
            <div class="section-head">
                <div>
                    <span class="eyebrow">PENDAFTARAN</span>
                    <h2>Daftar Berobat Online</h2>
                    <p>Antrian Anda akan dikonfirmasi oleh petugas pendaftaran.</p>
                </div>
            </div>
            @if ($patient)
                <div class="panel portal-book">
                    <form method="POST" action="{{ route('pasien.berobat') }}">
                        @csrf
                        <div class="rx-grid">
                            <div class="field">
                                <label for="visit_date">Tanggal Kunjungan *</label>
                                <input type="date" id="visit_date" name="visit_date" required
                                       min="{{ now()->toDateString() }}" max="{{ now()->addYear()->toDateString() }}"
                                       value="{{ old('visit_date') }}">
                            </div>
                            <div class="field">
                                <label for="doctor_id">Dokter Tujuan (opsional)</label>
                                <select id="doctor_id" name="doctor_id">
                                    <option value="">— Poli Umum —</option>
                                    @foreach ($doctors as $d)
                                        <option value="{{ $d->id }}" @selected(old('doctor_id') == $d->id)>
                                            {{ $d->name }} · {{ $d->specialty }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="field" style="margin-top:14px">
                            <label for="complaint">Keluhan (opsional)</label>
                            <textarea id="complaint" name="complaint" maxlength="500"
                                      placeholder="Contoh: demam 3 hari, pusing, batuk berdahak...">{{ old('complaint') }}</textarea>
                        </div>
                        <button class="btn btn-wide" type="submit" style="margin-top:16px">◷ Kirim Pendaftaran Berobat</button>
                    </form>
                </div>
            @endif
        </section>

        {{-- ============ RIWAYAT ANTRIAN ============ --}}
        <section id="riwayat">
            <div class="section-head">
                <div>
                    <span class="eyebrow">RIWAYAT</span>
                    <h2>Antrian &amp; Riwayat Berobat</h2>
                    <p>Status antrian terbaru tampil paling atas.</p>
                </div>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>Tanggal Kunjungan</th><th>Dokter</th><th>Keluhan</th><th>Status</th><th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($appointments as $a)
                        <tr>
                            <td>{{ $a->visit_date->locale('id')->translatedFormat('d M Y') }}</td>
                            <td>{{ $a->doctor?->name ?? 'Poli Umum' }}</td>
                            <td>{{ $a->complaint ?: '–' }}</td>
                            <td>
                                <span class="pill {{ ['Batal' => 'red', 'Menunggu' => 'orange', 'Dikonfirmasi' => ''][$a->status] ?? '' }}">{{ $a->status }}</span>
                            </td>
                            <td>
                                @if ($a->status === 'Menunggu')
                                    <form method="POST" action="{{ route('pasien.cancel', $a->id) }}"
                                          onsubmit="return confirm('Batalkan antrian ini?')">
                                        @csrf
                                        <button class="btn tiny danger" type="submit">Batalkan</button>
                                    </form>
                                @else
                                    <span class="muted">–</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty">Belum ada pendaftaran berobat. Daftarkan diri lewat formulir di atas.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- ============ JADWAL DOKTER ============ --}}
        <section id="jadwal-dokter">
            <div class="section-head">
                <div>
                    <span class="eyebrow">REFERENSI</span>
                    <h2>Jadwal Praktik Dokter</h2>
                    <p>Pilih dokter sesuai keluhan Anda pada formulir pendaftaran.</p>
                </div>
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
        </section>

        <p class="portal-foot">
            © {{ date('Y') }} Puskesmas Akelamo ·
            <a href="{{ url('/') }}">← Beranda</a> ·
            <a href="{{ url('/survey') }}">Isi Survei Kepuasan</a> ·
            Developed by <a href="https://ciptakaryadigital.id" target="_blank" rel="noopener noreferrer">Cipta Karya Digital</a>
        </p>
    </div>
</main>

</body>
</html>
