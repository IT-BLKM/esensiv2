<?php

namespace App\Console\Commands;

use App\Actions\Etl\ImportDataLamaAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('presensi:import-lama
    {--path= : Lokasi file dump SQL lama (wajib di luar repo)}
    {--since= : Batas bawah tanggal presensi YYYY-MM-DD, default 365 hari terakhir}
    {--dry-run : Tampilkan rencana + laporan tanpa menulis apa pun}
    {--force : Lewati konfirmasi sebelum eksekusi}')]
#[Description('Impor data lama presensigps ke skema baru — lihat docs/etl-runbook.md (issue #56)')]
class ImportDataLama extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ImportDataLamaAction $action): int
    {
        $path = (string) $this->option('path');

        if ($path === '') {
            $this->error('Option --path wajib diisi. Contoh: --path=~/data/u671765567_presensi.sql');

            return self::FAILURE;
        }

        $nyata = realpath($path);

        if ($nyata === false || ! is_file($nyata)) {
            $this->error("File dump tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        if (str_starts_with($nyata, base_path().DIRECTORY_SEPARATOR)) {
            $this->error('File dump berada di dalam direktori repo. Pindahkan ke luar repo dulu — '
                .'dump berisi data pribadi dan tidak boleh masuk git (lihat docs/etl-runbook.md §7).');

            return self::FAILURE;
        }

        $since = $this->option('since');
        if ($since !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $since) !== 1) {
            $this->error("Option --since harus format YYYY-MM-DD, diterima: {$since}");

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        if (! $dryRun && ! $this->option('force')) {
            $tersedia = $this->confirm(
                sprintf('Tulis data hasil impor ke database "%s"? Pastikan backup sudah dijalankan.', config('database.default')),
                false
            );

            if (! $tersedia) {
                $this->warn('Dibatalkan. Jalankan ulang dengan --dry-run untuk melihat rencana tanpa menulis.');

                return self::FAILURE;
            }
        }

        try {
            $report = $action->run($nyata, $since, $dryRun);
        } catch (Throwable $e) {
            $this->error('ETL gagal: '.$e->getMessage());
            $this->line('Pastikan migration #8–#17 sudah dijalankan (php artisan migrate) dan dump terbaca.');

            return self::FAILURE;
        }

        foreach ($report->barisLaporan() as $baris) {
            $this->line($baris);
        }

        if ($dryRun) {
            $this->newLine();
            $this->info('Dry-run selesai — tidak ada data yang ditulis. Ulangi tanpa --dry-run untuk eksekusi.');
        } else {
            $this->newLine();
            $this->info('Impor selesai. Log baris yang dibuang: storage/logs/etl-import.log');
        }

        return self::SUCCESS;
    }
}
