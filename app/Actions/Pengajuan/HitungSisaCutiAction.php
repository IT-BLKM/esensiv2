<?php

namespace App\Actions\Pengajuan;

use App\Models\Cuti;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\SaldoCuti;
use Carbon\CarbonImmutable;

/**
 * Sisa kuota cuti seorang pegawai untuk satu jenis cuti dan tahun kalender.
 *
 * Ini adalah implementasi awal yang dipakai form pengajuan (#36). Aturan PNS
 * lengkap (FIFO dan command cuti:rollover-tahunan) dapat mengganti isi action
 * ini tanpa mengubah kontraknya.
 */
class HitungSisaCutiAction
{
    public function __construct(private readonly HitungHariKerjaAction $hitungHariKerja) {}

    /**
     * Return null bila jenis cuti memang tidak punya kuota (mis. alasan penting).
     */
    public function __invoke(Pegawai $pegawai, Cuti $cuti, int $tahun): ?int
    {
        if ($cuti->kuota === null) {
            return null;
        }

        $tabungan = (int) SaldoCuti::query()
            ->where('id_pegawai', $pegawai->id)
            ->where('id_cuti', $cuti->id)
            ->whereBetween('tahun_sumber', [$tahun - 2, $tahun - 1])
            ->sum('saldo');

        return max(0, (int) $cuti->kuota + $tabungan - $this->terpakai($pegawai, $cuti, $tahun));
    }

    /**
     * Hari kerja cuti yang sudah disetujui pada tahun berjalan. Pengajuan
     * pending belum mengonsumsi kuota, sedangkan baris presensi cuti muncul
     * setelah approval (#39), jadi sumbernya disini adalah pengajuan approved.
     */
    private function terpakai(Pegawai $pegawai, Cuti $cuti, int $tahun): int
    {
        $awalTahun = CarbonImmutable::create($tahun, 1, 1)->startOfDay();
        $akhirTahun = CarbonImmutable::create($tahun, 12, 31)->endOfDay();

        $daftar = Pengajuan::query()
            ->where('id_pegawai', $pegawai->id)
            ->where('id_cuti', $cuti->id)
            ->where('status', 'approved')
            ->where('tanggal_mulai', '<=', $akhirTahun->toDateString())
            ->where('tanggal_berakhir', '>=', $awalTahun->toDateString())
            ->get(['tanggal_mulai', 'tanggal_berakhir']);

        $total = 0;

        foreach ($daftar as $pengajuan) {
            $mulai = CarbonImmutable::parse((string) $pengajuan->tanggal_mulai)->max($awalTahun);
            $selesai = CarbonImmutable::parse((string) $pengajuan->tanggal_berakhir)->min($akhirTahun);

            $total += ($this->hitungHariKerja)($pegawai, $mulai, $selesai);
        }

        return $total;
    }
}
