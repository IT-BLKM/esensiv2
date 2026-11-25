<?php

namespace App\Actions\Cuti;

use App\Models\Cuti;
use App\Models\Pegawai;
use App\Models\Presensi;

class HitungPemakaianCutiAction
{
    /**
     * Hari cuti terpakai = baris presensi (hari kerja) dari pengajuan
     * approved dengan jenis cuti tersebut pada tahun itu.
     *
     * Baris presensi adalah sumber kebenaran konsumsi (docs/database-design.md
     * aturan #8): approve membuat baris presensi hanya untuk hari ber-shift,
     * dan pembatalan menghapus baris itu kembali.
     */
    public function __invoke(Pegawai $pegawai, Cuti $cuti, int $tahun): int
    {
        return Presensi::query()
            ->join('pengajuan', 'pengajuan.id', '=', 'presensi.id_pengajuan')
            ->where('presensi.id_pegawai', $pegawai->id)
            ->where('presensi.status', 'cuti')
            ->where('pengajuan.jenis', 'cuti')
            ->where('pengajuan.status', 'approved')
            ->where('pengajuan.id_cuti', $cuti->id)
            ->whereBetween('presensi.tgl_presensi', ["{$tahun}-01-01", "{$tahun}-12-31"])
            ->count();
    }
}
