@extends('layouts.app', ['crumb' => 'pengaturan', 'pageTitle' => 'Pengaturan SMTP'])

@section('content')
<div class="section-head anim-up">
    <div>
        <h2>Pengaturan SMTP — Notifikasi Email</h2>
        <p>Email HTML otomatis terkirim ke staf saat ada transaksi keuangan, registrasi pasien baru, dan resep.</p>
    </div>
    <span class="tag {{ $enabled ? 'tag-pulse' : '' }}">{{ $enabled ? '● SMTP Aktif' : '○ SMTP Nonaktif' }}</span>
</div>

@if ($status)<div class="flash-ok">{{ $status }}</div>@endif
@if ($testSent)<div class="flash-ok">{{ $testSent }}</div>@endif

<div class="panels">
    <section class="panel anim-up">
        <h3>✉ Konfigurasi Server SMTP</h3>
        <form method="POST" action="{{ url('/pengaturan/smtp') }}" class="panel-form">
            @csrf
            <label class="check-row">
                <input type="checkbox" name="smtp_enabled" value="1" @checked($values['smtp_enabled'])>
                <b>Aktifkan notifikasi email</b>
                <small class="muted">— jika nonaktif, notifikasi hanya tampil di lonceng aplikasi</small>
            </label>

            <div class="rx-grid">
                <div class="field">
                    <label for="smtp_host">SMTP Host</label>
                    <input type="text" id="smtp_host" name="smtp_host" value="{{ old('smtp_host', $values['smtp_host']) }}" placeholder="mail.domain.ac.id" maxlength="190">
                </div>
                <div class="field rx-qty">
                    <label for="smtp_port">Port</label>
                    <input type="number" id="smtp_port" name="smtp_port" value="{{ old('smtp_port', $values['smtp_port']) }}" min="1" max="65535" placeholder="587">
                </div>
                <div class="field">
                    <label for="smtp_encryption">Enkripsi</label>
                    <select id="smtp_encryption" name="smtp_encryption">
                        @foreach (['tls' => 'TLS (587)', 'ssl' => 'SSL (465)', 'none' => 'Tanpa Enkripsi'] as $v => $lbl)
                            <option value="{{ $v }}" @selected(old('smtp_encryption', $values['smtp_encryption']) === $v)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="rx-grid">
                <div class="field">
                    <label for="smtp_username">Username SMTP</label>
                    <input type="text" id="smtp_username" name="smtp_username" value="{{ old('smtp_username', $values['smtp_username']) }}" maxlength="190" autocomplete="off">
                </div>
                <div class="field">
                    <label for="smtp_password">Sandi SMTP {{ $values['smtp_has_password'] ? '(tersimpan — kosongkan agar tidak berubah)' : '' }}</label>
                    <input type="password" id="smtp_password" name="smtp_password" maxlength="500" autocomplete="new-password" placeholder="{{ $values['smtp_has_password'] ? '••••••••' : 'sandi SMTP' }}">
                </div>
            </div>

            <div class="rx-grid">
                <div class="field">
                    <label for="smtp_from_email">Alamat Pengirim (From)</label>
                    <input type="email" id="smtp_from_email" name="smtp_from_email" value="{{ old('smtp_from_email', $values['smtp_from_email']) }}" placeholder="noreply@domain.ac.id" maxlength="150">
                </div>
                <div class="field">
                    <label for="smtp_from_name">Nama Pengirim</label>
                    <input type="text" id="smtp_from_name" name="smtp_from_name" value="{{ old('smtp_from_name', $values['smtp_from_name']) }}" maxlength="120">
                </div>
            </div>

            <button class="btn" type="submit">💾 Simpan Pengaturan</button>
        </form>
    </section>

    <section class="panel anim-up" style="--delay:.12s">
        <h3>🧪 Kirim Email Uji</h3>
        <p class="muted">Simpan konfigurasi dulu, lalu kirim email uji untuk memastikan semuanya berjalan. Format email yang diterima staf sama dengan contoh ini.</p>
        <form method="POST" action="{{ url('/pengaturan/smtp/test') }}" class="panel-form">
            @csrf
            <div class="field">
                <label for="test_email">Tujuan Email Uji</label>
                <input type="email" id="test_email" name="test_email" required maxlength="150" placeholder="nama@email.com">
            </div>
            <button class="btn" type="submit">📨 Kirim Email Uji</button>
        </form>

        <h3 style="margin-top:24px">ℹ Penerima Notifikasi</h3>
        <p class="muted">Email dikirim ke staf sesuai peran peristiwa (transaksi → admin &amp; keuangan; pasien/resep → admin &amp; petugas). Pastikan setiap staf mengisi <b>email</b> di halaman <a href="{{ url('/profil') }}">Profil</a>.</p>
        <p class="footer-note" style="margin:0">🔒 Sandi SMTP disimpan terenkripsi AES-256 di database (tabel <code>settings</code>), tidak pernah dikirim ulang ke browser.</p>
    </section>
</div>
@endsection
