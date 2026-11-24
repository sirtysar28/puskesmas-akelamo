<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>{{ $title }}</title>
<style>
    body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #1d2b23; margin: 34px 42px; }
    h1 { font-size: 16px; color: #065d35; margin: 0 0 3px; }
    .sub { color: #5d7267; font-size: 10px; margin: 0 0 14px; }
    .period { font-size: 11px; font-weight: bold; margin: 0 0 12px; }
    table.summary { border-collapse: collapse; margin: 0 0 16px; }
    table.summary td { border: 1px solid #cfe4d6; padding: 6px 12px; font-size: 10px; }
    table.summary td.k { background: #e9f7ef; color: #0b6e41; font-weight: bold; }
    table.data { border-collapse: collapse; width: 100%; }
    table.data th { background: #065d35; color: #fff; font-size: 9px; text-transform: uppercase; padding: 7px 8px; text-align: left; }
    table.data td { border-bottom: 1px solid #dfe9e2; padding: 6px 8px; font-size: 9.5px; }
    table.data tr:nth-child(even) td { background: #f6faf7; }
    .footer { position: fixed; bottom: 22px; left: 42px; right: 42px; border-top: 1px solid #cfe4d6; padding-top: 6px;
              font-size: 8.5px; color: #7d938a; display: block; width: 100%; }
    .empty { color: #7d938a; font-style: italic; padding: 18px 0; }
</style>
</head>
<body>
    <h1>SIM Puskesmas Akelamo — {{ $title }}</h1>
    <p class="sub">Sistem Informasi Manajemen Puskesmas Akelamo · Developed by Cipta Karya Digital</p>
    <p class="period">Periode: {{ $period }}</p>

    <table class="summary">
        @foreach ($summary as $s)
            <tr><td class="k">{{ $s['label'] }}</td><td>{{ $s['value'] }}</td></tr>
        @endforeach
    </table>

    <table class="data">
        <thead><tr>@foreach ($columns as $c)<th>{{ $c }}</th>@endforeach</tr></thead>
        <tbody>
        @forelse ($rows as $row)
            <tr>@foreach ($columns as $c)<td>{{ $row[$c] ?? '–' }}</td>@endforeach</tr>
        @empty
            <tr><td class="empty" colspan="{{ count($columns) }}">Tidak ada data pada rentang tanggal ini.</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dicetak: {{ $printedAt }} · oleh: {{ $user }} · Halaman 1 — dokumen ini dihasilkan otomatis dan tercatat di log audit.
    </div>
</body>
</html>
