<?php

namespace App\Console\Commands;

use App\Actions\Cuti\RolloverSaldoCutiAction;
use App\Models\Cuti;
use App\Models\Pegawai;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('cuti:rollover-tahunan
    {--tahun= : Tahun berjalan yang dipakai sebagai acuan rollover; default tahun kantor saat ini}')]
#[Description('Rollover tabungan Cuti Tahunan dari tahun sebelumnya (idempotent) — issue #37')]
class RolloverTahunanCuti extends Command
{
    public function handle(RolloverSaldoCutiAction $rollover): int
    {
        $opsiTahun = $this->option('tahun');

        if ($opsiTahun !== null && preg_match('/^\d{4}$/', (string) $opsiTahun) !== 1) {
            $this->error("Option --tahun harus format YYYY, diterima: {$opsiTahun}");

            return self::FAILURE;
        }

        $tahun = $opsiTahun === null
            ? CarbonImmutable::now((string) config('presensi.zona_waktu', 'Asia/Jakarta'))->year
            : (int) $opsiTahun;

        $cuti = Cuti::query()->where('kode', Cuti::KODE_TAHUNAN)->first();

        if ($cuti === null) {
            $this->error('Master Cuti Tahunan (C01) tidak ditemukan. Jalankan seeder/master cuti dulu.');

            return self::FAILURE;
        }

        $dibuat = 0;
        $dilewati = 0;

        Pegawai::query()->chunkById(200, function ($daftarPegawai) use ($rollover, $cuti, $tahun, &$dibuat, &$dilewati): void {
            foreach ($daftarPegawai as $pegawai) {
                $hasil = $rollover($pegawai, $cuti, $tahun);

                if ($hasil === null) {
                    $dilewati++;

                    continue;
                }

                $dibuat++;
            }
        });

        $this->info("Rollover cuti tahunan {$tahun}: {$dibuat} tabungan dibuat, {$dilewati} dilewati.");

        return self::SUCCESS;
    }
}
