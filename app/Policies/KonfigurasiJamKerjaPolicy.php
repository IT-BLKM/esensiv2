<?php

namespace App\Policies;

use App\Models\User;

/**
 * Otorisasi pengaturan jam kerja per pegawai.
 *
 * Subjek aksesnya adalah fitur konfigurasi, bukan model Pegawai — policy
 * Pegawai sudah dipakai LaporanPolicy (issue #22).
 */
class KonfigurasiJamKerjaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function delete(User $user): bool
    {
        return $user->role === 'admin';
    }
}
