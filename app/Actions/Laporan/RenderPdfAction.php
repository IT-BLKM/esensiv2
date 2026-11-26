<?php

namespace App\Actions\Laporan;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\File;

/**
 * Render dokumen PDF A4 dari Blade untuk laporan presensi dan TUKIN.
 *
 * Header laporan memakai logo Kemenkes yang sama. Batas memori dinaikkan
 * karena matriks presensi 50 pegawai x 31 hari membutuhkan >100 MB.
 */
class RenderPdfAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function render(string $view, array $data, string $orientasi = 'portrait'): string
    {
        ini_set('memory_limit', '512M');

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

    /**
     * @return array{logo: string|null, namaKantor: string, alamatKantor: string}
     */
    public function kop(): array
    {
        return [
            'logo' => $this->logo(),
            'namaKantor' => (string) config('presensi.nama_kantor'),
            'alamatKantor' => (string) config('presensi.alamat_kantor'),
        ];
    }

    private function logo(): ?string
    {
        $path = public_path('images/logo-kemenkes.png');

        if (! File::exists($path)) {
            $path = public_path('images/logo-esensi-transparan.png');
        }

        if (! File::exists($path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode((string) File::get($path));
    }
}
