@extends('layouts.app', ['crumb' => 'resep', 'pageTitle' => 'Resep & Obat'])

@section('content')
<div class="section-head anim-up">
    <div>
        <h2>Resep &amp; Obat</h2>
        <p>@if ($canWrite)Tulis resep untuk pasien — farmasi menyerahkan obat &amp; stok berkurang otomatis.@else Pilih pasien, lihat resep dari dokter, lalu tandai penyerahan obat.@endif</p>
    </div>
    <span class="tag">℞ {{ $list->where('status','aktif')->count() }} resep menunggu</span>
</div>

@if ($canWrite)
<section class="panel anim-up">
    <h3>✎ Tulis Resep Baru</h3>
    <form id="rx-form" data-endpoint="{{ url('/resep') }}">
        @csrf
        <div class="rx-grid">
            <div class="field">
                <label for="rx-patient">Pasien</label>
                <select id="rx-patient" name="patient_id" required>
                    <option value="">— pilih pasien —</option>
                    @foreach ($patients as $p)
                        <option value="{{ $p->id }}">{{ $p->medical_record_no }} · {{ $p->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="rx-notes">Catatan Klinis (opsional, terenkripsi)</label>
                <input type="text" id="rx-notes" name="notes" maxlength="500" placeholder="mis. tekanan darah 140/90, kontrol 2 minggu">
            </div>
        </div>

        <div class="rx-items" id="rx-items">
            <div class="rx-item">
                <div class="field">
                    <label>Obat</label>
                    <select name="items[0][medicine_id]" required>
                        <option value="">— pilih obat —</option>
                        @foreach ($medicines as $m)
                            <option value="{{ $m->id }}" data-stock="{{ $m->stock }}">{{ $m->code }} · {{ $m->name }} (stok {{ $m->stock }} {{ $m->unit }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Aturan Pakai</label>
                    <input type="text" name="items[0][dose]" maxlength="140" placeholder="3x1 sesudah makan" required>
                </div>
                <div class="field rx-qty">
                    <label>Jml</label>
                    <input type="number" name="items[0][quantity]" min="1" max="1000" value="1" required>
                </div>
                <button type="button" class="rx-del" title="Hapus baris">✕</button>
            </div>
        </div>

        <div class="report-actions">
            <button class="btn secondary" type="button" id="rx-add">＋ Tambah Obat</button>
            <button class="btn" type="submit">💾 Simpan Resep</button>
        </div>
    </form>
</section>
@endif

<section class="panel anim-up" style="--delay:.1s">
    <div class="chart-head">
        <h3>📋 Daftar Resep</h3>
        <form method="GET" action="{{ url('/resep') }}" class="rx-filter">
            <select name="pasien" onchange="this.form.submit()">
                <option value="0">Semua pasien</option>
                @foreach ($patients as $p)
                    <option value="{{ $p->id }}" @selected($filterPatient === $p->id)>{{ $p->medical_record_no }} · {{ $p->full_name }}</option>
                @endforeach
            </select>
        </form>
    </div>

    @forelse ($list as $rx)
        <details class="rx-card {{ $rx->status === 'diserahkan' ? 'rx-done' : 'rx-active' }}">
            <summary>
                <span class="rx-status {{ $rx->status }}">{{ $rx->status === 'aktif' ? '⏳ menunggu' : '✓ diserahkan' }}</span>
                <b>#{{ $rx->id }} · {{ $rx->patient?->full_name }}</b>
                <small class="muted">{{ $rx->patient?->medical_record_no }} · {{ $rx->items->count() }} obat · {{ $rx->created_at->locale('id')->translatedFormat('d M Y H:i') }}</small>
                <span class="rx-doctor">✍ {{ $rx->creator?->full_name ?? '-' }}{{ $rx->doctor && $rx->doctor->specialty ? ' · '.$rx->doctor->specialty : '' }}</span>
            </summary>
            <div class="rx-body">
                <div class="table-wrap">
                    <table class="data-table">
                        <thead><tr><th>Obat</th><th>Aturan Pakai</th><th>Jumlah</th></tr></thead>
                        <tbody>
                        @foreach ($rx->items as $item)
                            <tr>
                                <td><b>{{ $item->medicine?->name }}</b> <small class="muted">{{ $item->medicine?->unit }}</small></td>
                                <td>{{ $item->dose }}</td>
                                <td>{{ $item->quantity }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($rx->notes)<p class="muted">📝 {{ $rx->notes }}</p>@endif
                @if ($canDispense && $rx->status === 'aktif')
                    <form class="rx-dispense" data-endpoint="{{ url('/resep/'.$rx->id.'/serahkan') }}">
                        @csrf
                        <button class="btn" type="submit">✓ Tandai Diserahkan &amp; Kurangi Stok</button>
                        <small class="muted">Stok obat berkurang otomatis sesuai jumlah resep.</small>
                    </form>
                @endif
            </div>
        </details>
    @empty
        <p class="empty">Belum ada resep. @if ($canWrite)Tulis resep pertama lewat formulir di atas.@endif</p>
    @endforelse
</section>
@endsection
