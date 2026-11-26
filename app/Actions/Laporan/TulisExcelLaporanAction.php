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
 * Tulis laporan presensi ke XLSX memakai OpenSpout.
 *
 * Dua bentuk: laporan harian satu pegawai dan matriks semua pegawai, keduanya
 * A4 portrait. Page setup disamakan dengan lampiran QA supaya file bisa
 * langsung dicetak tanpa mengatur ulang orientasi/kertas.
 */
class TulisExcelLaporanAction
{
    public function __construct(
        private readonly KeteranganPresensiAction $keterangan,
    ) {}

    /**
     * @param  array{labelPeriode: string, baris: list<array<string, mixed>>, ringkasan: array<string, mixed>}  $data
     */
    public function pegawai(array $data, Pegawai $pegawai): string
    {
        return $this->tulis(function (Writer $writer) use ($data, $pegawai): void {
            $judul = $this->gayaJudul();
            $header = $this->gayaHeader();

            $writer->addRow($this->barisHeader('Laporan Presensi Pegawai Periode '.$data['labelPeriode'], $judul));
            $writer->addRow($this->barisHeader((string) config('presensi.nama_kantor')));
            $writer->addRow($this->barisHeader((string) config('presensi.alamat_kantor')));
            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValues(['NIP', ':', $pegawai->nip]));
            $writer->addRow(Row::fromValues(['Nama', ':', $pegawai->nama]));
            $writer->addRow(Row::fromValues(['Jabatan', ':', $pegawai->jabatan]));
            $writer->addRow(Row::fromValues(['Unit Kerja', ':', $pegawai->departemen?->nama ?? '-']));
            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValuesWithStyle([
                'No.',
                'Tanggal',
                'Jam Masuk',
                'Jam Pulang',
                'Status',
                'Keterangan',
                'Jml Jam',
            ], $header));

            foreach ($data['baris'] as $index => $item) {
                $nilai = [
                    $index + 1,
                    Carbon::parse((string) $item['tanggal'])->format('d-m-Y'),
                    $item['jamMasuk'] ?? '-',
                    $item['jamKeluar'] ?? '-',
                    $item['kodeHari'],
                    ($this->keterangan)($item),
                    $item['jmlJam'],
                ];

                $merah = $item['menitTerlambat'] > 0 || $item['menitPulangCepat'] > 0;
                $styles = [];

                foreach (array_keys($nilai) as $kolom) {
                    $styles[$kolom] = $kolom === 4
                        ? $this->gayaKodeHari($merah)
                        : $this->gayaTeksKiri($merah);
                }

                $writer->addRow(Row::fromValuesWithStyles($nilai, $styles, 22));
            }

            $sheet = $writer->getCurrentSheet();
            $sheet->setColumnWidth(5, 1);
            $sheet->setColumnWidth(12, 2);
            $sheet->setColumnWidth(11, 3, 4);
            $sheet->setColumnWidth(8, 5);
            $sheet->setColumnWidth(36, 6);
            $sheet->setColumnWidth(8, 7);
        }, PageOrientation::PORTRAIT);
    }

    /**
     * @param  array{labelPeriode: string, hari: list<int>, baris: list<array<string, mixed>>}  $data
     */
    public function semua(array $data): string
    {
        return $this->tulis(function (Writer $writer) use ($data): void {
            $judul = $this->gayaJudul();
            $header = $this->gayaHeader();

            $writer->addRow($this->barisHeader('Laporan Presensi Pegawai Periode '.$data['labelPeriode'], $judul));
            $writer->addRow($this->barisHeader((string) config('presensi.nama_kantor')));
            $writer->addRow($this->barisHeader((string) config('presensi.alamat_kantor')));
            $writer->addRow(Row::fromValues([]));

            $kolom = ['NIP', 'Nama Pegawai', ...array_map('strval', $data['hari']), 'H', 'DL', 'C', 'S', 'A', 'TL', 'PSW'];
            $writer->addRow(Row::fromValuesWithStyle($kolom, $header));

            foreach ($data['baris'] as $item) {
                $nilai = [$item['nip'], $item['nama']];

                foreach ($data['hari'] as $hari) {
                    $nilai[] = $item['sel'][$hari] ?? null;
                }

                $nilai[] = $item['h'];
                $nilai[] = $item['dl'];
                $nilai[] = $item['c'];
                $nilai[] = $item['s'];
                $nilai[] = $item['a'];
                $nilai[] = $item['tl'];
                $nilai[] = $item['psw'];

                $merah = $item['tl'] > 0 || $item['psw'] > 0;
                $styles = [];

                foreach (array_keys($nilai) as $kolom) {
                    $styles[$kolom] = $kolom < 2
                        ? $this->gayaTeksKiri($merah)
                        : $this->gayaKodeHari($merah);
                }

                $writer->addRow(Row::fromValuesWithStyles($nilai, $styles, 22));
            }

            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValues([
                'Kode: H = hadir, H! = hadir terlambat, DL = dinas luar, C = cuti, S = sakit, A = alpa, TL = hari terlambat, PSW = pulang sebelum waktu.',
            ]));

            $sheet = $writer->getCurrentSheet();
            $jumlahHari = count($data['hari']);
            $sheet->setColumnWidth(20, 1);
            $sheet->setColumnWidth(28, 2);
            $sheet->setColumnWidthForRange(4, 3, 2 + $jumlahHari);
            $sheet->setColumnWidthForRange(5, 3 + $jumlahHari, 9 + $jumlahHari);
        }, PageOrientation::PORTRAIT);
    }

    /**
     * @param  callable(Writer): void  $isi
     */
    private function tulis(callable $isi, PageOrientation $orientasi): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'laporan-presensi-');
        $writer = new Writer(new Options(
            pageSetup: new PageSetup($orientasi, PaperSize::A4, null, 1),
        ));
        $writer->openToFile($path);

        try {
            $isi($writer);
        } finally {
            $writer->close();
        }

        return $path;
    }

    private function gayaJudul(): Style
    {
        return (new Style)->withFontBold(true)->withFontSize(14);
    }

    private function gayaHeader(): Style
    {
        return (new Style)
            ->withFontBold(true)
            ->withBackgroundColor(Color::rgb(219, 219, 219));
    }

    private function barisHeader(string $teks, ?Style $gaya = null): Row
    {
        $logo = $this->logoHeader();
        $cells = [$logo ?? Cell::fromValue(''), Cell::fromValue($teks, $gaya)];

        return new Row($cells, 34);
    }

    private function logoHeader(): ?ImageCell
    {
        $path = public_path('images/logo-kemenkes.png');

        if (! File::exists($path)) {
            return null;
        }

        return new ImageCell($path, 40, 36);
    }

    private function gayaTeksKiri(bool $merah = false): Style
    {
        $style = (new Style)
            ->withFontSize(8)
            ->withCellAlignment(CellAlignment::LEFT)
            ->withCellVerticalAlignment(CellVerticalAlignment::CENTER);

        return $merah
            ? $style->withFontColor(Color::rgb(153, 27, 27))
            : $style;
    }

    private function gayaKodeHari(bool $merah = false): Style
    {
        $style = (new Style)
            ->withFontSize(6)
            ->withShouldWrapText(true)
            ->withCellAlignment(CellAlignment::CENTER)
            ->withCellVerticalAlignment(CellVerticalAlignment::CENTER);

        return $merah
            ? $style
                ->withFontColor(Color::rgb(153, 27, 27))
                ->withBackgroundColor(Color::rgb(254, 226, 226))
            : $style;
    }
}
