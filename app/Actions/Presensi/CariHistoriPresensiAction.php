<?php

namespace App\Actions\Presensi;

use App\Models\Pegawai;
use App\Models\Presensi;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

class CariHistoriPresensiAction
{
    public function __construct(
        private readonly HitungMenitTerlambatAction $hitungMenitTerlambat,
    ) {}

    /**
     * Histori presensi satu pegawai pada satu bulan, urut terbaru.
     *
     * Query memakai Eloquent + binding (`where`, `whereBetween`) tanpa
     * `whereRaw`/konkatenasi input, sehingga parameter filter tidak pernah
     * menjadi bagian dari SQL.
     *
     * @return list<array{
     *     id: int,
     *     tanggal: string,
     *     labelTanggal: string,
     *     hari: string,
     *     status: string,
     *     statusLabel: string,
     *     shift: string|null,
     *     jamMasuk: string|null,
     *     jamKeluar: string|null,
     *     menitTerlambat: int
     * }>
     */
    public function __invoke(Pegawai $pegawai, int $bulan, int $tahun): array
    {
        $zonaWaktu = (string) config('presensi.zona_waktu', 'Asia/Jakarta');
        $awalBulan = CarbonImmutable::create($tahun, $bulan, 1, 0, 0, 0, $zonaWaktu)->startOfMonth();
        $akhirBulan = $awalBulan->endOfMonth();

        return Presensi::query()
            ->with(['jamKerja', 'pengajuan'])
            ->where('id_pegawai', $pegawai->id)
            ->whereBetween('tgl_presensi', [$awalBulan->toDateString(), $akhirBulan->toDateString()])
            ->orderByDesc('tgl_presensi')
            ->orderByDesc('id')
            ->get()
            ->map(function (Presensi $presensi): array {
                $tanggal = Carbon::parse((string) $presensi->tgl_presensi);

                return [
                    'id' => $presensi->id,
                    'tanggal' => (string) $presensi->tgl_presensi,
                    'labelTanggal' => $tanggal->locale('id')->translatedFormat('d M Y'),
                    'hari' => $tanggal->locale('id')->translatedFormat('l'),
                    'status' => $presensi->status,
                    'statusLabel' => Presensi::LABEL_STATUS[$presensi->status] ?? ucfirst($presensi->status),
                    'shift' => $presensi->jamKerja?->nama,
                    'jamMasuk' => $this->formatJam($presensi->jam_masuk),
                    'jamKeluar' => $this->formatJam($presensi->jam_keluar),
                    'menitTerlambat' => ($this->hitungMenitTerlambat)($presensi),
                ];
            })
            ->values()
            ->all();
    }

    private function formatJam(?string $jam): ?string
    {
        return $jam === null || $jam === '' ? null : Carbon::parse($jam)->format('H:i');
    }
}
