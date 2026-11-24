<?php

namespace App\Actions\Pengajuan;

use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;

class CariPengajuanAction
{
    /**
     * Daftar pengajuan yang boleh dilihat user. Pegawai selalu dibatasi
     * pada id_pegawai miliknya; admin melihat semua.
     *
     * @return Collection<int, Pengajuan>
     */
    public function __invoke(User $user, ?string $status = null): Collection
    {
        Gate::forUser($user)->authorize('viewAny', Pengajuan::class);

        return Pengajuan::query()
            ->when($user->role !== 'admin', fn (Builder $query) => $query->milikSaya($user))
            ->when(
                $status !== null,
                fn (Builder $query) => $query->where('status', $status),
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }
}
