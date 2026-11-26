<?php

namespace App\Actions\Laporan;

use App\Actions\Presensi\HitungMenitPulangCepatAction;
use App\Actions\Presensi\HitungMenitTerlambatAction;
use App\Actions\Presensi\HitungPenggantianKeterlambatanAction;
use App\Models\Pegawai;
use App\Models\Presensi;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

/**
 * Laporan bulanan satu pegawai: daftar harian + rekap.
 *
 * Policy `view` pada Pegawai memastikan pegawai hanya bisa membuka
 * laporannya sendiri; admin boleh membuka semua.
 */
class LaporanBulananAction
{
    public function __construct(
        private readonly RekapKehadiranAction $rekap,
        private readonly KlasifikasiHariPresensiAction $klasifikasi,
        private readonly HitungMenitTerlambatAction $hitungMenitTerlambat,
        private readonly HitungMenitPulangCepatAction $hitungMenitPulangCepat,
        private readonly HitungPenggantianKeterlambatanAction $hitungPenggantian,
    ) {}

    /**
     * @return array{
     *     labelPeriode: string,
     *     baris: list<array{
     *         id: int,
     *         tanggal: string,
     *         labelTanggal: string,
     *         hari: string,
     *         shift: string|null,
     *         jamMasuk: string|null,
     *         jamKeluar: string|null,
     *         status: string,
     *         statusLabel: string,
     *         kodeStatus: string,
     *         kodeHari: string,
     *         klasifikasi: array<string, mixed>,
     *         menitTerlambat: int,
     *         menitPulangCepat: int,
     *         jmlJam: string,
     *         penggantian: array<string, mixed>|null
     *     }>,
     *     ringkasan: array<string, mixed>
     * }
     */
    public function __invoke(User $user, Pegawai $pegawai, int $bulan, int $tahun): array
    {
        Gate::forUser($user)->authorize('view', $pegawai);

        $zonaWaktu = (string) config('presensi.zona_waktu', 'Asia/Jakarta');
        $awal = CarbonImmutable::create($tahun, $bulan, 1, 0, 0, 0, $zonaWaktu)->startOfMonth();
        $akhir = $awal->endOfMonth();

        $presensi = Presensi::query()
            ->with(['jamKerja', 'pengajuan', 'penggantianKeterlambatan'])
            ->where('id_pegawai', $pegawai->id)
            ->whereBetween('tgl_presensi', [$awal->toDateString(), $akhir->toDateString()])
            ->orderByDesc('tgl_presensi')
            ->orderByDesc('id')
            ->get();

        $baris = $presensi
            ->map(function (Presensi $item): array {
                $tanggal = Carbon::parse((string) $item->tgl_presensi);
                $klasifikasi = ($this->klasifikasi)($item);

                return [
                    'id' => $item->id,
                    'tanggal' => (string) $item->tgl_presensi,
                    'labelTanggal' => $tanggal->locale('id')->translatedFormat('d M Y'),
                    'hari' => $tanggal->locale('id')->translatedFormat('l'),
                    'shift' => $item->jamKerja?->nama,
                    'jamMasuk' => $this->formatJam($item->jam_masuk),
                    'jamKeluar' => $this->formatJam($item->jam_keluar),
                    'status' => $item->status,
                    'statusLabel' => Presensi::LABEL_STATUS[$item->status] ?? ucfirst($item->status),
                    'kodeStatus' => $klasifikasi['kodeStatus'],
                    'kodeHari' => implode("\n", $klasifikasi['baris']),
                    'klasifikasi' => $klasifikasi,
                    'menitTerlambat' => ($this->hitungMenitTerlambat)($item),
                    'menitPulangCepat' => ($this->hitungMenitPulangCepat)($item),
                    'jmlJam' => $this->jmlJam($item->jam_masuk, $item->jam_keluar),
                    'penggantian' => ($this->hitungPenggantian)($item),
                ];
            })
            ->values()
            ->all();

        return [
            'labelPeriode' => $awal->locale('id')->translatedFormat('F Y'),
            'baris' => $baris,
            'ringkasan' => $this->rekap->ringkasBulanan($presensi, $awal),
        ];
    }

    private function formatJam(?string $jam): ?string
    {
        return $jam === null || $jam === '' ? null : Carbon::parse($jam)->format('H:i');
    }

    /**
     * Total jam kerja untuk kolom "Jml Jam" pada laporan/ekspor.
     */
    private function jmlJam(?string $masuk, ?string $keluar): string
    {
        if ($masuk === null || $keluar === null || $masuk === '' || $keluar === '') {
            return '-';
        }

        $awal = Carbon::parse($masuk);
        $akhir = Carbon::parse($keluar);

        if ($akhir->lt($awal)) {
            return '-';
        }

        $menit = (int) $awal->diffInMinutes($akhir);

        return intdiv($menit, 60).':'.str_pad((string) ($menit % 60), 2, '0', STR_PAD_LEFT);
    }
}
