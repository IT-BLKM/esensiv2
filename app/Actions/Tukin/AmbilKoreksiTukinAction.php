<?php

namespace App\Actions\Tukin;

use App\Models\Pegawai;
use App\Models\TukinKoreksi;
use Illuminate\Support\Collection;

class AmbilKoreksiTukinAction
{
    /**
     * Koreksi semua pegawai pada periode, dikelompokkan per id pegawai.
     *
     * @return Collection<int, \Illuminate\Database\Eloquent\Collection<int, TukinKoreksi>>
     */
    public function semua(int $tahun, ?int $bulan): Collection
    {
        return TukinKoreksi::query()
            ->with('pembuat:id,nama')
            ->where('tahun', $tahun)
            ->when($bulan !== null, fn ($query) => $query->where('bulan', $bulan))
            ->orderBy('created_at')
            ->get()
            ->groupBy('id_pegawai')
            ->toBase();
    }

    /**
     * @return Collection<int, TukinKoreksi>
     */
    public function pegawai(Pegawai $pegawai, int $tahun, ?int $bulan): Collection
    {
        return TukinKoreksi::query()
            ->with('pembuat:id,nama')
            ->where('id_pegawai', $pegawai->id)
            ->where('tahun', $tahun)
            ->when($bulan !== null, fn ($query) => $query->where('bulan', $bulan))
            ->orderBy('created_at')
            ->get();
    }
}
