<?php

namespace App\Policies;

use App\Models\Presensi;
use App\Models\User;

/**
 * Otorisasi data presensi.
 *
 * Admin melihat seluruh presensi. Pegawai hanya boleh membaca presensi
 * miliknya sendiri; mutation tetap lewat Action yang ber-owner dari auth.
 */
class PresensiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin' || $user->pegawai !== null;
    }

    public function view(User $user, Presensi $presensi): bool
    {
        return $this->milikAtauAdmin($user, $presensi->id_pegawai);
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin' || $user->pegawai !== null;
    }

    public function update(User $user, Presensi $presensi): bool
    {
        return $this->milikAtauAdmin($user, $presensi->id_pegawai);
    }

    public function delete(User $user, Presensi $presensi): bool
    {
        return $this->milikAtauAdmin($user, $presensi->id_pegawai);
    }

    private function milikAtauAdmin(User $user, int $idPegawai): bool
    {
        return $user->role === 'admin' || $user->pegawai?->id === $idPegawai;
    }
}
