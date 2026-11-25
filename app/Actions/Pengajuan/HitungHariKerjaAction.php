<?php

namespace App\Actions\Pengajuan;

use App\Models\KonfigurasiJamKerja;
use App\Models\Pegawai;
use Carbon\CarbonImmutable;

/**
 * Hitung jumlah hari kerja dalam rentang tanggal pengajuan.
 *
 * Hari kerja adalah hari yang punya shift di konfigurasi jam kerja pegawai.
 * Bila pegawai belum punya konfigurasi, dipakai default Senin–Jumat sesuai
 * docs/database-design.md §"Aturan bisnis yang menempel di skema".
 */
class HitungHariKerjaAction
{
    /** @var array<int, string> */
    private const HARI_DEFAULT = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

    public function __invoke(Pegawai $pegawai, CarbonImmutable $mulai, CarbonImmutable $selesai): int
    {
        if ($selesai->lt($mulai)) {
            return 0;
        }

        $hariKerja = KonfigurasiJamKerja::query()
            ->where('id_pegawai', $pegawai->id)
            ->pluck('hari')
            ->map(fn (mixed $hari): string => (string) $hari)
            ->all();

        if ($hariKerja === []) {
            $hariKerja = self::HARI_DEFAULT;
        }

        $jumlah = 0;

        for ($tanggal = $mulai->startOfDay(); $tanggal->lte($selesai); $tanggal = $tanggal->addDay()) {
            // Carbon: 0 = Minggu. KonfigurasiJamKerja::HARI: 0 = Senin.
            $namaHari = KonfigurasiJamKerja::HARI[($tanggal->dayOfWeek + 6) % 7];

            if (in_array($namaHari, $hariKerja, true)) {
                $jumlah++;
            }
        }

        return $jumlah;
    }
}
