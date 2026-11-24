<?php

namespace App\Policies;

use App\Models\Pegawai;
use App\Models\User;

/**
 * Otorisasi laporan per pegawai.
 *
 * Laporan belum punya model tersendiri (issue #42), jadi policy ini diikat
 * ke model Pegawai lewat Gate::policy di AppServiceProvider. Subjek yang
 * diotorisasi adalah pegawai yang datanya dilaporkan.
 */
class LaporanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin' || $user->pegawai !== null;
    }

    public function view(User $user, Pegawai $pegawai): bool
    {
        return $user->role === 'admin' || $user->pegawai?->id === $pegawai->id;
    }
}
