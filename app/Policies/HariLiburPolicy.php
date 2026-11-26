<?php

namespace App\Policies;

use App\Models\HariLibur;
use App\Models\User;

/**
 * Seluruh pengelolaan kalender libur hanya untuk admin.
 */
class HariLiburPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, HariLibur $hariLibur): bool
    {
        return $user->role === 'admin';
    }

    public function delete(User $user, HariLibur $hariLibur): bool
    {
        return $user->role === 'admin';
    }
}
