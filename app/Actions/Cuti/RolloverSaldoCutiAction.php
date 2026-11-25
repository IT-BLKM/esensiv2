<?php

namespace App\Actions\Cuti;

use App\Models\Cuti;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\Presensi;
use App\Models\SaldoCuti;

/**
 * Rollover tabungan Cuti Tahunan untuk satu tahun berjalan.
 *
 * `saldo(tahun_sumber = Y-1) = min(sisa kuota tahun Y-1, maks_bawa)`.
 * Idempotent: bila baris tahun_sumber Y-1 sudah ada, tidak ditimpa — ini
 * menjaga hasil init ETL yang dihitung dari riwayat pengajuan penuh.
 *
 * Bila pegawai tidak punya aktivitas sama sekali pada tahun sumber (tidak ada
 * presensi/pengajuan), rollover dilewati. Tanpa penjaga ini, sistem baru akan
 * mengarang tabungan 6 hari untuk tahun sebelum ada data.
 */
class RolloverSaldoCutiAction
{
    public function __construct(
        private readonly HitungSisaTahunanAction $hitungSisaTahunan,
    ) {}

    /**
     * @return SaldoCuti|null baris yang dibuat; null bila sudah ada, tidak ada
     *                        aktivitas, atau sisa kuota 0
     */
    public function __invoke(Pegawai $pegawai, Cuti $cuti, int $tahunSekarang): ?SaldoCuti
    {
        $tahunSumber = $tahunSekarang - 1;

        $sudahAda = SaldoCuti::query()
            ->where('id_pegawai', $pegawai->id)
            ->where('id_cuti', $cuti->id)
            ->where('tahun_sumber', $tahunSumber)
            ->exists();

        if ($sudahAda || ! $this->adaAktivitas($pegawai, $tahunSumber)) {
            return null;
        }

        $hasil = ($this->hitungSisaTahunan)($pegawai, $cuti, $tahunSumber);
        $saldo = min(
            max(0, (int) ($hasil['sisaKuota'] ?? 0)),
            max(0, (int) $cuti->maks_bawa),
        );

        if ($saldo <= 0) {
            return null;
        }

        return SaldoCuti::query()->firstOrCreate(
            [
                'id_pegawai' => $pegawai->id,
                'id_cuti' => $cuti->id,
                'tahun_sumber' => $tahunSumber,
            ],
            ['saldo' => $saldo],
        );
    }

    private function adaAktivitas(Pegawai $pegawai, int $tahun): bool
    {
        $punyaPresensi = Presensi::query()
            ->where('id_pegawai', $pegawai->id)
            ->whereBetween('tgl_presensi', ["{$tahun}-01-01", "{$tahun}-12-31"])
            ->exists();

        if ($punyaPresensi) {
            return true;
        }

        return Pengajuan::query()
            ->where('id_pegawai', $pegawai->id)
            ->whereBetween('tanggal_mulai', ["{$tahun}-01-01", "{$tahun}-12-31"])
            ->exists();
    }
}
