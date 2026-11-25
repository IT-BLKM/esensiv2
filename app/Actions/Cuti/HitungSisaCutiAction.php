<?php

namespace App\Actions\Cuti;

use App\Models\Cuti;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use Carbon\CarbonImmutable;

/**
 * Satu pintu aturan sisa cuti PNS per pegawai, per jenis, per tahun.
 *
 * - C01 Cuti Tahunan: kuota tahunan + tabungan Y-1/Y-2 (FIFO), lihat
 *   HitungSisaTahunanAction.
 * - C02 Cuti Bersalin: memakai kuota sendiri; bila diambil, kuota C01 tahun
 *   itu hangus.
 * - C03 Cuti Alasan Penting: berdiri sendiri, tidak mengurangi jenis lain.
 * - C04 Cuti Besar: hanya boleh bila belum pernah dalam 5 tahun kalender
 *   terakhir, atau alasan haji (justifikasi manual dari form/admin).
 *
 * Hasilnya dipakai form pengajuan (#36) dan panel approve (#39).
 */
class HitungSisaCutiAction
{
    public function __construct(
        private readonly HitungPemakaianCutiAction $hitungPemakaian,
        private readonly HitungSisaTahunanAction $hitungSisaTahunan,
        private readonly RolloverSaldoCutiAction $rollover,
    ) {}

    /**
     * @return array{
     *     kode: string,
     *     nama: string,
     *     tahun: int,
     *     kuota: int|null,
     *     maksHari: int|null,
     *     terpakai: int,
     *     sisaKuota: int|null,
     *     tabungan: list<array{tahun_sumber: int, saldo: int, terpakai: int, sisa: int}>,
     *     totalTersedia: int|null,
     *     maksBawaTahunDepan: int,
     *     kuotaHangus: bool,
     *     memenuhiSyarat: bool,
     *     alasanTidakMemenuhi: string|null
     * }
     */
    public function __invoke(Pegawai $pegawai, Cuti $cuti, int $tahun, bool $alasanHaji = false): array
    {
        if ($cuti->kode === Cuti::KODE_TAHUNAN) {
            /*
             * Jaring pengaman bila cron 1 Januari tidak jalan. Hanya untuk
             * tahun berjalan; query tahun lama tidak boleh membuat tabungan
             * baru secara retroaktif.
             */
            if ($tahun === $this->tahunSekarang()) {
                ($this->rollover)($pegawai, $cuti, $tahun);
            }

            return ($this->hitungSisaTahunan)($pegawai, $cuti, $tahun);
        }

        $terpakai = ($this->hitungPemakaian)($pegawai, $cuti, $tahun);
        $kuota = $cuti->kuota === null ? null : max(0, (int) $cuti->kuota);
        $maksHari = $cuti->maks_hari === null ? null : max(0, (int) $cuti->maks_hari);
        $sisaKuota = $kuota === null ? null : max(0, $kuota - $terpakai);
        $memenuhiSyarat = true;
        $alasanTidakMemenuhi = null;

        if ($cuti->kode === Cuti::KODE_BESAR) {
            $memenuhiSyarat = $alasanHaji
                || ! $this->pernahCutiBesarLimaTahun($pegawai, $cuti, $tahun);

            if (! $memenuhiSyarat) {
                $alasanTidakMemenuhi = 'Cuti besar hanya dapat diambil setelah 5 tahun tidak pernah '
                    .'mengambil cuti besar, kecuali dengan alasan haji.';
            }
        }

        $totalTersedia = $memenuhiSyarat
            ? ($sisaKuota ?? ($maksHari === null ? null : max(0, $maksHari - $terpakai)))
            : 0;

        return [
            'kode' => $cuti->kode,
            'nama' => $cuti->nama,
            'tahun' => $tahun,
            'kuota' => $kuota,
            'maksHari' => $maksHari,
            'terpakai' => $terpakai,
            'sisaKuota' => $sisaKuota,
            'tabungan' => [],
            'totalTersedia' => $totalTersedia,
            'maksBawaTahunDepan' => 0,
            'kuotaHangus' => false,
            'memenuhiSyarat' => $memenuhiSyarat,
            'alasanTidakMemenuhi' => $alasanTidakMemenuhi,
        ];
    }

    /**
     * Lima tahun kalender terakhir, termasuk tahun berjalan.
     */
    private function pernahCutiBesarLimaTahun(Pegawai $pegawai, Cuti $cuti, int $tahun): bool
    {
        return Pengajuan::query()
            ->where('id_pegawai', $pegawai->id)
            ->where('jenis', 'cuti')
            ->where('status', 'approved')
            ->where('id_cuti', $cuti->id)
            ->whereBetween('tanggal_mulai', [($tahun - 4).'-01-01', "{$tahun}-12-31"])
            ->exists();
    }

    private function tahunSekarang(): int
    {
        return CarbonImmutable::now((string) config('presensi.zona_waktu', 'Asia/Jakarta'))->year;
    }
}
