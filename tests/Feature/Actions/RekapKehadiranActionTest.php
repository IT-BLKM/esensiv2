<?php

use App\Actions\Laporan\RekapKehadiranAction;
use App\Models\JamKerja;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\Presensi;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
 * Rekap dashboard pegawai (issue #34). Semua angka dihitung di
 * RekapKehadiranAction; test ini mengunci arti tiap keranjang dan sumber
 * batas keterlambatan (shift, bukan angka tetap).
 */
beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-25 09:00:00'));
    config(['presensi.kode_jam_kerja_diabaikan' => ['JK03']]);
});

function shiftUntukRekap(string $kode = 'JK01'): JamKerja
{
    return JamKerja::factory()->create([
        'kode' => $kode,
        'nama' => 'Shift '.$kode,
        'awal_masuk' => '07:00:00',
        'jam_masuk' => '07:30:00',
        'akhir_masuk' => '09:30:00',
        'jam_pulang' => '16:00:00',
    ]);
}

function buatPresensiRekap(
    Pegawai $pegawai,
    string $tanggal,
    string $status = 'hadir',
    ?JamKerja $shift = null,
    ?string $jamMasuk = '07:15:00',
): Presensi {
    return Presensi::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_jam_kerja' => $status === 'hadir' ? $shift?->id : null,
        'tgl_presensi' => $tanggal,
        'jam_masuk' => $status === 'hadir' ? $jamMasuk : null,
        'jam_keluar' => $status === 'hadir' ? '16:00:00' : null,
        'status' => $status,
    ]);
}

it('separates on-time from late rows in the monthly recap', function () {
    $pegawai = Pegawai::factory()->create();
    $shift = shiftUntukRekap();

    buatPresensiRekap($pegawai, '2026-11-02', 'hadir', $shift, '07:15:00');
    buatPresensiRekap($pegawai, '2026-11-03', 'hadir', $shift, '07:30:00');
    buatPresensiRekap($pegawai, '2026-11-04', 'hadir', $shift, '07:45:00');
    buatPresensiRekap($pegawai, '2026-11-05', 'cuti');
    buatPresensiRekap($pegawai, '2026-11-06', 'sakit');
    buatPresensiRekap($pegawai, '2026-11-07', 'dl');

    $bulan = app(RekapKehadiranAction::class)($pegawai)['bulanIni'];

    expect($bulan)->toMatchArray([
        'label' => 'November 2026',
        'tepatWaktu' => 2,
        'terlambat' => 1,
        'cuti' => 1,
        'sakit' => 1,
        'dl' => 1,
        'totalHari' => 6,
        'totalMenitTerlambat' => 15,
        'totalMenitTerlambatTeks' => '15 menit',
    ])
        ->and($bulan['tepatWaktu'] + $bulan['terlambat'] + $bulan['cuti'] + $bulan['sakit'] + $bulan['dl'])
        ->toBe($bulan['totalHari']);
});

it('uses the shift schedule as the late boundary instead of a fixed time', function () {
    $pegawai = Pegawai::factory()->create();
    $shift = shiftUntukRekap('JK02');
    $shift->update(['jam_masuk' => '13:00:00', 'jam_pulang' => '22:00:00']);

    buatPresensiRekap($pegawai, '2026-11-02', 'hadir', $shift, '12:55:00');
    buatPresensiRekap($pegawai, '2026-11-03', 'hadir', $shift, '13:10:00');

    $bulan = app(RekapKehadiranAction::class)($pegawai)['bulanIni'];

    expect($bulan['tepatWaktu'])->toBe(1)
        ->and($bulan['terlambat'])->toBe(1)
        ->and($bulan['totalMenitTerlambat'])->toBe(10);
});

it('totals lateness across the year and ignores the previous year', function () {
    $pegawai = Pegawai::factory()->create();
    $shift = shiftUntukRekap();

    buatPresensiRekap($pegawai, '2026-01-15', 'hadir', $shift, '08:00:00');
    buatPresensiRekap($pegawai, '2026-11-04', 'hadir', $shift, '07:45:00');
    buatPresensiRekap($pegawai, '2025-12-31', 'hadir', $shift, '09:00:00');

    $rekap = app(RekapKehadiranAction::class)($pegawai);

    expect($rekap['bulanIni']['totalMenitTerlambat'])->toBe(15)
        ->and($rekap['tahunIni'])->toMatchArray([
            'tahun' => 2026,
            'hariTerlambat' => 2,
            'totalMenitTerlambat' => 45,
            'totalMenitTerlambatTeks' => '45 menit',
        ]);
});

