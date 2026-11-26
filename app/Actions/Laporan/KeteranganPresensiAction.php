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
        $menitTerlambat = (int) ($item['menitTerlambat'] ?? 0);

        if ($menitTerlambat > 0) {
            $teks = "Terlambat {$menitTerlambat} menit";
            $penggantian = $item['penggantian'] ?? null;

            if (is_array($penggantian) && ($penggantian['terpenuhi'] ?? false) === true) {
                $teks .= ' (diganti)';
            } elseif (is_array($penggantian) && ($penggantian['bisaDiganti'] ?? true) === false) {
                $teks .= ' (tidak bisa diganti)';
            }

            $bagian[] = $teks;
        }

        $menitPulangCepat = (int) ($item['menitPulangCepat'] ?? 0);

        if ($menitPulangCepat > 0) {
            $bagian[] = "Pulang cepat {$menitPulangCepat} menit";
        }

        return $bagian === [] ? 'Tepat Waktu' : implode('; ', $bagian);
    }
}
