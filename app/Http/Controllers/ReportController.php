<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Finance;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Survey;
use App\Support\Reports;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Laporan dengan filter tanggal + export Excel (.xlsx) & PDF.
 * Setiap jenis laporan dibatasi per peran (lihat App\Support\Reports).
 */
class ReportController extends Controller
{
    private const MAX_RANGE_DAYS = 731; // maks 2 tahun

    /** Halaman laporan. */
    public function index(Request $request)
    {
        $types = Reports::allowedFor($request->user()->role);

        abort_if($types === [], 403, 'Anda tidak mempunyai izin untuk menu ini.');

        return view('laporan', [
            'types' => $types,
            'start' => now()->startOfMonth()->toDateString(),
            'end' => now()->toDateString(),
        ]);
    }

    /** Data pratinjau (JSON) — dipakai halaman laporan. */
    public function data(Request $request)
    {
        [$type, $start, $end] = $this->validated($request);

        return response()->json($this->build($type, $start, $end, limit: 200));
    }

    /** Export Excel (.xlsx) via openspout. */
    public function exportExcel(Request $request)
    {
        [$type, $start, $end] = $this->validated($request);
        $report = $this->build($type, $start, $end);
        $this->auditExport($request, 'EXCEL', $type);

        $temp = tempnam(sys_get_temp_dir(), 'akelamo_xlsx_');

        $writer = new \OpenSpout\Writer\XLSX\Writer();
        $writer->openToFile($temp);

        $sheet = $writer->getCurrentSheet();
        $sheet->setName(substr(Reports::types()[$type]['title'], 0, 30));

        $titleStyle = new \OpenSpout\Common\Entity\Style\Style(fontSize: 13, fontBold: true);
        $headStyle = new \OpenSpout\Common\Entity\Style\Style(backgroundColor: 'E9F7EF', fontBold: true);

        $writer->addRow(\OpenSpout\Common\Entity\Row::fromValuesWithStyle(
            ['SIM Puskesmas Akelamo — '.Reports::types()[$type]['title']], $titleStyle
        ));
        $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(
            ['Periode', $start.' s/d '.$end]
        ));
        $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(
            array_map(fn ($s) => $s['label'].': '.$s['value'], $report['summary'])
        ));
        $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([])); // baris kosong

        $writer->addRow(\OpenSpout\Common\Entity\Row::fromValuesWithStyle($report['columns'], $headStyle));
        foreach ($report['rows'] as $row) {
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(
                array_map(fn ($c) => $row[$c] ?? '', $report['columns'])
            ));
        }

        $writer->close();

        $filename = "laporan_{$type}_{$start}_sd_{$end}.xlsx";

        return response()->download($temp, $filename)->deleteFileAfterSend(true);
    }

    /** Export PDF via dompdf (dari view Blade khusus PDF). */
    public function exportPdf(Request $request)
    {
        [$type, $start, $end] = $this->validated($request);
        $report = $this->build($type, $start, $end);
        $this->auditExport($request, 'PDF', $type);

        $html = view('reports.pdf', [
            'title' => Reports::types()[$type]['title'],
            'period' => Carbon::parse($start)->locale('id')->translatedFormat('d F Y').' s/d '.Carbon::parse($end)->locale('id')->translatedFormat('d F Y'),
            'summary' => $report['summary'],
            'columns' => $report['columns'],
            'rows' => $report['rows'],
            'printedAt' => now()->locale('id')->translatedFormat('d F Y H:i'),
            'user' => $request->user()->full_name,
        ])->render();

        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false, 'defaultFont' => 'Helvetica']);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = "laporan_{$type}_{$start}_sd_{$end}.pdf";

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    // ------------------------------------------------------------------

    /** Validasi tipe + rentang tanggal (maks 2 tahun, start ≤ end). */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'report_type' => ['required', 'string', 'max:30'],
            'start' => ['required', 'date', 'before_or_equal:today'],
            'end' => ['required', 'date', 'after_or_equal:start'],
        ], [
            'report_type.required' => 'Pilih jenis laporan.',
            'end.after_or_equal' => 'Tanggal akhir harus ≥ tanggal mulai.',
        ]);

        abort_unless(Reports::canAccess($request->user()->role, $data['report_type']), 403, 'Akses laporan ini dibatasi.');

        $days = Carbon::parse($data['start'])->diffInDays(Carbon::parse($data['end']));
        abort_if($days > self::MAX_RANGE_DAYS, 422, 'Rentang tanggal maksimal 2 tahun.');

        return [$data['report_type'], $data['start'], $data['end']];
    }

    /** Bangun isi laporan (kolom, ringkasan, baris) sesuai tipe. */
    private function build(string $type, string $start, string $end, ?int $limit = null): array
    {
        return match ($type) {
            'keuangan' => $this->financeReport($start, $end, $limit),
            'pasien' => $this->patientReport($start, $end, $limit),
            'obat' => $this->medicineReport($start, $end, $limit),
            'survei' => $this->surveyReport($start, $end, $limit),
            default => abort(404),
        };
    }

    private function financeReport(string $start, string $end, ?int $limit): array
    {
        $q = Finance::whereBetween('transaction_date', [$start, $end])->orderBy('transaction_date')->orderBy('id');
        $rows = ($limit ? $q->limit($limit) : $q)->get();

        $in = $rows->where('type', 'Pemasukan')->sum('amount');
        $out = $rows->where('type', 'Pengeluaran')->sum('amount');

        return [
            'columns' => ['Tanggal', 'Jenis', 'Kategori', 'Keterangan', 'Nominal'],
            'summary' => [
                ['label' => 'Total Pemasukan', 'value' => $this->rupiah($in)],
                ['label' => 'Total Pengeluaran', 'value' => $this->rupiah($out)],
                ['label' => 'Selisih Periode', 'value' => $this->rupiah($in - $out)],
                ['label' => 'Jumlah Transaksi', 'value' => number_format($rows->count(), 0, ',', '.')],
            ],
            'rows' => $rows->map(fn ($f) => [
                'Tanggal' => $f->transaction_date->format('d/m/Y'),
                'Jenis' => $f->type,
                'Kategori' => $f->category,
                'Keterangan' => $f->description ?: '–',
                'Nominal' => $this->rupiah($f->amount),
            ])->all(),
        ];
    }

    private function patientReport(string $start, string $end, ?int $limit): array
    {
        $q = Patient::whereBetween('created_at', [$start.' 00:00:00', $end.' 23:59:59'])->orderBy('id');
        $rows = ($limit ? $q->limit($limit) : $q)->get();

        return [
            'columns' => ['No. RM', 'Nama', 'Gender', 'Tanggal Lahir', 'Terdaftar'],
            'summary' => [
                ['label' => 'Pasien Baru', 'value' => number_format($rows->count(), 0, ',', '.')],
                ['label' => 'Laki-laki', 'value' => number_format($rows->where('gender', 'Laki-laki')->count(), 0, ',', '.')],
                ['label' => 'Perempuan', 'value' => number_format($rows->where('gender', 'Perempuan')->count(), 0, ',', '.')],
                ['label' => 'Rata-rata Usia', 'value' => $this->avgAge($rows)],
            ],
            'rows' => $rows->map(fn ($p) => [
                'No. RM' => $p->medical_record_no,
                'Nama' => $p->full_name,
                'Gender' => $p->gender ?: '–',
                'Tanggal Lahir' => $p->birth_date?->format('d/m/Y') ?: '–',
                'Terdaftar' => $p->created_at->format('d/m/Y'),
            ])->all(),
        ];
    }

    private function medicineReport(string $start, string $end, ?int $limit): array
    {
        $q = Medicine::whereBetween('expiry_date', [$start, $end])->orderBy('expiry_date');
        $rows = ($limit ? $q->limit($limit) : $q)->get();

        $low = Medicine::whereColumn('stock', '<=', 'min_stock')->count();

        return [
            'columns' => ['Kode', 'Nama Obat', 'Satuan', 'Stok', 'Minimum', 'Kedaluwarsa', 'Status'],
            'summary' => [
                ['label' => 'Obat Kedaluwarsa di Periode Ini', 'value' => number_format($rows->count(), 0, ',', '.')],
                ['label' => 'Total Stok Terkait', 'value' => number_format($rows->sum('stock'), 0, ',', '.')],
                ['label' => 'Stok Menipis (saat ini)', 'value' => number_format($low, 0, ',', '.')],
                ['label' => 'Perlu Re-evaluasi', 'value' => number_format($rows->filter(fn ($m) => $m->stock <= $m->min_stock)->count(), 0, ',', '.')],
            ],
            'rows' => $rows->map(fn ($m) => [
                'Kode' => $m->code,
                'Nama Obat' => $m->name,
                'Satuan' => $m->unit,
                'Stok' => number_format($m->stock, 0, ',', '.'),
                'Minimum' => number_format($m->min_stock, 0, ',', '.'),
                'Kedaluwarsa' => $m->expiry_date->format('d/m/Y'),
                'Status' => $m->expiry_date->isPast() ? 'Sudah Kedaluwarsa' : ($m->stock <= $m->min_stock ? 'Stok Menipis' : 'Aman'),
            ])->all(),
        ];
    }

    private function surveyReport(string $start, string $end, ?int $limit): array
    {
        $q = Survey::whereBetween('created_at', [$start.' 00:00:00', $end.' 23:59:59'])->orderByDesc('id');
        $rows = ($limit ? $q->limit($limit) : $q)->get();

        $dist = collect(range(1, 5))->mapWithKeys(fn ($r) => [$r => $rows->where('rating', $r)->count()]);

        return [
            'columns' => ['Tanggal', 'Layanan', 'Rating', 'Saran'],
            'summary' => [
                ['label' => 'Total Responden', 'value' => number_format($rows->count(), 0, ',', '.')],
                ['label' => 'Rata-rata', 'value' => ($rows->count() ? number_format(round($rows->avg('rating'), 2), 2, ',', '.') : '0').'/5'],
                ['label' => 'Puas (4–5)', 'value' => number_format($dist[4] + $dist[5], 0, ',', '.')],
                ['label' => 'Dengan Saran', 'value' => number_format($rows->whereNotNull('suggestion')->count(), 0, ',', '.')],
            ],
            'rows' => $rows->map(fn ($s) => [
                'Tanggal' => $s->created_at->format('d/m/Y H:i'),
                'Layanan' => $s->service,
                'Rating' => $s->rating.'/5',
                'Saran' => $s->suggestion ?: '–',
            ])->all(),
        ];
    }

    private function avgAge($rows): string
    {
        $ages = $rows->filter(fn ($p) => $p->birth_date)->map(fn ($p) => $p->birth_date->age);
        if ($ages->isEmpty()) {
            return '–';
        }

        return number_format(round($ages->avg(), 1), 1, ',', '.').' thn';
    }

    private function rupiah(float $n): string
    {
        return 'Rp '.number_format($n, 0, ',', '.');
    }

    private function auditExport(Request $request, string $fmt, string $type): void
    {
        AuditLog::tryCreate([
            'actor_id' => $request->user()->id,
            'action' => 'EXPORT_'.$fmt,
            'entity' => 'reports/'.$type,
            'record_id' => null,
        ]);
    }
}