it('excludes shifts listed in config and follows changes to that list', function () {
    $pegawai = Pegawai::factory()->create();
    $shift = shiftUntukRekap('JK03');

    buatPresensiRekap($pegawai, '2026-11-04', 'hadir', $shift, '07:45:00');

    $diabaikan = app(RekapKehadiranAction::class)($pegawai)['bulanIni'];
    expect($diabaikan['terlambat'])->toBe(0)
        ->and($diabaikan['tepatWaktu'])->toBe(1)
        ->and($diabaikan['totalMenitTerlambat'])->toBe(0);

    config(['presensi.kode_jam_kerja_diabaikan' => []]);

    $dihitung = app(RekapKehadiranAction::class)($pegawai)['bulanIni'];
    expect($dihitung['terlambat'])->toBe(1)
        ->and($dihitung['totalMenitTerlambat'])->toBe(15);
});

it('treats hadir rows without a shift or check-in time as on time', function () {
    $pegawai = Pegawai::factory()->create();
    $shift = shiftUntukRekap();

    buatPresensiRekap($pegawai, '2026-11-02', 'hadir', $shift, null);
    Presensi::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_jam_kerja' => null,
        'tgl_presensi' => '2026-11-03',
        'jam_masuk' => '09:00:00',
        'status' => 'hadir',
    ]);

    $bulan = app(RekapKehadiranAction::class)($pegawai)['bulanIni'];

    expect($bulan['tepatWaktu'])->toBe(2)
        ->and($bulan['terlambat'])->toBe(0)
        ->and($bulan['totalMenitTerlambat'])->toBe(0);
});

it('rounds partial lateness up to a whole minute', function () {
    $pegawai = Pegawai::factory()->create();
    $shift = shiftUntukRekap();

    buatPresensiRekap($pegawai, '2026-11-04', 'hadir', $shift, '07:30:30');

    $bulan = app(RekapKehadiranAction::class)($pegawai)['bulanIni'];

    expect($bulan['terlambat'])->toBe(1)
        ->and($bulan['totalMenitTerlambat'])->toBe(1)
        ->and($bulan['totalMenitTerlambatTeks'])->toBe('1 menit');
});

it('formats the yearly total in hours and minutes', function () {
    $pegawai = Pegawai::factory()->create();
    $shift = shiftUntukRekap();

    buatPresensiRekap($pegawai, '2026-02-02', 'hadir', $shift, '09:30:00');
    buatPresensiRekap($pegawai, '2026-03-03', 'hadir', $shift, '08:35:00');

    $tahun = app(RekapKehadiranAction::class)($pegawai)['tahunIni'];

    expect($tahun['totalMenitTerlambat'])->toBe(185)
        ->and($tahun['totalMenitTerlambatTeks'])->toBe('3 jam 5 menit');
});

it('uses the office timezone to decide which day is today', function () {
    // 18:30 UTC = 01:30 WIB keesokan harinya.
    $this->travelTo(CarbonImmutable::parse('2026-11-25 18:30:00', 'UTC'));

    $pegawai = Pegawai::factory()->create();
    $shift = shiftUntukRekap();
    buatPresensiRekap($pegawai, '2026-11-26', 'hadir', $shift, '07:45:00');

    $hariIni = app(RekapKehadiranAction::class)($pegawai)['hariIni'];

    expect($hariIni['tanggal'])->toBe('2026-11-26')
        ->and($hariIni['labelTanggal'])->toBe('Kamis, 26 November 2026')
        ->and($hariIni['status'])->toBe('hadir')
        ->and($hariIni['menitTerlambat'])->toBe(15);
});

it('does not count half day dinas luar as late', function () {
    $pegawai = Pegawai::factory()->create();
    $shift = shiftUntukRekap('JKDL');

    $dl = Pengajuan::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_cuti' => null,
        'tanggal_mulai' => '2026-11-25',
        'tanggal_berakhir' => '2026-11-25',
        'jenis' => 'dl',
        'mode_dl' => 'setengah',
        'keterangan' => 'Dinas luar setengah hari',
        'status' => 'approved',
    ]);

    Presensi::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_jam_kerja' => $shift->id,
        'id_pengajuan' => $dl->id,
        'tgl_presensi' => '2026-11-25',
        'jam_masuk' => '10:00:00',
        'jam_keluar' => null,
        'status' => 'hadir',
    ]);

    $rekap = app(RekapKehadiranAction::class)($pegawai);

    expect($rekap['hariIni']['menitTerlambat'])->toBe(0)
        ->and($rekap['hariIni']['terlambat'])->toBeFalse()
        ->and($rekap['bulanIni']['terlambat'])->toBe(0)
        ->and($rekap['bulanIni']['tepatWaktu'])->toBe(1);
});

