<?php

namespace App\Actions\Tukin;

use App\Actions\Laporan\HitungPotonganTunjanganKinerjaAction;
use App\Models\Pegawai;
use App\Models\TukinNominal;

/**
 * Ambil rincian TUKIN satu pegawai lengkap dengan koreksi dan nominal rupiah.
 */
class AmbilDetailTukinAction
{
    public function __construct(
        private readonly HitungPotonganTunjanganKinerjaAction $hitungTukin,
        private readonly AmbilKoreksiTukinAction $ambilKoreksi,
        private readonly LengkapiTukinAction $lengkapiTukin,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(Pegawai $pegawai, int $tahun, ?int $bulan): array
    {
        $hasil = $bulan === null
            ? $this->hitungTukin->tahunan($pegawai, $tahun)
            : $this->hitungTukin->bulanan($pegawai, $tahun, $bulan);
        $nominal = TukinNominal::query()->where('jabatan', $pegawai->jabatan)->value('nominal');

        return ($this->lengkapiTukin)(
            $hasil,
            $nominal === null ? null : (int) $nominal,
            $this->ambilKoreksi->pegawai($pegawai, $tahun, $bulan),
        );
    }
}
