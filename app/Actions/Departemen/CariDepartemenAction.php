<?php

namespace App\Actions\Departemen;

use App\Models\Departemen;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CariDepartemenAction
{
    /**
     * Cari departemen berdasarkan kode atau nama, urut menurut kode.
     *
     * @return Collection<int, Departemen>
     */
    public function __invoke(?string $kataKunci = null): Collection
    {
        $kataKunci = trim((string) $kataKunci);

        return Departemen::query()
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
