<?php

namespace App\Actions\Laporan;

/**
 * Susun kolom "Keterangan" laporan presensi dari satu baris LaporanBulananAction.
 *
 * Keterlambatan yang sudah diganti tetap tampil sebagai keterlambatan dengan
 * catatan "(diganti)"; penghapusan potongan hanya terjadi di perhitungan tukin.
 */
class KeteranganPresensiAction
{
    /**
     * @param  array<string, mixed>  $item
     */
    public function __invoke(array $item): string
    {
        if (($item['status'] ?? null) !== 'hadir') {
            return (string) ($item['statusLabel'] ?? '-');
        }

        $bagian = [];
        $klasifikasi = $item['klasifikasi'] ?? null;
        $klasifikasi = is_array($klasifikasi) ? $klasifikasi : [];
        $menitTerlambat = (int) ($item['menitTerlambat'] ?? 0);

        if ($menitTerlambat > 0) {
            $teks = "Terlambat {$menitTerlambat} menit";
            $penggantian = $item['penggantian'] ?? null;
            $levelTl = $klasifikasi['levelTl'] ?? null;

            if (($klasifikasi['terlambatDiganti'] ?? false) === true
                || (is_array($penggantian) && ($penggantian['terpenuhi'] ?? false) === true)) {
                $teks .= ' (diganti)';
            } elseif (is_string($levelTl)) {
                $teks .= " ({$levelTl})";
            } elseif (is_array($penggantian) && ($penggantian['bisaDiganti'] ?? true) === false) {
                $teks .= ' (tidak bisa diganti)';
            }

            $bagian[] = $teks;
        }

        $menitPulangCepat = (int) ($item['menitPulangCepat'] ?? 0);

        if ($menitPulangCepat > 0) {
            $levelPsw = $klasifikasi['levelPsw'] ?? null;
            $bagian[] = "Pulang cepat {$menitPulangCepat} menit"
                .(is_string($levelPsw) ? " ({$levelPsw})" : '');
        }

        return $bagian === [] ? 'Tepat Waktu' : implode('; ', $bagian);
    }
}
