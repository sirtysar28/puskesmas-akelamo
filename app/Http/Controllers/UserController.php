<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserController extends Controller
{
    /** Daftar pengguna (khusus admin). */
    public function index(): View
    {
        return view('users', [
            'users' => User::orderByDesc('id')->get(['id', 'full_name', 'username', 'role', 'is_active', 'created_at']),
        ]);
    }

    /** Buat akun baru. Password minimal 12 karakter, hash bcrypt cost 12. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'username' => ['required', 'string', 'min:3', 'max:80', 'regex:/^[a-zA-Z0-9_.-]+$/', 'unique:users,username'],
            'role' => ['required', 'in:'.implode(',', User::ROLES)],
            'password' => ['required', 'string', 'min:12', 'max:200'],
        ], [
            'username.regex' => 'Username hanya boleh huruf, angka, titik, garis bawah, dan strip.',
            'username.unique' => 'Username sudah dipakai.',
            'password.min' => 'Kata sandi minimal 12 karakter.',
        ]);

        $user = User::create([
            'full_name' => $data['full_name'],
            'username' => $data['username'],
            'role' => $data['role'],
            'password' => $data['password'], // di-hash otomatis oleh cast 'hashed'
            'is_active' => true,
        ]);

        AuditLog::tryCreate(['actor_id' => $request->user()->id, 'action' => 'CREATE', 'entity' => 'users', 'record_id' => $user->id]);

        return response()->json(['ok' => true, 'id' => $user->id], 201);
    }

    /** Aktifkan / nonaktifkan akun. Nonaktif = semua sesi diputus. */
    public function toggleActive(Request $request, int $id): JsonResponse
    {
        $target = User::findOrFail($id);

        if ($target->id === $request->user()->id) {
            return response()->json(['error' => 'Tidak dapat menonaktifkan akun sendiri.'], 400);
        }

        $request->validate(['is_active' => ['required', 'boolean']]);
        $active = (bool) $request->input('is_active');
        $target->update(['is_active' => $active]);

        if (! $active) {
            DB::table('sessions')->where('user_id', $target->id)->delete();
        }

        AuditLog::tryCreate(['actor_id' => $request->user()->id, 'action' => 'UPDATE', 'entity' => 'users', 'record_id' => $id]);

        return response()->json(['ok' => true]);
    }

    /** Reset kata sandi: hash baru + seluruh sesi aktif target diputus. */
    public function resetPassword(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:12', 'max:200'],
        ], [
            'password.min' => 'Kata sandi minimal 12 karakter.',
        ]);

        $target = User::findOrFail($id);
        $target->update(['password' => $data['password']]);

        DB::table('sessions')->where('user_id', $target->id)->delete();

        AuditLog::tryCreate(['actor_id' => $request->user()->id, 'action' => 'RESET_PASSWORD', 'entity' => 'users', 'record_id' => $id]);

        return response()->json(['ok' => true]);
    }
}