it('recaps all employees monthly with the same per-pegawai numbers', function () {
    $pegawaiA = Pegawai::factory()->create(['nama' => 'Budi Santoso']);
    $pegawaiB = Pegawai::factory()->create(['nama' => 'Siti Aminah']);
    $shift = shiftUntukRekap();

    buatPresensiRekap($pegawaiA, '2026-11-02', 'hadir', $shift, '07:45:00');
    buatPresensiRekap($pegawaiA, '2026-11-03', 'hadir', $shift, '08:15:00');
    buatPresensiRekap($pegawaiA, '2026-11-04', 'cuti');
    buatPresensiRekap($pegawaiB, '2026-11-02', 'hadir', $shift, '07:35:00');

    $hasil = app(RekapKehadiranAction::class)->semuaBulanan(2026, 11);
    $barisA = collect($hasil['baris'])->firstWhere('id', $pegawaiA->id);
    $barisB = collect($hasil['baris'])->firstWhere('id', $pegawaiB->id);

    expect($hasil['labelPeriode'])->toBe('November 2026')
        ->and($barisA['hariTerlambat'])->toBe(2)
        ->and($barisA['totalMenitTerlambat'])->toBe(60)
        ->and($barisA['tepatWaktu'])->toBe(0)
        ->and($barisA['cuti'])->toBe(1)
        ->and($barisB['hariTerlambat'])->toBe(1)
        ->and($barisB['totalMenitTerlambat'])->toBe(5)
        ->and($hasil['ringkasan'])->toMatchArray([
            'totalPegawai' => 2,
            'pegawaiTerlambat' => 2,
            'totalHariTerlambat' => 3,
            'totalMenitTerlambat' => 65,
            'totalMenitTerlambatTeks' => '1 jam 5 menit',
        ]);
});

it('recaps all employees yearly and excludes other years', function () {
    $pegawai = Pegawai::factory()->create();
    $shift = shiftUntukRekap();

    buatPresensiRekap($pegawai, '2026-01-15', 'hadir', $shift, '08:00:00');
    buatPresensiRekap($pegawai, '2026-11-04', 'hadir', $shift, '07:45:00');
    buatPresensiRekap($pegawai, '2025-12-31', 'hadir', $shift, '09:00:00');

    $hasil = app(RekapKehadiranAction::class)->semuaTahunan(2026);

    expect($hasil['baris'][0]['hariTerlambat'])->toBe(2)
        ->and($hasil['baris'][0]['totalMenitTerlambat'])->toBe(45)
        ->and($hasil['ringkasan']['totalMenitTerlambat'])->toBe(45)
        ->and($hasil['ringkasan']['totalMenitTerlambatTeks'])->toBe('45 menit');
});

it('ignores excluded shifts in the all-employee recap', function () {
    $pegawai = Pegawai::factory()->create();
    $shift = shiftUntukRekap('JK03');

    buatPresensiRekap($pegawai, '2026-11-04', 'hadir', $shift, '07:45:00');

    $hasil = app(RekapKehadiranAction::class)->semuaBulanan(2026, 11);

    expect($hasil['ringkasan']['pegawaiTerlambat'])->toBe(0)
        ->and($hasil['ringkasan']['totalMenitTerlambat'])->toBe(0);
});

it('exposes per-pegawai monthly and yearly recap from the same service', function () {
    $pegawai = Pegawai::factory()->create();
    $shift = shiftUntukRekap();

    buatPresensiRekap($pegawai, '2026-10-05', 'hadir', $shift, '08:00:00');
    buatPresensiRekap($pegawai, '2026-11-02', 'hadir', $shift, '07:45:00');

    $bulanan = app(RekapKehadiranAction::class)->pegawaiBulanan($pegawai, 2026, 11);
    $tahunan = app(RekapKehadiranAction::class)->pegawaiTahunan($pegawai, 2026);

    expect($bulanan['terlambat'])->toBe(1)
        ->and($bulanan['totalMenitTerlambat'])->toBe(15)
        ->and($tahunan['hariTerlambat'])->toBe(2)
        ->and($tahunan['totalMenitTerlambat'])->toBe(45);
});
