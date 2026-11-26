<?php

namespace App\Policies;

use App\Models\TukinNominal;
use App\Models\User;

class TukinNominalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, TukinNominal $tukinNominal): bool
    {
        return $user->role === 'admin';
    }

    public function delete(User $user, TukinNominal $tukinNominal): bool
    {
        return $user->role === 'admin';
    }
}
