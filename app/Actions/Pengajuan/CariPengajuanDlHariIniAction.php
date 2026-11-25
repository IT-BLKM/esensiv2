<?php

namespace App\Actions\Pengajuan;

use App\Models\Pegawai;
use App\Models\Pengajuan;
use Carbon\CarbonInterface;

/**
 * Pengajuan Dinas Luar yang disetujui dan mencakup satu tanggal.
 *
 * Dipakai halaman presensi dan StorePresensiAction untuk membedakan
 * DL penuh (tidak perlu absen) dan DL setengah (absen sekali).
 */
class CariPengajuanDlHariIniAction
{
    public function __invoke(Pegawai $pegawai, CarbonInterface $tanggal): ?Pengajuan
    {
        return Pengajuan::query()
            ->where('id_pegawai', $pegawai->id)
            ->where('jenis', 'dl')
            ->where('status', 'approved')
            ->where('tanggal_mulai', '<=', $tanggal->toDateString())
            ->where('tanggal_berakhir', '>=', $tanggal->toDateString())
            ->orderBy('created_at')
            ->first();
    }
}
