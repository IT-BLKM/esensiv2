<?php

namespace App\Actions\Pengajuan;

use App\Models\Pegawai;
use Carbon\CarbonImmutable;

/**
 * Hitung jumlah hari kerja dalam rentang tanggal pengajuan.
 *
 * Sumber aturan hari kerja ada di DaftarHariKerjaAction agar approval (#39)
 * memakai daftar tanggal yang sama dengan validasi pengajuan (#36).
 */
class HitungHariKerjaAction
{
    public function __construct(
        private readonly DaftarHariKerjaAction $daftarHariKerja,
    ) {}

    public function __invoke(Pegawai $pegawai, CarbonImmutable $mulai, CarbonImmutable $selesai): int
    {
        return count(($this->daftarHariKerja)($pegawai, $mulai, $selesai));
    }
}
