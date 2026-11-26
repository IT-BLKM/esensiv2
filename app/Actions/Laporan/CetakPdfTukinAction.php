<?php

namespace App\Actions\Laporan;

use App\Models\Pegawai;

/**
 * Render laporan TUKIN ke PDF A4 portrait dengan header Kemenkes.
 */
class CetakPdfTukinAction
{
    public function __construct(
        private readonly RenderPdfAction $renderPdf,
    ) {}

    /**
     * @param  array{labelPeriode: string, baris: list<array<string, mixed>>, ringkasan: array<string, mixed>}  $data
     */
    public function semua(array $data): string
    {
        return $this->renderPdf->render('admin.tukin-pdf-semua', [
            'data' => $data,
            ...$this->renderPdf->kop(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function pegawai(array $data, Pegawai $pegawai, string $labelPeriode): string
    {
        return $this->renderPdf->render('admin.tukin-pdf-pegawai', [
            'data' => $data,
            'pegawai' => $pegawai,
            'labelPeriode' => $labelPeriode,
            ...$this->renderPdf->kop(),
        ]);
    }
}
