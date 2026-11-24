@extends('layouts.app', ['crumb' => 'laporan', 'pageTitle' => 'Laporan'])

@section('content')
<div class="section-head anim-up">
    <div>
        <h2>Laporan &amp; Ekspor Data</h2>
        <p>Pilih jenis laporan dan rentang tanggal, lalu unduh dalam format Excel atau PDF.</p>
    </div>
    <span class="tag">📊 Filter Tanggal · Excel &amp; PDF</span>
</div>

<form class="report-filters anim-up" style="--delay:.08s" id="report-form">
    <div class="field">
        <label for="report_type">Jenis Laporan</label>
        <select id="report_type" name="report_type" required>
            @foreach ($types as $key => $t)
                <option value="{{ $key }}" @selected($loop->first)>{{ $t['title'] }}</option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label for="start">Tanggal Mulai</label>
        <input type="date" id="start" name="start" value="{{ $start }}" max="{{ now()->toDateString() }}" required>
    </div>
    <div class="field">
        <label for="end">Tanggal Akhir</label>
        <input type="date" id="end" name="end" value="{{ $end }}" max="{{ now()->toDateString() }}" required>
    </div>
    <div class="report-actions">
        <button class="btn" type="submit">🔎 Tampilkan</button>
        <a class="btn secondary btn-export" data-href="{{ url('/laporan/export/excel') }}" href="#">↓ Excel (.xlsx)</a>
        <a class="btn secondary btn-export" data-href="{{ url('/laporan/export/pdf') }}" href="#">🧾 PDF</a>
    </div>
</form>

<div id="report-output" class="anim-up" style="--delay:.15s">
    <p class="empty">Memuat laporan…</p>
</div>

<p class="footer-note">Setiap ekspor tercatat dalam log audit (jenis, format, dan pengunduh). Ekspor hanya data pada rentang tanggal terpilih.</p>
@endsection
