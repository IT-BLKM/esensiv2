<?php

namespace App\Policies;

use App\Models\JamKerja;
use App\Models\User;

class JamKerjaPolicy
{
    /**
     * Seluruh kelola master jam kerja hanya untuk admin.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, JamKerja $jamKerja): bool
    {
        return $user->role === 'admin';
    }

    public function delete(User $user, JamKerja $jamKerja): bool
    {
        return $user->role === 'admin';
    }
}
