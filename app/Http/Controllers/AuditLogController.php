<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Log aktivitas aplikasi (khusus admin) — dibaca dari tabel audit_logs. */
class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $q = AuditLog::with('actor:id,full_name,username,role')
            ->orderByDesc('id');

        if ($search = trim((string) $request->query('q', ''))) {
            $safe = mb_substr($search, 0, 60);
            $q->where(function ($w) use ($safe) {
                $w->where('action', 'like', '%'.$safe.'%')
                    ->orWhere('entity', 'like', '%'.$safe.'%')
                    ->orWhereHas('actor', fn ($a) => $a->where('username', 'like', '%'.$safe.'%'));
            });
        }

        return view('log', [
            'rows' => $q->paginate(25)->withQueryString(),
            'search' => $search,
        ]);
    }
}
