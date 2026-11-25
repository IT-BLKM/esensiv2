<?php

namespace App\Actions\Pengajuan;

use App\Models\Pengajuan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;

class CariPengajuanAction
{
    /**
     * Daftar pengajuan yang boleh dilihat user. Pegawai selalu dibatasi
     * pada id_pegawai miliknya; admin melihat semua.
     *
     * Filter bulan/tahun memakai rentang tanggal_mulai dengan binding
     * (`whereBetween`), bukan konkatenasi SQL.
     *
     * @return Collection<int, Pengajuan>
     */
    public function __invoke(
        User $user,
        ?string $status = null,
        ?int $bulan = null,
        ?int $tahun = null,
    ): Collection {
        Gate::forUser($user)->authorize('viewAny', Pengajuan::class);

        $query = Pengajuan::query()
            ->with('cuti')
            ->when($user->role !== 'admin', fn (Builder $query) => $query->milikSaya($user))
            ->when(
                $status !== null,
                fn (Builder $query) => $query->where('status', $status),
            );

        if ($bulan !== null && $tahun !== null) {
            $awal = CarbonImmutable::create($tahun, $bulan, 1)->startOfMonth();

            $query->whereBetween('tanggal_mulai', [
                $awal->toDateString(),
                $awal->endOfMonth()->toDateString(),
            ]);
        } elseif ($tahun !== null) {
            $query->whereBetween('tanggal_mulai', ["{$tahun}-01-01", "{$tahun}-12-31"]);
        } elseif ($bulan !== null) {
            $query->whereMonth('tanggal_mulai', $bulan);
        }

        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }
}
