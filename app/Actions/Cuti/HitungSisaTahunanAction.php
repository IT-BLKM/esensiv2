<?php

namespace App\Actions\Cuti;

use App\Models\Cuti;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\SaldoCuti;

/**
 * Hitung sisa Cuti Tahunan (C01) untuk satu tahun, tanpa efek samping.
 *
 * Aturan (docs/database-design.md aturan #6–#7):
 * - Kuota tahunan dari master `cuti.kuota` (12).
 * - Tabungan = `saldo_cuti` tahun_sumber Y-2 dan Y-1, masing-masing dibatasi
 *   `cuti.maks_bawa` (6). Tahun lebih lama diabaikan lewat filter, tanpa hapus.
 * - Pemakaian tahun berjalan dibebankan FIFO: tabungan paling lama dulu,
 *   baru kuota tahun berjalan.
 * - Jika Cuti Bersalin (C02) diambil tahun itu, kuota tahun berjalan hangus
 *   (0), tetapi tabungan lama tetap tersedia.
 * - Rollover tahun depan = min(sisa kuota tahun berjalan, maks_bawa).
 */
class HitungSisaTahunanAction
{
    public function __construct(
        private readonly HitungPemakaianCutiAction $hitungPemakaian,
    ) {}

    /**
     * @return array{
     *     kode: string,
     *     nama: string,
     *     tahun: int,
     *     kuota: int,
     *     maksHari: int|null,
     *     terpakai: int,
     *     sisaKuota: int,
     *     tabungan: list<array{tahun_sumber: int, saldo: int, terpakai: int, sisa: int}>,
     *     totalTersedia: int,
     *     maksBawaTahunDepan: int,
     *     kuotaHangus: bool,
     *     memenuhiSyarat: bool,
     *     alasanTidakMemenuhi: string|null
     * }
     */
    public function __invoke(Pegawai $pegawai, Cuti $cuti, int $tahun): array
    {
        $terpakai = ($this->hitungPemakaian)($pegawai, $cuti, $tahun);
        $kuotaHangus = $this->cutiBersalinTahun($pegawai, $tahun);
        $kuota = max(0, (int) $cuti->kuota);
        $kuotaEfektif = $kuotaHangus ? 0 : $kuota;
        $maksBawa = max(0, (int) $cuti->maks_bawa);

        $saldoTabungan = SaldoCuti::query()
            ->where('id_pegawai', $pegawai->id)
            ->where('id_cuti', $cuti->id)
            ->whereBetween('tahun_sumber', [$tahun - 2, $tahun - 1])
            ->orderBy('tahun_sumber')
            ->get()
            ->map(fn (SaldoCuti $saldo): array => [
                'tahun_sumber' => (int) $saldo->tahun_sumber,
                'saldo' => min(max(0, (int) $saldo->saldo), $maksBawa),
            ])
            ->filter(fn (array $saldo): bool => $saldo['saldo'] > 0)
            ->values();

        $sisaPemakaian = $terpakai;
        $tabungan = [];

        foreach ($saldoTabungan as $saldo) {
            $dipakai = min($saldo['saldo'], $sisaPemakaian);
            $sisaPemakaian -= $dipakai;

            $tabungan[] = [
                'tahun_sumber' => $saldo['tahun_sumber'],
                'saldo' => $saldo['saldo'],
                'terpakai' => $dipakai,
                'sisa' => $saldo['saldo'] - $dipakai,
            ];
        }

        $sisaKuota = max(0, $kuotaEfektif - $sisaPemakaian);
        $totalTersedia = array_sum(array_column($tabungan, 'sisa')) + $sisaKuota;

        return [
            'kode' => $cuti->kode,
            'nama' => $cuti->nama,
            'tahun' => $tahun,
            'kuota' => $kuota,
            'maksHari' => $cuti->maks_hari === null ? null : (int) $cuti->maks_hari,
            'terpakai' => $terpakai,
            'sisaKuota' => $sisaKuota,
            'tabungan' => $tabungan,
            'totalTersedia' => $totalTersedia,
            'maksBawaTahunDepan' => min($sisaKuota, $maksBawa),
            'kuotaHangus' => $kuotaHangus,
            'memenuhiSyarat' => true,
            'alasanTidakMemenuhi' => null,
        ];
    }

    /**
     * Cuti Bersalin dimulai pada tahun itu → kuota tahunan hangus.
     * Menggunakan tanggal_mulai pengajuan yang approved, bukan tanggal
     * presensi, supaya cuti yang melewati pergantian tahun tidak menghanguskan
     * dua tahun sekaligus.
     */
    private function cutiBersalinTahun(Pegawai $pegawai, int $tahun): bool
    {
        $cutiBersalin = Cuti::query()
            ->where('kode', Cuti::KODE_BERSALIN)
            ->first();

        if ($cutiBersalin === null) {
            return false;
        }

        return Pengajuan::query()
            ->where('id_pegawai', $pegawai->id)
            ->where('jenis', 'cuti')
            ->where('status', 'approved')
            ->where('id_cuti', $cutiBersalin->id)
            ->whereBetween('tanggal_mulai', ["{$tahun}-01-01", "{$tahun}-12-31"])
            ->exists();
    }
}
