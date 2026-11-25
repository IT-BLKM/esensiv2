<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| ETL Fixtures (issue #56)
|--------------------------------------------------------------------------
|
| Dump SQL mini yang meniru struktur + data kotor sistem lama — lihat
| docs/etl-runbook.md §3. Tanggal dibuat relatif terhadap hari ini supaya
| selalu masuk jendela 12 bulan dan test tidak berubah hasil di masa depan.
|
*/

function sqlDumpLama(): string
{
    $nipA = '199302032022032001';
    $nipB = '198009082003122003';
    $nipAsing = '196506101989011001';

    $d1 = now()->subDays(30)->toDateString();
    $rabu = now()->next('Wednesday')->toDateString();
    $rabu2 = now()->next('Wednesday')->addDays(7)->toDateString();
    $rabu3 = now()->next('Wednesday')->addDays(14)->toDateString();
    $rabu4 = now()->next('Wednesday')->addDays(21)->toDateString();
    $minggu = now()->next('Sunday')->toDateString();
    $luarJendela = now()->subDays(400)->toDateString();

    // Rentang 8 hari kerja (Rab–Jum minggu 1 + Sen–Jum minggu 2) di tahun lalu
    $saldoDari = now()->subYears(1)->month(6)->day(15)->next('Wednesday');
    $saldoSampai = $saldoDari->copy()->addDays(11);

    // Hash bcrypt lama (password: rahasia-lama-a) — tidak boleh diimpor; ETL memakai default 123456.
    $hashLamaA = '$2y$12$XTo1gYRUFkatznocRZvpieFjFfedgD72oJlPQXJaWYD5b/A/knd.2';

    return <<<SQL
INSERT INTO `departemen` (`kode_dept`, `nama_dept`) VALUES
('D001', 'Umum');

INSERT INTO `jam_kerja` (`kode_jam_kerja`, `nama_jam_kerja`, `awal_jam_masuk`, `jam_masuk`, `akhir_jam_masuk`, `jam_pulang`) VALUES
('JK01', 'Pagi', '07:30:00', '08:00:00', '08:30:00', '17:00:00'),
('JK02', 'Siang', '13:00:00', '13:30:00', '14:00:00', '22:00:00');

INSERT INTO `master_cuti` (`kode_cutii`, `nama_cuti`, `jml_hari`) VALUES
('C01', 'Cuti Tahunan', 24),
('C02', 'Cuti Bersalin', 90),
('C03', 'Cuti Alasan Penting', 30),
('C04', 'Cuti Besar', 90);

INSERT INTO `pegawai` (`nip`, `nama_lengkap`, `jabatan`, `foto`, `kode_dept`, `no_hp`, `password`, `remember_token`) VALUES
('{$nipA}', 'Budi Santoso', 'Staf', 'budi.jpg', 'D001', '08123456789', '{$hashLamaA}', NULL),
('{$nipB}', 'Ani Wijaya', 'Staf', 'null', 'D001', '08123456780', NULL, NULL);

INSERT INTO `users` (`id`, `name`, `email`, `password`) VALUES
(1, 'Admin Lama', 'admin.lama@presensi.test', 'hash-lama-yang-tidak-boleh-diimpor');

INSERT INTO `konfigurasi_jamkerja` (`nip`, `hari`, `kode_jam_kerja`) VALUES
('{$nipA}', 'Senin', 'JK01'),
('{$nipA}', 'Selasa', 'JK01'),
('{$nipA}', 'Rabu', 'JK01'),
('{$nipA}', 'Kamis', 'JK01'),
('{$nipA}', 'Jumat', 'JK01'),
('{$nipA}', 'Senin', 'JK01'),
('{$nipAsing}', 'Senin', 'JK01');

INSERT INTO `pengajuan_cuti` (`kode_cuti`, `nip`, `tgl_cuti_dari`, `tgl_cuti_sampai`, `status`, `kode_cutii`, `keterangan`, `doc_sid`, `status_approved`) VALUES
('CU-A-1', '{$nipA}', '{$rabu}', '{$rabu}', 'c', 'C01', 'Cuti tahunan', NULL, '1'),
('DL-B-1', '{$nipB}', '{$rabu}', '{$rabu}', 'd', NULL, 'Dinas luar', NULL, '1'),
('DL-A-1', '{$nipA}', '{$rabu3}', '{$rabu3}', 'd', NULL, 'Dinas luar kantor', NULL, '1'),
('SA-A-1', '{$nipA}', '{$minggu}', '{$minggu}', 's', NULL, 'Sakit', 'SAKIT.jpg', '1'),
('CU-A-2', '{$nipA}', '{$rabu4}', '{$rabu4}', 'c', 'C01', 'Menunggu', NULL, '0'),
('SA-A-2', '{$nipB}', '{$rabu2}', '{$rabu2}', 's', NULL, 'Sakit sama', NULL, '1'),
('SA-A-3', '{$nipB}', '{$rabu2}', '{$rabu2}', 's', NULL, 'Sakit sama', NULL, '1'),
('CU-SALDO', '{$nipA}', '{$saldoDari->toDateString()}', '{$saldoSampai->toDateString()}', 'c', 'C01', 'Tabungan lama', NULL, '1');

INSERT INTO `presensi` (`id`, `nip`, `tgl_presensi`, `jam_in`, `jam_out`, `foto_in`, `foto_out`, `lokasi_in`, `lokasi_out`, `kode_jam_kerja`, `status`, `kode_cuti`) VALUES
(1, '{$nipA}', '{$d1}', '08:00:00', '17:00:00', '{$nipA}-in.png', '{$nipA}-out.png', '-2.91,104.69', '-2.91,104.69', 'JK01', 'h', NULL),
(2, '{$nipA}', '{$d1}', NULL, '17:00:00', NULL, NULL, NULL, NULL, 'JK01', 'h', NULL),
(3, '{$nipA}', '{$rabu}', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'c', 'CU-A-1'),
(4, '{$nipA}', '{$rabu2}', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'c', 'CU-TIDAK-ADA'),
(5, '{$nipA}', '{$minggu}', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 's', 'SA-A-1'),
(6, '{$nipA}', '{$rabu3}', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'c', 'DL-A-1'),
(7, 'nip', '0000-00-00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'h', NULL),
(8, '999999999999999999', '{$rabu}', '08:00:00', '17:00:00', NULL, NULL, NULL, NULL, 'JK01', 'h', NULL),
(9, '{$nipA}', '{$luarJendela}', '08:00:00', '17:00:00', NULL, NULL, NULL, NULL, 'JK01', 'h', NULL),
(10, '{$nipA}', '{$rabu4}', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'c', 'CU-A-2'),
(11, '', '{$rabu}', '08:00:00', '17:00:00', NULL, NULL, NULL, NULL, 'JK01', 'h', NULL);

INSERT INTO `konfigurasi_lokasi` (`id`, `lokasi_kantor`, `radius`) VALUES
(1, '-2.91380858459444, 104.69505610526285', 25000);
SQL;
}

/**
 * Tulis string SQL ke file sementara DI LUAR repo (guard ETL menolak path di dalam repo).
 */
function tulisDumpLama(string $sql): string
{
    $path = tempnam(sys_get_temp_dir(), 'etl-dump-');
    file_put_contents($path, $sql);

    return $path;
}

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}
