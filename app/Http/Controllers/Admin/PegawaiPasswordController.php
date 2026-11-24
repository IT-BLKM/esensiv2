<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\PasswordResetLog;
use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PegawaiPasswordController extends Controller
{
    public function edit(Pegawai $pegawai): View
    {
        $this->authorize('resetPassword', $pegawai->user);

        return view('admin.pegawai.password.edit', ['pegawai' => $pegawai]);
    }

    public function update(ResetPasswordRequest $request, Pegawai $pegawai): RedirectResponse
    {
        $this->authorize('resetPassword', $pegawai->user);

        /** @var User $admin */
        $admin = $request->user('web');

        $pegawai->user->update(['password' => $request->validated('password')]);

        PasswordResetLog::create([
            'admin_id' => $admin->id,
            'id_pegawai' => $pegawai->id,
        ]);

        return redirect()->route('admin.dashboard')
            ->with('status', "Password {$pegawai->nama} berhasil direset.");
    }
}
