<?php

namespace App\Actions\Laporan;

use App\Models\Pegawai;
use App\Models\Presensi;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;

class RingkasLaporanAction
{
    /**
     * Ringkasan presensi untuk satu pegawai. Policy laporan memastikan
     * pegawai tidak bisa membuka ringkasan pegawai lain.
     *
     * @return Collection<int, Presensi>
     */
    public function __invoke(User $user, Pegawai $pegawai): Collection
    {
        Gate::forUser($user)->authorize('view', $pegawai);

        return Presensi::query()
            ->where('id_pegawai', $pegawai->id)
            ->orderByDesc('tgl_presensi')
            ->orderByDesc('id')
            ->get();
    }
}
