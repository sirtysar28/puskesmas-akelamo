@extends('layouts.app', ['crumb' => 'surveys', 'pageTitle' => 'Survei Kepuasan'])

@section('content')
<div class="section-head anim-up">
    <div>
        <h2>Survei Kepuasan Masyarakat</h2>
        <p>Bagikan QR Code agar pengunjung dapat menilai layanan secara anonim.</p>
    </div>
    <span class="tag">Publik &amp; Tanpa Login</span>
</div>

<div class="stats stats-two anim-up" style="--delay:.1s">
    <div class="stat stat-animated">
        <div class="icon-tile">☆</div>
        <small>Total Responden</small>
        <strong class="count-up" data-count="{{ $stats['total'] }}" data-format="number">0</strong>
        <em>Survei diterima</em>
    </div>
    <div class="stat stat-animated" style="--delay:.12s">
        <div class="icon-tile">★</div>
        <small>Rata-rata Kepuasan</small>
        <strong class="count-up" data-count="{{ round((float) $stats['average'], 2) }}" data-format="rating">0</strong>
        <em>Skala penilaian 1 sampai 5</em>
    </div>
</div>

<section class="panel anim-up" style="--delay:.2s">
    <h3>📱 QR Code Survei Online</h3>
    <div class="qr-panel">
        <img src="{{ route('surveys.qr') }}" alt="QR Code untuk mengisi survei kepuasan Puskesmas Akelamo">
        <div class="qr-side">
            <p class="muted">Pasang QR ini di ruang tunggu atau loket. Masyarakat dapat memindainya menggunakan kamera ponsel tanpa perlu akun.</p>
            <div class="qr-link">{{ $link }}</div>
            <button class="btn secondary" id="copy-link" type="button" data-link="{{ $link }}">Salin Tautan</button>
            <a class="btn" href="{{ route('surveys.qr') }}" target="_blank" rel="noopener">Buka QR</a>
        </div>
    </div>
</section>

<div class="section-head anim-up" style="--delay:.28s"><h2>Umpan Balik Terbaru</h2></div>

<div class="table-wrap anim-up" style="--delay:.34s" id="surveys-table"
     data-rows="{{ json_encode($items->map(fn ($s) => [
         'id' => $s->id,
         'created_at' => $s->created_at?->locale('id')->translatedFormat('d M Y H:i'),
         'service' => $s->service,
         'rating' => $s->rating,
         'suggestion' => $s->suggestion,
     ])) }}">
    <table class="data-table">
        <thead>
        <tr>
            <th>Tanggal</th><th>Jenis Layanan</th><th>Rating</th><th>Kritik &amp; Saran</th><th>Aksi</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($items as $s)
            <tr>
                <td>{{ $s->created_at?->locale('id')->translatedFormat('d M Y H:i') }}</td>
                <td>{{ $s->service }}</td>
                <td><span class="pill">{{ $s->rating }} / 5 ★</span></td>
                <td>{{ $s->suggestion ?: '–' }}</td>
                <td><button class="btn tiny danger" data-survey-delete="{{ $s->id }}" type="button">Hapus</button></td>
            </tr>
        @empty
            <tr><td colspan="5" class="empty">Belum ada penilaian.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
