<?php

namespace App\Services\Etl;

/**
 * Laporan hasil (atau rencana, saat dry-run) ETL impor data lama.
 *
 * Angka di laporan ini adalah pembanding verifikasi untuk QA —
 * bandingkan dengan target di docs/etl-runbook.md bagian 4 dan 8.
 */
class ImportReport
{
    /** @var array<string, array{baru: int, ada: int}> */
    private array $tabel = [];

    /** @var array<string, int> */
    private array $buang = [];

    /** @var list<string> */
    private array $catatan = [];

    /** @var array<int, array{baris: int, hari: int}> */
    private array $saldo = [];

    /**
     * @param  string  $path  lokasi dump yang dibaca
     * @param  string  $sejak  batas bawah tanggal presensi (YYYY-MM-DD)
     * @param  bool  $dryRun  true bila tidak ada yang ditulis
     */
    public function __construct(
        private readonly string $path,
        private readonly string $sejak,
        private readonly bool $dryRun,
    ) {}

    public function dryRun(): bool
    {
        return $this->dryRun;
    }

    /**
     * Catat satu baris tabel: baru (akan/sudah ditulis) atau sudah ada (dilewati).
     */
    public function catatTabel(string $tabel, bool $baru): void
    {
        $this->tabel[$tabel] ??= ['baru' => 0, 'ada' => 0];
        $this->tabel[$tabel][$baru ? 'baru' : 'ada']++;
    }

    /**
     * Catat satu baris yang dibuang beserta alasannya.
     */
    public function catatBuang(string $alasan): void
    {
        $this->buang[$alasan] = ($this->buang[$alasan] ?? 0) + 1;
    }

    public function catatan(string $teks): void
    {
        $this->catatan[] = $teks;
    }

    /**
     * Catat satu baris rencana saldo_cuti (untuk pembanding dry-run).
     */
    public function catatSaldo(int $tahun, int $hari): void
    {
        $this->saldo[$tahun] ??= ['baris' => 0, 'hari' => 0];
        $this->saldo[$tahun]['baris']++;
        $this->saldo[$tahun]['hari'] += $hari;
    }

    /**
     * Jumlah baris (baru / sudah ada) untuk satu tabel — dipakai test.
     */
    public function jumlahTabel(string $tabel, bool $baru): int
    {
        return $this->tabel[$tabel][$baru ? 'baru' : 'ada'] ?? 0;
    }

    /**
     * Jumlah baris dibuang untuk satu alasan — dipakai test.
     */
    public function jumlahBuang(string $alasan): int
    {
        return $this->buang[$alasan] ?? 0;
    }

    public function jumlahSaldoBaris(int $tahun): int
    {
        return $this->saldo[$tahun]['baris'] ?? 0;
    }

    public function jumlahSaldoHari(int $tahun): int
    {
        return $this->saldo[$tahun]['hari'] ?? 0;
    }

    /**
     * Baris-baris laporan siap dicetak oleh command.
     *
     * @return list<string>
     */
    public function barisLaporan(): array
    {
        $baris = [
            'Mode           : '.($this->dryRun ? 'DRY-RUN (tidak ada data yang ditulis)' : 'EKSEKUSI'),
            'Dump           : '.$this->path,
            'Jendela sejak  : '.$this->sejak.' (presensi sebelum tanggal ini tidak diimpor)',
            '',
            sprintf('%-26s %10s %10s', 'Tabel', 'Baris baru', 'Sudah ada'),
            str_repeat('-', 48),
        ];

        foreach ($this->tabel as $tabel => $jumlah) {
            $baris[] = sprintf('%-26s %10d %10d', $tabel, $jumlah['baru'], $jumlah['ada']);
        }

        if ($this->buang !== []) {
            $baris[] = '';
            $baris[] = 'Baris yang dibuang:';
            arsort($this->buang);
            foreach ($this->buang as $alasan => $jumlah) {
                $baris[] = sprintf('  - %-36s %4d', $alasan, $jumlah);
            }
        }

        if ($this->saldo !== []) {
            $baris[] = '';
            $baris[] = 'Rencana init saldo_cuti:';
            krsort($this->saldo);
            foreach ($this->saldo as $tahun => $jumlah) {
                $baris[] = sprintf('  - tahun sumber %d: %d baris, total %d hari', $tahun, $jumlah['baris'], $jumlah['hari']);
            }
        }

        if ($this->catatan !== []) {
            $baris[] = '';
            $baris[] = 'Catatan:';
            foreach ($this->catatan as $catatan) {
                $baris[] = '  - '.$catatan;
            }
        }

        return $baris;
    }
}
