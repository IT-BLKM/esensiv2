<?php

namespace App\Policies;

use App\Models\Pengajuan;
use App\Models\User;

/**
 * Otorisasi data pengajuan.
 *
 * Admin mengelola semua pengajuan. Pegawai hanya boleh melihat, mengubah,
 * dan menghapus pengajuan yang id_pegawai-nya tertaut ke user tersebut.
 */
class PengajuanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin' || $user->pegawai !== null;
    }

    public function view(User $user, Pengajuan $pengajuan): bool
    {
        return $this->milikAtauAdmin($user, $pengajuan->id_pegawai);
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin' || $user->pegawai !== null;
    }

    public function update(User $user, Pengajuan $pengajuan): bool
    {
        return $this->milikAtauAdmin($user, $pengajuan->id_pegawai);
    }

    public function delete(User $user, Pengajuan $pengajuan): bool
    {
        return $this->milikAtauAdmin($user, $pengajuan->id_pegawai);
    }

    private function milikAtauAdmin(User $user, int $idPegawai): bool
    {
        return $user->role === 'admin' || $user->pegawai?->id === $idPegawai;
    }
}
