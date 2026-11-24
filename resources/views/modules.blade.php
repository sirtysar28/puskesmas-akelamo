@extends('layouts.app', ['crumb' => $module, 'pageTitle' => $def['title']])

@section('content')
<div class="section-head anim-up">
    <div>
        <h2>{{ $def['title'] }}</h2>
        <p>Kelola {{ strtolower($def['title']) }} Puskesmas Akelamo secara terpusat.</p>
    </div>
    <span class="tag">{{ number_format(count($rows), 0, ',', '.') }} Data</span>
</div>

<div class="toolbar anim-up" style="--delay:.1s">
    <input id="search" class="search" type="search" placeholder="⌕  Cari data..." aria-label="Cari data" autocomplete="off">
    <div class="actions">
        <button class="btn secondary" id="export" type="button">↓ Ekspor CSV</button>
        @if ($canEdit)
            <button class="btn" id="add" type="button">＋ Tambah Data</button>
        @endif
    </div>
</div>

<div class="table-wrap anim-up" style="--delay:.18s"
     id="crud-root"
     data-module="{{ $module }}"
     data-title="{{ $def['title'] }}"
     data-can-edit="{{ $canEdit ? '1' : '0' }}"
     data-columns="{{ json_encode($def['columns']) }}"
     data-fields="{{ json_encode($def['fields']) }}"
     data-doctors="{{ json_encode($doctors) }}"
     data-rows="{{ json_encode($rows) }}">
    {{-- Tabel dirender oleh app.js agar pencarian instan & ekspor CSV tetap sinkron --}}
    <p class="empty">Memuat data…</p>
</div>

<p class="footer-note">Menampilkan maksimal 500 entri terbaru. Hindari mengekspor data pribadi kecuali benar-benar diperlukan.</p>
@endsection
