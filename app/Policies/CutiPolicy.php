<?php

namespace App\Policies;

use App\Models\Cuti;
use App\Models\User;

class CutiPolicy
{
    /**
     * Kelola master jenis dan aturan cuti hanya untuk admin. Saldo pegawai
     * tidak diubah lewat halaman ini (issue #27).
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, Cuti $cuti): bool
    {
        return $user->role === 'admin';
    }

    public function delete(User $user, Cuti $cuti): bool
    {
        return $user->role === 'admin';
    }
}
