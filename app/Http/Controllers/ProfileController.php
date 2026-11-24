<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/** Profil pengguna + ganti sandi (dengan verifikasi sandi lama). */
class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        $recent = AuditLog::with('actor:id,full_name')
            ->where('actor_id', $user->id)
            ->latest('id')->limit(8)->get();

        return view('profil', [
            'user' => $user,
            'recent' => $recent,
            'status' => session('status'),
            'errors' => session('errors')?->getBag('default')->all() ?? [],
        ]);
    }

    /** Perbarui profil dasar (nama & email notifikasi). */
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'string', 'email:rfc', 'max:150', 'unique:users,email,'.$request->user()->id],
        ], [
            'email.unique' => 'Email sudah dipakai pengguna lain.',
            'email.email' => 'Format email tidak valid.',
        ]);

        $request->user()->update([
            'full_name' => $data['full_name'],
            'email' => $data['email'] ?: null,
        ]);

        AuditLog::tryCreate(['actor_id' => $request->user()->id, 'action' => 'UPDATE', 'entity' => 'profil', 'record_id' => $request->user()->id]);

        return back()->with('status', 'Profil berhasil diperbarui.');
    }

    /** Ganti sandi — wajib sandi lama, sandi baru minimal 10 karakter kuat. */
    public function password(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string', 'max:200'],
            'password' => ['required', 'string', 'min:10', 'max:200', 'different:current_password', 'regex:/^(?=.*[A-Za-z])(?=.*\d).+$/'],
            'password_confirmation' => ['required', 'same:password'],
        ], [
            'current_password.required' => 'Sandi lama wajib diisi.',
            'password.min' => 'Sandi baru minimal 10 karakter.',
            'password.different' => 'Sandi baru harus berbeda dari sandi lama.',
            'password.regex' => 'Sandi baru harus mengandung huruf dan angka.',
            'password_confirmation.same' => 'Konfirmasi sandi tidak sama.',
        ]);

        $user = $request->user();
        if (! Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Sandi lama salah.'])->with('form', 'password');
        }

        $user->forceFill(['password' => $data['password']])->save();

        AuditLog::tryCreate(['actor_id' => $user->id, 'action' => 'GANTI_SANDI', 'entity' => 'profil', 'record_id' => $user->id]);

        return back()->with('status', 'Sandi berhasil diganti.');
    }
}
