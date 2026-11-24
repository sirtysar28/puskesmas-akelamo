@extends('layouts.app', ['crumb' => 'log', 'pageTitle' => 'Log Aktivitas'])

@section('content')
<div class="section-head anim-up">
    <div>
        <h2>Log Aktivitas Aplikasi</h2>
        <p>Semua aksi penting tercatat: login, CRUD data, ekspor, ganti sandi, terminal, penyerahan resep, dll.</p>
    </div>
    <span class="tag">☰ Audit Trail</span>
</div>

<section class="panel anim-up" style="--delay:.06s">
    <form method="GET" action="{{ url('/log-aktivitas') }}" class="chart-head">
        <div class="field" style="max-width:340px;margin:0">
            <input type="text" name="q" value="{{ $search }}" placeholder="Cari aksi / entitas / username…" maxlength="60">
        </div>
        <button class="btn secondary" type="submit">🔎 Cari</button>
        <a class="btn secondary" href="{{ url('/log-aktivitas') }}">Reset</a>
    </form>

    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Waktu</th><th>Pelaku</th><th>Aksi</th><th>Entitas</th><th>Referensi</th></tr></thead>
            <tbody>
            @forelse ($rows as $log)
                <tr>
                    <td>{{ $log->created_at->locale('id')->translatedFormat('d M Y, H:i') }}</td>
                    <td><b>{{ $log->actor?->full_name ?? 'Publik/Anonim' }}</b> <small class="muted">{{ $log->actor?->role }}</small></td>
                    <td><span class="tag {{ str_starts_with($log->action, 'DELETE') ? 'tag-danger' : '' }}">{{ $log->action }}</span></td>
                    <td>{{ $log->entity }}</td>
                    <td>{{ $log->record_id !== null ? '#'.$log->record_id : '–' }}</td>
                </tr>
            @empty
                <tr><td class="empty" colspan="5">Tidak ada aktivitas{{ $search ? ' untuk pencarian "'.$search.'"' : '' }}.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $rows->links() }}
</section>
@endsection
