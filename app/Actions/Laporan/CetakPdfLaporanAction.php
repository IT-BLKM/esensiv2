<?php

namespace App\Actions\Laporan;

use App\Models\Pegawai;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\File;

/**
 * Render laporan presensi ke PDF dengan Dompdf.
 *
 * Foto hanya dimuat untuk laporan per pegawai. File foto tetap berasal dari
 * disk privat dan dikirim ke PDF sebagai data URI, bukan URL publik.
 */
class CetakPdfLaporanAction
{
    /**
     * @param  array{labelPeriode: string, baris: list<array<string, mixed>>, ringkasan: array<string, mixed>}  $data
     * @param  array<int, array{masuk: string|null, keluar: string|null}>  $foto
     */
    public function pegawai(array $data, Pegawai $pegawai, array $foto): string
    {
        $baris = array_map(function (array $item) use ($foto): array {
            $gambar = $foto[$item['id']] ?? ['masuk' => null, 'keluar' => null];

            return [
                ...$item,
                'fotoMasuk' => $gambar['masuk'],
                'fotoKeluar' => $gambar['keluar'],
                'keterangan' => $this->keteranganTerlambat($item),
            ];
        }, $data['baris']);

        return $this->render('admin.laporan-pdf-pegawai', [
            'data' => $data,
            'pegawai' => $pegawai,
            'baris' => $baris,
            'logo' => $this->logo(),
            'namaKantor' => (string) config('presensi.nama_kantor'),
            'alamatKantor' => (string) config('presensi.alamat_kantor'),
        ], 'portrait');
    }

    /**
     * @param  array{labelPeriode: string, hari: list<int>, baris: list<array<string, mixed>>}  $data
     */
    public function semua(array $data): string
    {
        return $this->render('admin.laporan-pdf-semua', [
            'data' => $data,
            'logo' => $this->logo(),
            'namaKantor' => (string) config('presensi.nama_kantor'),
            'alamatKantor' => (string) config('presensi.alamat_kantor'),
        ], 'landscape');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function render(string $view, array $data, string $orientasi): string
    {
        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view($view, $data)->render(), 'UTF-8');
        $dompdf->setPaper('A4', $orientasi);
        $dompdf->render();

        return (string) $dompdf->output();
    }

    private function logo(): ?string
    {
        $path = public_path('images/logo-esensi-transparan.png');

        if (! File::exists($path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode((string) File::get($path));
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function keteranganTerlambat(array $item): string
    {
        if ($item['status'] !== 'hadir') {
            return $item['statusLabel'];
        }

        if ($item['menitTerlambat'] <= 0) {
            return 'Tepat Waktu';
        }

        $penggantian = $item['penggantian'] ?? null;

        if ($penggantian !== null && $penggantian['terpenuhi']) {
            return "Terlambat {$item['menitTerlambat']} menit (diganti)";
        }

        if ($penggantian !== null && ! $penggantian['bisaDiganti']) {
            return "Terlambat {$item['menitTerlambat']} menit (tidak bisa diganti)";
        }

        return "Terlambat {$item['menitTerlambat']} menit";
    }
}
