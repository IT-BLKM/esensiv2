<?php

namespace App\Actions\Laporan;

use App\Models\Pegawai;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\ImageCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Options\PageOrientation;
use OpenSpout\Writer\XLSX\Options\PageSetup;
use OpenSpout\Writer\XLSX\Options\PaperSize;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Tulis laporan TUKIN ke XLSX A4 portrait.
 */
class TulisExcelTukinAction
{
    /**
     * @param  array{labelPeriode: string, baris: list<array<string, mixed>>, ringkasan: array<string, mixed>}  $data
     */
    public function semua(array $data): string
    {
        return $this->tulis(function (Writer $writer) use ($data): void {
            $judul = $this->gayaJudul();
            $header = $this->gayaHeader();

            $writer->addRow($this->barisHeader('Rekap Potongan Tunjangan Kinerja Periode '.$data['labelPeriode'], $judul));
            $writer->addRow($this->barisHeader((string) config('presensi.nama_kantor')));
            $writer->addRow($this->barisHeader((string) config('presensi.alamat_kantor')));
            $writer->addRow(Row::fromValues([]));

            $kolom = [
                'No.', 'NIP', 'Nama Pegawai', 'Departemen',
                'TL1', 'TL2', 'TL3', 'PSW1', 'PSW2', 'PSW3', 'PSW4',
                'Hari diganti', 'Total % TL', 'Total % PSW', 'Total %',
                'Koreksi %', 'Total akhir %', 'Nominal', 'Potongan Rp',
            ];
            $writer->addRow(Row::fromValuesWithStyle($kolom, $header));

            foreach ($data['baris'] as $index => $item) {
                $ringkasan = $item['ringkasan'];
                $nilai = [
                    $index + 1,
                    $item['nip'],
                    $item['nama'],
                    $item['departemen'] ?? '-',
                    $ringkasan['jumlahTl1'],
                    $ringkasan['jumlahTl2'],
                    $ringkasan['jumlahTl3'],
                    $ringkasan['jumlahPsw1'],
                    $ringkasan['jumlahPsw2'],
                    $ringkasan['jumlahPsw3'],
                    $ringkasan['jumlahPsw4'],
                    $ringkasan['jumlahPenggantian'],
                    $ringkasan['totalPersenTl'],
                    $ringkasan['totalPersenPsw'],
                    $ringkasan['totalPersen'],
                    $ringkasan['koreksiPersen'],
                    $ringkasan['totalPersenAkhir'],
                    $ringkasan['nominal'] === null ? '-' : $ringkasan['nominal'],
                    $ringkasan['potonganNominal'] === null ? '-' : $ringkasan['potonganNominal'],
                ];

                $writer->addRow($this->barisData($nilai, $ringkasan['totalPersenAkhir'] > 0));
            }

            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValues([
                'Persentase adalah potongan dari tunjangan kinerja, bukan nominal rupiah. '
                .'TL/PSW yang sudah diganti tidak menambah potongan.',
            ]));

            $sheet = $writer->getCurrentSheet();
            $sheet->setColumnWidth(5, 1);
            $sheet->setColumnWidth(20, 2);
            $sheet->setColumnWidth(28, 3);
            $sheet->setColumnWidth(18, 4);
            $sheet->setColumnWidthForRange(6, 5, 15);
            $sheet->setColumnWidth(9, 16);
            $sheet->setColumnWidth(9, 17);
            $sheet->setColumnWidth(14, 18);
            $sheet->setColumnWidth(14, 19);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function pegawai(array $data, Pegawai $pegawai, string $labelPeriode): string
    {
        return $this->tulis(function (Writer $writer) use ($data, $pegawai, $labelPeriode): void {
            $judul = $this->gayaJudul();
            $header = $this->gayaHeader();

            $writer->addRow($this->barisHeader('Laporan Potongan Tunjangan Kinerja Periode '.$labelPeriode, $judul));
            $writer->addRow($this->barisHeader((string) config('presensi.nama_kantor')));
            $writer->addRow($this->barisHeader((string) config('presensi.alamat_kantor')));
            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValues(['NIP', ':', $pegawai->nip]));
            $writer->addRow(Row::fromValues(['Nama', ':', $pegawai->nama]));
            $writer->addRow(Row::fromValues(['Jabatan', ':', $pegawai->jabatan]));
            $unitKerja = $pegawai->departemen;
            $writer->addRow(Row::fromValues([
                'Unit Kerja',
                ':',
                $unitKerja === null ? '-' : (string) $unitKerja->getAttribute('nama'),
            ]));
            $writer->addRow(Row::fromValues([]));

            $kolom = [
                'No.', 'Tanggal', 'Hari',
                'TL', 'Menit TL', '% TL', 'PSW', 'Menit PSW', '% PSW', 'Total %', 'Keterangan',
            ];
            $writer->addRow(Row::fromValuesWithStyle($kolom, $header));

            foreach ($data['baris'] as $index => $item) {
                $nilai = [
                    $index + 1,
                    Carbon::parse((string) $item['tanggal'])->format('d-m-Y'),
                    $item['hari'],
                    $item['levelTl'] ?? '-',
                    $item['menitTerlambat'] ?? '-',
                    $item['persenTl'],
                    $item['levelPsw'] ?? '-',
                    $item['menitPulangCepat'] ?? '-',
                    $item['persenPsw'],
                    $item['persenTotal'],
                    $item['catatanPenggantian'] ?? '-',
                ];

                $writer->addRow($this->barisData($nilai, $item['persenTotal'] > 0, [0, 1, 2, 10]));
            }

            $ringkasan = $data['ringkasan'];
            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValuesWithStyle(['Rekap'], $judul));
            $writer->addRow(Row::fromValues([
                "TL1: {$ringkasan['jumlahTl1']}, TL2: {$ringkasan['jumlahTl2']}, TL3: {$ringkasan['jumlahTl3']}",
            ]));
            $writer->addRow(Row::fromValues([
                "PSW1: {$ringkasan['jumlahPsw1']}, PSW2: {$ringkasan['jumlahPsw2']}, "
                ."PSW3: {$ringkasan['jumlahPsw3']}, PSW4: {$ringkasan['jumlahPsw4']}",
            ]));
            $writer->addRow(Row::fromValues([
                "Hari diganti: {$ringkasan['jumlahPenggantian']}, "
                ."Total % TL: {$ringkasan['totalPersenTl']}, "
                ."Total % PSW: {$ringkasan['totalPersenPsw']}, "
                ."Total sebelum koreksi: {$ringkasan['totalPersen']}",
            ]));
            $writer->addRow(Row::fromValues([
                "Koreksi: {$ringkasan['koreksiPersen']}%, "
                ."Total akhir: {$ringkasan['totalPersenAkhir']}%, "
                .'Nominal: '.($ringkasan['nominal'] === null ? 'belum diatur' : $ringkasan['nominal']).', '
                .'Potongan: '.($ringkasan['potonganNominal'] === null ? '-' : $ringkasan['potonganNominal']),
            ]));

            $sheet = $writer->getCurrentSheet();
            $sheet->setColumnWidth(5, 1);
            $sheet->setColumnWidth(12, 2);
            $sheet->setColumnWidth(10, 3);
            $sheet->setColumnWidth(8, 4);
            $sheet->setColumnWidth(9, 5);
            $sheet->setColumnWidth(8, 6);
            $sheet->setColumnWidth(8, 7);
            $sheet->setColumnWidth(9, 8);
            $sheet->setColumnWidth(8, 9);
            $sheet->setColumnWidth(8, 10);
            $sheet->setColumnWidth(28, 11);
        });
    }

