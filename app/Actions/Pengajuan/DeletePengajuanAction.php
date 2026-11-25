<?php

namespace App\Actions\Pengajuan;

use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class DeletePengajuanAction
{
    /**
     * Hapus pengajuan hanya setelah policy memeriksa kepemilikan.
     */
    public function __invoke(User $user, Pengajuan $pengajuan): bool
    {
        Gate::forUser($user)->authorize('delete', $pengajuan);

        return (bool) $pengajuan->delete();
    }
}
