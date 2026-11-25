<?php

namespace App\Actions\Presensi;

use App\Models\Presensi;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;

class CariPresensiAction
{
    /**
     * Daftar presensi yang boleh dilihat user. Pegawai hanya melihat
     * baris dengan id_pegawai miliknya; admin melihat semua.
     *
     * @return Collection<int, Presensi>
     */
    public function __invoke(User $user, ?string $status = null): Collection
    {
        Gate::forUser($user)->authorize('viewAny', Presensi::class);

        return Presensi::query()
            ->when($user->role !== 'admin', fn (Builder $query) => $query->milikSaya($user))
            ->when(
                $status !== null,
                fn (Builder $query) => $query->where('status', $status),
            )
            ->orderByDesc('tgl_presensi')
            ->orderByDesc('id')
            ->get();
    }
}
