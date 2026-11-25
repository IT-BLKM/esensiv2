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
        // Pengajuan yang sudah diputuskan tidak boleh dihapus; approved harus
        // dibatalkan lewat alur cancel approval, bukan delete.
        return $pengajuan->status === 'pending'
            && $this->milikAtauAdmin($user, $pengajuan->id_pegawai);
    }

    /**
     * Approve/reject hanya untuk admin. Dipakai action keputusan (#39),
     * terpisah dari update biasa agar pegawai tidak bisa memutuskan sendiri.
     */
    public function decide(User $user, Pengajuan $pengajuan): bool
    {
        return $user->role === 'admin';
    }

    private function milikAtauAdmin(User $user, int $idPegawai): bool
    {
        return $user->role === 'admin' || $user->pegawai?->id === $idPegawai;
    }
}
