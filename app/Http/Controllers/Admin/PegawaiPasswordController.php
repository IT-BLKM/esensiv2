<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Pegawai\BuatPasswordAwalAction;
use App\Http\Controllers\Controller;
use App\Models\PasswordResetLog;
use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PegawaiPasswordController extends Controller
{
    public function edit(Pegawai $pegawai): View
    {
        $this->authorize('resetPassword', $pegawai->user);

        return view('admin.pegawai.password.edit', ['pegawai' => $pegawai]);
    }

    public function update(
        Request $request,
        Pegawai $pegawai,
        BuatPasswordAwalAction $buatPasswordAwal,
    ): RedirectResponse {
        $this->authorize('resetPassword', $pegawai->user);

        /** @var User $admin */
        $admin = $request->user('web');

        $passwordBaru = $buatPasswordAwal();

        $pegawai->user->update(['password' => $passwordBaru]);

        PasswordResetLog::create([
            'admin_id' => $admin->id,
            'id_pegawai' => $pegawai->id,
        ]);

        /*
         * Password plaintext hanya dikirim sekali lewat flash session.
         * Halaman tujuan yang menampilkannya tidak menyimpannya lagi.
         */
        return redirect()->route('admin.pegawai.password.edit', $pegawai)
            ->with('status', "Password {$pegawai->nama} berhasil direset.")
            ->with('passwordSekali', $passwordBaru);
    }
}
