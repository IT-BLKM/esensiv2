<?php

namespace App\Actions\Cuti;

use App\Models\Cuti;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CariCutiAction
{
    /**
     * Cari jenis cuti berdasarkan kode atau nama, urut menurut kode.
     *
     * @return Collection<int, Cuti>
     */
    public function __invoke(?string $kataKunci = null): Collection
    {
        $kataKunci = trim((string) $kataKunci);

        return Cuti::query()
            ->when($kataKunci !== '', function (Builder $query) use ($kataKunci): void {
                $query->where(function (Builder $query) use ($kataKunci): void {
                    $query->where('kode', 'like', "%{$kataKunci}%")
                        ->orWhere('nama', 'like', "%{$kataKunci}%");
                });
            })
            ->orderBy('kode')
            ->get();
    }
}
