<?php

namespace App\Policies;

use App\Models\TunjanganTingkat;
use App\Models\User;

class TunjanganTingkatPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, TunjanganTingkat $tunjanganTingkat): bool
    {
        return $user->role === 'admin';
    }

    public function updateAny(User $user): bool
    {
        return $user->role === 'admin';
    }
}
