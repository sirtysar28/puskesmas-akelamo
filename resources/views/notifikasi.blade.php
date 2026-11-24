@extends('layouts.app', ['crumb' => 'notifikasi', 'pageTitle' => 'Notifikasi'])

@section('content')
<div class="section-head anim-up">
    <div>
        <h2>Notifikasi Sistem</h2>
        <p>{{ $unread > 0 ? $unread.' belum dibaca' : 'Semua sudah dibaca' }} — peristiwa transaksi, registrasi pasien, dan resep.</p>
    </div>
    <button class="btn secondary" type="button" id="mark-read-btn" {{ $unread === 0 ? 'disabled' : '' }}>✓ Tandai Semua Dibaca</button>
</div>

<div class="panel anim-up" style="--delay:.08s">
    @forelse ($list as $n)
        <a class="notif-row {{ $n->read_at ? '' : 'unread' }}" href="{{ $n->url ?: '#' }}">
            <span class="notif-icon">{{ \App\Models\AppNotification::iconFor($n->type) }}</span>
            <span>
                <b>{{ $n->title }}</b>
                @if ($n->body)<small>{{ $n->body }}</small>@endif
            </span>
            <time>{{ $n->created_at->locale('id')->diffForHumans() }}</time>
        </a>
    @empty
        <p class="empty">Belum ada notifikasi. Transaksi keuangan, registrasi pasien baru, dan resep akan muncul di sini.</p>
    @endforelse
</div>
@endsection
