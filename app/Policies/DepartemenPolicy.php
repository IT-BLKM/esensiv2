<?php

namespace App\Policies;

use App\Models\Departemen;
use App\Models\User;

class DepartemenPolicy
{
    /**
     * Seluruh kelola master departemen hanya untuk admin.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, Departemen $departemen): bool
    {
        return $user->role === 'admin';
    }

    public function delete(User $user, Departemen $departemen): bool
    {
        return $user->role === 'admin';
    }
}
