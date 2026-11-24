<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Notifikasi in-app (ikon lonceng di topbar). */
class NotificationController extends Controller
{
    /** Halaman daftar notifikasi (terbaru dulu). */
    public function index(Request $request): View
    {
        $list = AppNotification::latest('id')->limit(120)->get();

        return view('notifikasi', [
            'list' => $list,
            'unread' => AppNotification::unread()->count(),
        ]);
    }

    /** Data terbaru untuk dropdown lonceng (JSON). */
    public function data(): JsonResponse
    {
        $rows = AppNotification::latest('id')->limit(6)->get(['id', 'type', 'title', 'url', 'read_at', 'created_at']);

        return response()->json([
            'unread' => AppNotification::unread()->count(),
            'items' => $rows->map(fn ($n) => [
                'icon' => AppNotification::iconFor($n->type),
                'title' => $n->title,
                'time' => $n->created_at->locale('id')->diffForHumans(),
                'url' => $n->url ?: '/notifikasi',
                'unread' => $n->read_at === null,
            ]),
        ]);
    }

    /** Tandai semua sudah dibaca. */
    public function readAll(): JsonResponse
    {
        AppNotification::unread()->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
