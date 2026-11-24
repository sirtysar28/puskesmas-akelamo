@extends('layouts.app', ['crumb' => 'profil', 'pageTitle' => 'Profil Saya'])

@section('content')
<div class="section-head anim-up">
    <div>
        <h2>Profil Saya</h2>
        <p>Perbarui data akun, email notifikasi, dan ganti sandi secara berkala.</p>
    </div>
    <span class="tag">👤 {{ strtoupper($user->role) }}</span>
</div>

@if ($status)
    <div class="flash-ok">{{ $status }}</div>
@endif
@if (! empty($errors))
    <div class="form-error-box">{{ implode(' ', $errors) }}</div>
@endif

<div class="panels">
    <section class="panel anim-up">
        <h3>📇 Informasi Akun</h3>
        <table class="kv">
            <tr><td>Username</td><td><b>{{ $user->username }}</b></td></tr>
            <tr><td>Nama Lengkap</td><td>{{ $user->full_name }}</td></tr>
            <tr><td>Peran</td><td><span class="tag">{{ $user->role }}</span></td></tr>
            <tr><td>Email Notifikasi</td><td>@if ($user->email)<b>{{ $user->email }}</b>@else<span class="muted">belum diisi — tanpa email, notifikasi hanya tampil di aplikasi</span>@endif</td></tr>
            <tr><td>Terdaftar</td><td>{{ $user->created_at->locale('id')->translatedFormat('d F Y') }}</td></tr>
        </table>

        <form method="POST" action="{{ url('/profil') }}" class="panel-form">
            @csrf
            <h3>✎ Perbarui Profil</h3>
            <div class="field">
                <label for="full_name">Nama Lengkap</label>
                <input type="text" id="full_name" name="full_name" value="{{ old('full_name', $user->full_name) }}" required maxlength="150">
            </div>
            <div class="field">
                <label for="email">Email Notifikasi (opsional)</label>
                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" maxlength="150" placeholder="nama@email.com">
                <small>Email dipakai mengirim notifikasi transaksi &amp; aktivitas bila SMTP diaktifkan admin.</small>
            </div>
            <button class="btn" type="submit">Simpan Profil</button>
        </form>
    </section>

    <section class="panel anim-up" style="--delay:.12s">
        <h3>🔐 Ganti Sandi</h3>
        <p class="muted">Minimal 10 karakter, mengandung huruf &amp; angka. Sandi lama wajib untuk konfirmasi.</p>
        <form method="POST" action="{{ url('/profil/sandi') }}" class="panel-form">
            @csrf
            <div class="field">
                <label for="current_password">Sandi Lama</label>
                <input type="password" id="current_password" name="current_password" required maxlength="200" autocomplete="current-password">
            </div>
            <div class="field">
                <label for="password">Sandi Baru</label>
                <input type="password" id="password" name="password" required minlength="10" maxlength="200" autocomplete="new-password">
            </div>
            <div class="field">
                <label for="password_confirmation">Ulangi Sandi Baru</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required maxlength="200" autocomplete="new-password">
            </div>
            <button class="btn" type="submit">Ganti Sandi</button>
        </form>

        <h3 style="margin-top:24px">🕘 Aktivitas Saya Terakhir</h3>
        @forelse ($recent as $log)
            <div class="schedule-row">
                <b>{{ $log->action }}</b>
                <div class="muted">{{ $log->entity }}{{ $log->record_id ? ' #'.$log->record_id : '' }} · {{ $log->created_at->locale('id')->translatedFormat('d M Y H:i') }}</div>
            </div>
        @empty
            <p class="muted">Belum ada aktivitas tercatat.</p>
        @endforelse
    </section>
</div>

<p class="footer-note">🔒 Sandi disimpan sebagai hash bcrypt (cost 12) — tidak ada yang dapat membaca sandi Anda, termasuk administrator.</p>
@endsection
