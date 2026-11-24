@extends('layouts.app', ['crumb' => 'users', 'pageTitle' => 'Pengguna & Akses'])

@section('content')
<div class="section-head anim-up">
    <div>
        <h2>Pengguna &amp; Hak Akses</h2>
        <p>Admin dapat membuat akun untuk Dokter, Petugas, dan Keuangan.</p>
    </div>
    <button class="btn" id="add-user" type="button">＋ Akun Baru</button>
</div>

<div class="table-wrap anim-up" style="--delay:.12s"
     id="users-table"
     data-rows="{{ json_encode($users->map(fn ($u) => [
         'id' => $u->id,
         'full_name' => $u->full_name,
         'username' => $u->username,
         'role' => $u->role,
         'is_active' => $u->is_active,
     ])) }}">
    <table class="data-table">
        <thead>
        <tr><th>Nama</th><th>Username</th><th>Hak Akses</th><th>Status</th><th>Aksi</th></tr>
        </thead>
        <tbody>
        @foreach ($users as $u)
            <tr>
                <td>{{ $u->full_name }}</td>
                <td>{{ $u->username }}</td>
                <td><span class="pill">{{ $u->role }}</span></td>
                <td><span class="pill {{ $u->is_active ? '' : 'red' }}">{{ $u->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                <td>
                    <button class="btn tiny secondary" data-reset="{{ $u->id }}" type="button">Reset Sandi</button>
                    @if ($u->id !== auth()->id())
                        <button class="btn tiny {{ $u->is_active ? 'danger' : 'secondary' }}" data-active="{{ $u->id }}"
                                data-status="{{ $u->is_active ? '1' : '0' }}" type="button">
                            {{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                        </button>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<p class="footer-note">Untuk keamanan, penghapusan pengguna tidak tersedia. Akun dapat dinonaktifkan dan seluruh sesi aktifnya akan diputus.</p>
@endsection