    /**
     * @param  callable(Writer): void  $isi
     */
    private function tulis(callable $isi): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'laporan-tukin-');
        $writer = new Writer(new Options(
            pageSetup: new PageSetup(PageOrientation::PORTRAIT, PaperSize::A4, null, 1),
        ));
        $writer->openToFile($path);

        try {
            $isi($writer);
        } finally {
            $writer->close();
        }

        return $path;
    }

    private function barisHeader(string $teks, ?Style $gaya = null): Row
    {
        $cells = [$this->logoHeader() ?? Cell::fromValue(''), Cell::fromValue($teks, $gaya)];

        return new Row($cells, 34);
    }

    private function logoHeader(): ?ImageCell
    {
        $path = public_path('images/logo-kemenkes.png');

        return File::exists($path) ? new ImageCell($path, 40, 36) : null;
    }

    /**
     * @param  list<int|float|string|null>  $nilai
     * @param  list<int>  $kolomKiri
     */
    private function barisData(array $nilai, bool $merah, array $kolomKiri = [0, 1, 2, 3]): Row
    {
        $styles = [];

        foreach (array_keys($nilai) as $kolom) {
            $styles[$kolom] = in_array($kolom, $kolomKiri, true)
                ? $this->gayaTeksKiri($merah)
                : $this->gayaAngka($merah);
        }

        return Row::fromValuesWithStyles($nilai, $styles, 20);
    }

    private function gayaJudul(): Style
    {
        return (new Style)->withFontBold(true)->withFontSize(14);
    }

    private function gayaHeader(): Style
    {
        return (new Style)
            ->withFontBold(true)
            ->withBackgroundColor(Color::rgb(219, 219, 219))
            ->withCellAlignment(CellAlignment::CENTER)
            ->withCellVerticalAlignment(CellVerticalAlignment::CENTER)
            ->withShouldWrapText(true);
    }

    private function gayaTeksKiri(bool $merah = false): Style
    {
        $style = (new Style)
            ->withFontSize(8)
            ->withCellAlignment(CellAlignment::LEFT)
            ->withCellVerticalAlignment(CellVerticalAlignment::CENTER);

        return $merah ? $style->withFontColor(Color::rgb(153, 27, 27)) : $style;
    }

    private function gayaAngka(bool $merah = false): Style
    {
        $style = (new Style)
            ->withFontSize(8)
            ->withCellAlignment(CellAlignment::CENTER)
            ->withCellVerticalAlignment(CellVerticalAlignment::CENTER);

        return $merah
            ? $style
                ->withFontColor(Color::rgb(153, 27, 27))
                ->withBackgroundColor(Color::rgb(254, 226, 226))
            : $style;
    }
}
