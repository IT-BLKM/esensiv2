<?php

namespace App\Actions\Laporan;

use App\Models\Pegawai;

class CetakSlipTukinAction
{
    public function __construct(
        private readonly RenderPdfAction $renderPdf,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(array $data, Pegawai $pegawai, string $labelPeriode): string
    {
        return $this->renderPdf->render('admin.tukin-slip-pdf', [
            'data' => $data,
            'pegawai' => $pegawai,
            'labelPeriode' => $labelPeriode,
            ...$this->renderPdf->kop(),
        ]);
    }
}
