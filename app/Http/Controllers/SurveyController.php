<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class SurveyController extends Controller
{
    /** ===================== HALAMAN PUBLIK (tanpa login) ===================== */

    public function publicForm(): View
    {
        return view('survey-public', ['services' => Survey::SERVICES]);
    }

    /**
     * Submit survei publik.
     * Proteksi: throttle (8x/jam per IP), validasi ketat, honeypot bot,
     * tidak ada data pribadi yang diminta.
     */
    public function submit(Request $request)
    {
        $data = $request->validate([
            'service' => ['required', 'in:'.implode(',', Survey::SERVICES)],
            'rating' => ['required', 'integer', 'between:1,5'],
            'suggestion' => ['nullable', 'string', 'max:1000'],
            'website' => ['nullable', 'string', 'max:200'], // honeypot
        ]);

        // Honeypot: bot biasanya mengisi semua field tersembunyi — anggap sukses diam-diam.
        if (! empty($data['website'] ?? '')) {
            return response()->json(['ok' => true], 201);
        }

        Survey::create([
            'service' => $data['service'],
            'rating' => $data['rating'],
            'suggestion' => $data['suggestion'] ?? null,
        ]);

        return response()->json(['ok' => true], 201);
    }

    /** ===================== PANEL STAF (login + role) ===================== */

    public function index(Request $request): View
    {
        $items = Survey::latest('id')->limit(500)->get();
        $stats = [
            'total' => Survey::count(),
            'average' => Survey::avg('rating'),
        ];

        return view('surveys', [
            'items' => $items,
            'stats' => $stats,
            'link' => rtrim(config('app.url'), '/').'/survey',
        ]);
    }

    public function destroy(Request $request, int $id)
    {
        $survey = Survey::findOrFail($id);
        $survey->delete();

        AuditLog::tryCreate(['actor_id' => $request->user()->id, 'action' => 'DELETE', 'entity' => 'surveys', 'record_id' => $id]);

        return response()->json(['ok' => true]);
    }

    /** QR Code survei (SVG hijau, dibuat server-side). */
    public function qr(Request $request)
    {
        $url = rtrim(config('app.url'), '/').'/survey';

        $svg = QrCode::format('svg')
            ->size(320)
            ->margin(2)
            ->color(6, 93, 53)
            ->backgroundColor(255, 255, 255)
            ->generate($url);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}
