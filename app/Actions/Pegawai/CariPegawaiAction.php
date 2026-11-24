<?php

namespace App\Actions\Pegawai;

use App\Models\Pegawai;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CariPegawaiAction
{
    /**
     * Cari pegawai berdasarkan NIP, nama, atau jabatan, urut menurut NIP.
     *
     * @return Collection<int, Pegawai>
     */
    public function __invoke(?string $kataKunci = null): Collection
    {
        $kataKunci = trim((string) $kataKunci);

        return Pegawai::query()
            ->with('departemen')
            ->when($kataKunci !== '', function (Builder $query) use ($kataKunci): void {
                $query->where(function (Builder $query) use ($kataKunci): void {
                    $query->where('nip', 'like', "%{$kataKunci}%")
                        ->orWhere('nama', 'like', "%{$kataKunci}%")
                        ->orWhere('jabatan', 'like', "%{$kataKunci}%");
                });
            })
            ->orderBy('nip')
            ->get();
    }
}
