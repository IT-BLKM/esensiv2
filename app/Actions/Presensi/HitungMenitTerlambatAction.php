<?php

namespace App\Actions\Presensi;

use App\Models\Pengajuan;
use App\Models\Presensi;
use Illuminate\Support\Carbon;

/**
 * Satu-satunya tempat menghitung menit keterlambatan satu baris presensi.
 *
 * Dipakai dashboard (#34), histori pegawai (#33), dan nanti rekap semua
 * pegawai (#44) supaya angkanya tidak pernah berbeda antar halaman.
 */
class HitungMenitTerlambatAction
{
    /**
     * Menit keterlambatan satu baris presensi. Nol berarti tidak terlambat
     * atau tidak bisa dihitung (bukan hadir, tanpa jam masuk, tanpa shift,
     * atau shift yang dikecualikan lewat config).
     *
     * @param  list<string>|null  $kodeDiabaikan  Default: ambil dari config/presensi.php
     */
    public function __invoke(Presensi $presensi, ?array $kodeDiabaikan = null): int
    {
        if ($presensi->status !== 'hadir' || $presensi->jam_masuk === null) {
            return 0;
        }

        $kodeDiabaikan ??= $this->kodeJamKerjaDiabaikan();
        $shift = $presensi->jamKerja;

        if ($shift === null || in_array($shift->kode, $kodeDiabaikan, true)) {
            return 0;
        }

        $pengajuan = $presensi->pengajuan;

        if (
            $pengajuan !== null
            && $pengajuan->jenis === 'dl'
            && $pengajuan->mode_dl === Pengajuan::MODE_DL_SETENGAH
        ) {
            return 0;
        }

        $batasMasuk = Carbon::parse($shift->jam_masuk);
        $jamMasuk = Carbon::parse($presensi->jam_masuk);
        $selisihDetik = $jamMasuk->getTimestamp() - $batasMasuk->getTimestamp();

        if ($selisihDetik <= 0) {
            return 0;
        }

        // Dibulatkan ke atas: terlambat 30 detik tetap tercatat 1 menit,
        // supaya jumlah hari terlambat dan total menit tidak saling bertentangan.
        return (int) ceil($selisihDetik / 60);
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
