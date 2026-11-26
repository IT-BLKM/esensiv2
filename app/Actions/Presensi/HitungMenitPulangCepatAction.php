<?php

namespace App\Actions\Presensi;

use App\Models\JamKerja;
use App\Models\Pengajuan;
use App\Models\Presensi;
use Illuminate\Support\Carbon;

/**
 * Satu-satunya tempat menghitung menit pulang sebelum waktu (PSW) satu baris
 * presensi untuk laporan.
 *
 * Berbeda dari potongan tunjangan, laporan hanya mencatat PSW yang benar-benar
 * bisa dibuktikan dari jam keluar. Baris tanpa jam keluar tidak dianggap PSW
 * karena laporan tidak boleh menebak.
 */
class HitungMenitPulangCepatAction
{
    /**
     * Menit pulang sebelum waktu. Nol berarti tidak pulang cepat atau tidak
     * bisa dihitung (bukan hadir, tanpa jam keluar, tanpa shift, shift yang
     * dikecualikan, atau dinas luar setengah hari).
     *
     * @param  list<string>|null  $kodeDiabaikan  Default: ambil dari config/presensi.php
     */
    public function __invoke(Presensi $presensi, ?array $kodeDiabaikan = null): int
    {
        if ($presensi->status !== 'hadir' || $presensi->jam_keluar === null) {
            return 0;
        }

        $shift = $presensi->jamKerja;

        if (! $shift instanceof JamKerja || $shift->jam_pulang === null || $shift->jam_pulang === '') {
            return 0;
        }

        $kodeDiabaikan ??= $this->kodeJamKerjaDiabaikan();

        if (in_array($shift->kode, $kodeDiabaikan, true)) {
            return 0;
        }

        $pengajuan = $presensi->pengajuan;

        if (
            $pengajuan instanceof Pengajuan
            && $pengajuan->jenis === 'dl'
            && $pengajuan->mode_dl === Pengajuan::MODE_DL_SETENGAH
        ) {
            return 0;
        }

        $batasPulang = Carbon::parse($shift->jam_pulang);
        $jamKeluar = Carbon::parse($presensi->jam_keluar);

        if ($jamKeluar->gte($batasPulang)) {
            return 0;
        }

        // Dibulatkan ke atas: pulang 30 detik lebih awal tetap tercatat 1 menit.
        return (int) ceil(($batasPulang->getTimestamp() - $jamKeluar->getTimestamp()) / 60);
    }

    /**
     * @return list<string>
     */
    private function kodeJamKerjaDiabaikan(): array
    {
        $kode = config('presensi.kode_jam_kerja_diabaikan', []);

        return is_array($kode) ? array_values($kode) : [];
    }
}
