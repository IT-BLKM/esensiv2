<?php

namespace App\Actions\Pengajuan;

use App\Models\HariLibur;
use App\Models\KonfigurasiJamKerja;
use App\Models\Pegawai;
use Carbon\CarbonImmutable;

/**
 * Daftar hari kerja dalam rentang pengajuan.
 *
 * Hari kerja = hari yang punya shift di konfigurasi jam kerja pegawai. Bila
 * pegawai belum punya konfigurasi sama sekali, dipakai default Senin–Jumat
 * dengan `id_jam_kerja = null` (docs/database-design.md aturan #1).
 */
class DaftarHariKerjaAction
{
    /** @var array<int, string> */
    private const HARI_DEFAULT = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

    /**
     * @return list<array{tanggal: string, id_jam_kerja: int|null}>
     */
    public function __invoke(Pegawai $pegawai, CarbonImmutable $mulai, CarbonImmutable $selesai): array
    {
        if ($selesai->lt($mulai)) {
            return [];
        }

        $konfigurasi = KonfigurasiJamKerja::query()
            ->where('id_pegawai', $pegawai->id)
            ->get(['hari', 'id_jam_kerja'])
            ->keyBy('hari');

        $hariLibur = array_fill_keys(HariLibur::dalamRentang($mulai, $selesai), true);
        $hasil = [];

        for ($tanggal = $mulai->startOfDay(); $tanggal->lte($selesai); $tanggal = $tanggal->addDay()) {
            if (isset($hariLibur[$tanggal->toDateString()])) {
                continue;
            }

            // Carbon: 0 = Minggu. KonfigurasiJamKerja::HARI: 0 = Senin.
            $namaHari = KonfigurasiJamKerja::HARI[($tanggal->dayOfWeek + 6) % 7];

            if ($konfigurasi->isEmpty()) {
                if (in_array($namaHari, self::HARI_DEFAULT, true)) {
                    $hasil[] = [
                        'tanggal' => $tanggal->toDateString(),
                        'id_jam_kerja' => null,
                    ];
                }

                continue;
            }

            $shift = $konfigurasi->get($namaHari);

            if ($shift !== null) {
                $hasil[] = [
                    'tanggal' => $tanggal->toDateString(),
                    'id_jam_kerja' => (int) $shift->id_jam_kerja,
                ];
            }
        }

        return $hasil;
    }
}
