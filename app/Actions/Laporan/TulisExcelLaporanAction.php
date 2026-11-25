<?php

namespace App\Actions\Laporan;

use App\Models\Pegawai;
use Carbon\Carbon;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Tulis laporan presensi ke XLSX memakai OpenSpout.
 *
 * Dua bentuk: laporan harian satu pegawai dan matriks semua pegawai.
 * File ditulis ke temporary path; controller mengirimnya sebagai download
 * lalu menghapus file.
 */
class TulisExcelLaporanAction
{
    /**
     * @param  array{labelPeriode: string, baris: list<array<string, mixed>>, ringkasan: array<string, mixed>}  $data
     */
    public function pegawai(array $data, Pegawai $pegawai): string
    {
        return $this->tulis(function (Writer $writer) use ($data, $pegawai): void {
            $judul = $this->gayaJudul();
            $header = $this->gayaHeader();

            $writer->addRow(Row::fromValuesWithStyle(['Laporan Presensi Pegawai'], $judul));
            $writer->addRow(Row::fromValues([config('presensi.nama_kantor')]));
            $writer->addRow(Row::fromValues([config('presensi.alamat_kantor')]));
            $writer->addRow(Row::fromValues(['Periode', $data['labelPeriode']]));
            $writer->addRow(Row::fromValues(['NIP', $pegawai->nip]));
            $writer->addRow(Row::fromValues(['Nama', $pegawai->nama]));
            $writer->addRow(Row::fromValues(['Jabatan', $pegawai->jabatan]));
            $writer->addRow(Row::fromValues(['Unit Kerja', $pegawai->departemen?->nama ?? '-']));
            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValuesWithStyle([
                'No.',
                'Tanggal',
                'Hari',
                'Shift',
                'Jam Masuk',
                'Jam Pulang',
                'Status',
                'Keterlambatan',
                'Jml Jam',
            ], $header));

            foreach ($data['baris'] as $index => $item) {
                $writer->addRow(Row::fromValues([
                    $index + 1,
                    Carbon::parse((string) $item['tanggal'])->format('d-m-Y'),
                    $item['hari'],
                    $item['shift'] ?? '-',
                    $item['jamMasuk'] ?? '-',
                    $item['jamKeluar'] ?? '-',
                    $item['statusLabel'],
                    $this->keteranganTerlambat($item),
                    $item['jmlJam'],
                ]));
            }

            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValuesWithStyle(['Rekap'], $judul));
            $writer->addRow(Row::fromValues(['Hadir (tepat waktu)', $data['ringkasan']['tepatWaktu']]));
            $writer->addRow(Row::fromValues(['Terlambat', $data['ringkasan']['terlambat']]));
            $writer->addRow(Row::fromValues(['Cuti', $data['ringkasan']['cuti']]));
            $writer->addRow(Row::fromValues(['Sakit', $data['ringkasan']['sakit']]));
            $writer->addRow(Row::fromValues(['Dinas Luar', $data['ringkasan']['dl']]));
            $writer->addRow(Row::fromValues(['Total menit terlambat', $data['ringkasan']['totalMenitTerlambat']]));
        });
    }

    /**
     * @param  array{labelPeriode: string, hari: list<int>, baris: list<array<string, mixed>>}  $data
     */
    public function semua(array $data): string
    {
        return $this->tulis(function (Writer $writer) use ($data): void {
            $judul = $this->gayaJudul();
            $header = $this->gayaHeader();

            $writer->addRow(Row::fromValuesWithStyle(['Rekap Presensi Semua Pegawai'], $judul));
            $writer->addRow(Row::fromValues([config('presensi.nama_kantor')]));
            $writer->addRow(Row::fromValues(['Periode', $data['labelPeriode']]));
            $writer->addRow(Row::fromValues([]));

            $kolom = ['NIP', 'Nama Pegawai', ...array_map('strval', $data['hari']), 'H', 'DL', 'C', 'S', 'A'];
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

                $writer->addRow(Row::fromValues($nilai));
            }
        });
    }

    /**
     * @param  callable(Writer): void  $isi
     */
    private function tulis(callable $isi): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'laporan-presensi-');
        $writer = new Writer;
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
