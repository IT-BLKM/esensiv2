<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Reset password pegawai hanya boleh dilakukan oleh admin.
     */
    public function resetPassword(User $admin, User $target): bool
    {
        return $admin->role === 'admin';
    }
}
