<?php

namespace App\Actions\JamKerja;

use App\Models\JamKerja;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CariJamKerjaAction
{
    /**
     * Cari jam kerja berdasarkan kode atau nama, urut menurut kode.
     *
     * @return Collection<int, JamKerja>
     */
    public function __invoke(?string $kataKunci = null): Collection
    {
        $kataKunci = trim((string) $kataKunci);

        return JamKerja::query()
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
