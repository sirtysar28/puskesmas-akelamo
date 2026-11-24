@extends('layouts.app', ['crumb' => 'dashboard', 'pageTitle' => 'Dashboard'])

@section('content')
<div class="hero hero-animated">
    <div class="hero-inner">
        <span class="eyebrow">SELAMAT DATANG DI SIM AKELAMO</span>
        <h1>Pelayanan Kesehatan<br>Lebih Mudah &amp; Terintegrasi</h1>
        <p>Kelola layanan pasien, tenaga kesehatan, obat, keuangan, dan survei masyarakat dalam satu sistem informasi terpadu.</p>
    </div>
    <div class="hero-badge">☀ {{ now()->locale('id')->translatedFormat('l, j F Y') }}</div>
</div>

<div class="section-head anim-up">
    <div>
        <h2>Ringkasan Operasional</h2>
        <p>Informasi sesuai hak akses akun {{ auth()->user()->role }} — arahkan kursor pada kartu, klik untuk membuka detail.</p>
    </div>
    <span class="tag tag-pulse">● Terhubung ke Database</span>
</div>

<div class="stats">
    @forelse ($tiles as $i => $tile)
        @if ($tile['link'])
            <a class="stat stat-animated stat-link" href="{{ $tile['link'] }}" style="--delay: {{ $i * 0.09 }}s">
        @else
            <div class="stat stat-animated" style="--delay: {{ $i * 0.09 }}s">
        @endif
            <div class="icon-tile">{{ $tile['icon'] }}</div>
            <small>{{ $tile['title'] }}</small>
            <strong class="count-up"
                    data-count="{{ $tile['value'] }}"
                    data-format="{{ $tile['format'] }}">0</strong>
            <em>{{ $tile['sub'] }}</em>
            @if ($tile['link'])<span class="stat-go">Lihat detail →</span>@endif
        @if ($tile['link'])</a>@else</div>@endif
    @empty
        <div class="stat"><small>Tidak ada statistik untuk peran ini.</small></div>
    @endforelse
</div>

{{-- ===================== GRAFIK ===================== --}}
<div class="chart-grid">
    @if (isset($barChart['finance']))
        <section class="panel chart-panel anim-up" style="--delay:.2s">
            <div class="chart-head">
                <h3>📊 {{ $barChart['finance']['title'] }}</h3>
                <div class="chart-legend">
                    <span><i class="dot dot-in"></i>{{ $barChart['finance']['inLabel'] }}</span>
                    <span><i class="dot dot-out"></i>{{ $barChart['finance']['outLabel'] }}</span>
                </div>
            </div>
            <div class="bar-chart bar-chart-dual"
                 data-labels="{{ json_encode($barChart['finance']['labels']) }}"
                 data-series='{!! json_encode([$barChart['finance']['in'], $barChart['finance']['out']]) !!}'
                 data-format="currency"></div>
        </section>
    @endif

    @if (isset($barChart['patients']))
        <section class="panel chart-panel anim-up" style="--delay:.25s">
            <div class="chart-head"><h3>📊 {{ $barChart['patients']['title'] }}</h3></div>
            <div class="bar-chart"
                 data-labels="{{ json_encode($barChart['patients']['labels']) }}"
                 data-series='{!! json_encode([$barChart['patients']['values']]) !!}'
                 data-format="number"></div>
        </section>
    @endif

    @if ($venn)
        <section class="panel chart-panel anim-up" style="--delay:.3s">
            <div class="chart-head"><h3>⭕ {{ $venn['title'] }}</h3></div>
            <div class="venn-wrap">
                <svg viewBox="0 0 320 200" class="venn" role="img" aria-label="{{ $venn['title'] }}">
                    <circle class="venn-circle venn-a" cx="120" cy="100" r="78"/>
                    <circle class="venn-circle venn-b" cx="200" cy="100" r="78"/>
                    <text class="venn-num" x="80" y="92" text-anchor="middle" data-count="{{ $venn['a'] - $venn['both'] }}">0</text>
                    <text class="venn-num venn-num-both" x="160" y="92" text-anchor="middle" data-count="{{ $venn['both'] }}">0</text>
                    <text class="venn-num" x="240" y="92" text-anchor="middle" data-count="{{ $venn['b'] - $venn['both'] }}">0</text>
                    <text class="venn-cap" x="52" y="128" text-anchor="middle">{{ $venn['aLabel'] }}</text>
                    <text class="venn-cap venn-cap-both" x="160" y="120" text-anchor="middle">Keduanya</text>
                    <text class="venn-cap" x="272" y="128" text-anchor="middle">{{ $venn['bLabel'] }}</text>
                </svg>
            </div>
            <p class="muted venn-caption">{{ $venn['caption'] }}</p>
        </section>
    @endif
</div>

<div class="panels">
    <section class="panel anim-up" style="--delay:.35s">
        <h3>⚡ Akses Cepat</h3>
        <div class="quick-links">
            @foreach ($quickLinks as $key => $item)
                <a class="quick" href="{{ \App\Support\ModuleRegistry::urlFor($key) }}">
                    {{ $item[0] }} &nbsp;{{ $item[1] }} &nbsp;→
                </a>
            @endforeach
        </div>
    </section>

    <section class="panel anim-up" style="--delay:.45s">
        <h3>📅 Jadwal Praktik Terbaru</h3>
        @forelse ($schedules as $s)
            <div class="schedule-row">
                <b>{{ $s['doctor'] }}</b>
                <div class="muted">{{ $s['day'] }} · {{ $s['time_start'] }}–{{ $s['time_end'] }} · {{ $s['room'] }}</div>
            </div>
        @empty
            <p class="muted">Jadwal tidak tersedia pada akses ini atau belum diisi.</p>
        @endforelse
    </section>
</div>

<p class="footer-note">🔒 Akses berbasis peran. Data tersimpan di server dan tidak disimpan di localStorage perangkat.</p>
@endsection
