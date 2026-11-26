<?php

namespace App\Actions\Laporan;

use App\Models\Pegawai;

/**
 * Render laporan presensi ke PDF dengan header Kemenkes.
 *
 * Foto hanya dimuat untuk laporan per pegawai. File foto tetap berasal dari
 * disk privat dan dikirim ke PDF sebagai data URI, bukan URL publik.
 */
class CetakPdfLaporanAction
{
    public function __construct(
        private readonly KeteranganPresensiAction $keterangan,
        private readonly RenderPdfAction $renderPdf,
    ) {}

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
                'keterangan' => ($this->keterangan)($item),
            ];
        }, $data['baris']);

        return $this->renderPdf->render('admin.laporan-pdf-pegawai', [
            'data' => $data,
            'pegawai' => $pegawai,
            'baris' => $baris,
            ...$this->renderPdf->kop(),
        ]);
    }

    /**
     * @param  array{labelPeriode: string, hari: list<int>, baris: list<array<string, mixed>>}  $data
     */
    public function semua(array $data): string
    {
        return $this->renderPdf->render('admin.laporan-pdf-semua', [
            'data' => $data,
            ...$this->renderPdf->kop(),
        ]);
    }
}
