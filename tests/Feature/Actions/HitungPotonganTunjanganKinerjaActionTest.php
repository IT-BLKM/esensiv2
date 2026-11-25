<?php

use App\Actions\Laporan\HitungPotonganTunjanganKinerjaAction;
use App\Models\JamKerja;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\Presensi;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-25 09:00:00', 'Asia/Jakarta'));
    config([
        'presensi.zona_waktu' => 'Asia/Jakarta',
        'presensi.kode_jam_kerja_diabaikan' => ['JK03'],
    ]);
});

function shiftTunjangan(string $kode = 'JK01'): JamKerja
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

function presensiTunjangan(
    Pegawai $pegawai,
    JamKerja $shift,
    string $tanggal,
    ?string $jamMasuk = '07:30:00',
    ?string $jamKeluar = '16:00:00',
    string $status = 'hadir',
    ?Pengajuan $pengajuan = null,
): Presensi {
    return Presensi::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_jam_kerja' => $shift->id,
        'id_pengajuan' => $pengajuan?->id,
        'tgl_presensi' => $tanggal,
        'jam_masuk' => $jamMasuk,
        'jam_keluar' => $jamKeluar,
        'status' => $status,
    ]);
}

it('classifies lateness tiers at their boundaries', function (string $jamMasuk, ?string $level, float $persen, int $menit) {
    $pegawai = Pegawai::factory()->create();
    $shift = shiftTunjangan();
    $tanggal = '2026-11-02';

    presensiTunjangan($pegawai, $shift, $tanggal, $jamMasuk);

    $hasil = app(HitungPotonganTunjanganKinerjaAction::class)(
        $pegawai,
        CarbonImmutable::parse($tanggal),
        CarbonImmutable::parse($tanggal),
    );

    expect($hasil['baris'][0]['levelTl'])->toBe($level)
        ->and($hasil['baris'][0]['persenTl'])->toBe($persen)
        ->and($hasil['baris'][0]['menitTerlambat'])->toBe($menit);
})->with([
    'tepat waktu' => ['07:30:00', null, 0.0, 0],
    'telat 1 menit' => ['07:31:00', 'TL1', 1.0, 1],
    'telat 30 menit' => ['08:00:00', 'TL1', 1.0, 30],
    'telat 31 menit' => ['08:01:00', 'TL2', 1.25, 31],
    'telat 60 menit' => ['08:30:00', 'TL2', 1.25, 60],
    'telat 61 menit' => ['08:31:00', 'TL3', 2.5, 61],
    'telat 90 menit' => ['09:00:00', 'TL3', 2.5, 90],
]);

it('classifies early leave tiers at their boundaries', function (string $jamKeluar, ?string $level, float $persen, int $menit) {
    $pegawai = Pegawai::factory()->create();
    $shift = shiftTunjangan();
    $tanggal = '2026-11-03';

    presensiTunjangan($pegawai, $shift, $tanggal, '07:30:00', $jamKeluar);

    $hasil = app(HitungPotonganTunjanganKinerjaAction::class)(
        $pegawai,
        CarbonImmutable::parse($tanggal),
        CarbonImmutable::parse($tanggal),
    );

    expect($hasil['baris'][0]['levelPsw'])->toBe($level)
        ->and($hasil['baris'][0]['persenPsw'])->toBe($persen)
        ->and($hasil['baris'][0]['menitPulangCepat'])->toBe($menit);
})->with([
    'tepat pulang' => ['16:00:00', null, 0.0, 0],
    'pulang 1 menit cepat' => ['15:59:00', 'PSW1', 0.5, 1],
    'pulang 30 menit cepat' => ['15:30:00', 'PSW1', 0.5, 30],
    'pulang 31 menit cepat' => ['15:29:00', 'PSW2', 1.0, 31],
    'pulang 60 menit cepat' => ['15:00:00', 'PSW2', 1.0, 60],
    'pulang 61 menit cepat' => ['14:59:00', 'PSW3', 1.25, 61],
    'pulang 90 menit cepat' => ['14:30:00', 'PSW3', 1.25, 90],
    'pulang 91 menit cepat' => ['14:29:00', 'PSW4', 2.5, 91],
]);

it('treats hadir without check-in as TL3', function () {
    $pegawai = Pegawai::factory()->create();
    $shift = shiftTunjangan();
    $tanggal = '2026-11-04';

    presensiTunjangan($pegawai, $shift, $tanggal, null, '16:00:00');

    $hasil = app(HitungPotonganTunjanganKinerjaAction::class)(
        $pegawai,
        CarbonImmutable::parse($tanggal),
        CarbonImmutable::parse($tanggal),
    );

    expect($hasil['baris'][0]['levelTl'])->toBe('TL3')
        ->and($hasil['baris'][0]['persenTl'])->toBe(2.5)
        ->and($hasil['baris'][0]['menitTerlambat'])->toBeNull();
});

it('treats a missing check-out after the shift as PSW4', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-25 17:00:00', 'Asia/Jakarta'));

    $pegawai = Pegawai::factory()->create();
    $shift = shiftTunjangan();
    $tanggal = '2026-11-24';

    presensiTunjangan($pegawai, $shift, $tanggal, '07:30:00', null);

    $hasil = app(HitungPotonganTunjanganKinerjaAction::class)(
        $pegawai,
        CarbonImmutable::parse($tanggal),
        CarbonImmutable::parse($tanggal),
    );

    expect($hasil['baris'][0]['levelPsw'])->toBe('PSW4')
        ->and($hasil['baris'][0]['persenPsw'])->toBe(2.5)
        ->and($hasil['baris'][0]['menitPulangCepat'])->toBeNull();
});

it('does not decide a missing check-out before the shift ends today', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-25 15:00:00', 'Asia/Jakarta'));

    $pegawai = Pegawai::factory()->create();
    $shift = shiftTunjangan();
    $tanggal = '2026-11-25';

    presensiTunjangan($pegawai, $shift, $tanggal, '07:30:00', null);

    $hasil = app(HitungPotonganTunjanganKinerjaAction::class)(
        $pegawai,
        CarbonImmutable::parse($tanggal),
        CarbonImmutable::parse($tanggal),
    );

    expect($hasil['baris'][0]['levelPsw'])->toBeNull()
        ->and($hasil['baris'][0]['persenPsw'])->toBe(0.0);
});

it('ignores excluded shifts and half-day dinas luar', function () {
    $pegawai = Pegawai::factory()->create();
    $shiftDikecualikan = shiftTunjangan('JK03');
    $shiftNormal = shiftTunjangan('JK01');

    $dl = Pengajuan::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_cuti' => null,
        'tanggal_mulai' => '2026-11-05',
        'tanggal_berakhir' => '2026-11-05',
        'jenis' => 'dl',
        'mode_dl' => 'setengah',
        'keterangan' => 'Dinas luar setengah hari',
        'status' => 'approved',
    ]);

    presensiTunjangan($pegawai, $shiftDikecualikan, '2026-11-04', '08:00:00', '15:00:00');
    presensiTunjangan($pegawai, $shiftNormal, '2026-11-05', '08:00:00', null, 'hadir', $dl);

    $hasil = app(HitungPotonganTunjanganKinerjaAction::class)(
        $pegawai,
        CarbonImmutable::parse('2026-11-04'),
        CarbonImmutable::parse('2026-11-05'),
    );

    expect($hasil['baris'])->toBe([])
        ->and($hasil['ringkasan']['totalPersen'])->toBe(0.0);
});

it('aggregates monthly levels and percentages', function () {
    $pegawai = Pegawai::factory()->create();
    $shift = shiftTunjangan();

    presensiTunjangan($pegawai, $shift, '2026-11-02', '08:00:00', '15:30:00');
    presensiTunjangan($pegawai, $shift, '2026-11-03', '08:30:00', '14:30:00');
    presensiTunjangan($pegawai, $shift, '2026-11-04', '08:31:00', '16:00:00');

    $hasil = app(HitungPotonganTunjanganKinerjaAction::class)
        ->bulanan($pegawai, 2026, 11);

    expect($hasil['tahun'])->toBe(2026)
        ->and($hasil['bulan'])->toBe(11)
        ->and($hasil['ringkasan'])->toMatchArray([
            'jumlahHari' => 3,
            'jumlahTl1' => 1,
            'jumlahTl2' => 1,
            'jumlahTl3' => 1,
            'jumlahPsw1' => 1,
            'jumlahPsw2' => 0,
            'jumlahPsw3' => 1,
            'jumlahPsw4' => 0,
            'totalMenitTerlambat' => 30 + 60 + 61,
            'totalMenitPulangCepat' => 30 + 90,
            'totalPersenTl' => 1.0 + 1.25 + 2.5,
            'totalPersenPsw' => 0.5 + 1.25,
            'totalPersen' => 1.0 + 1.25 + 2.5 + 0.5 + 1.25,
        ]);
});

it('ignores rows outside the requested year', function () {
    $pegawai = Pegawai::factory()->create();
    $shift = shiftTunjangan();

    presensiTunjangan($pegawai, $shift, '2025-12-31', '08:00:00');
    presensiTunjangan($pegawai, $shift, '2026-01-02', '08:00:00');

    $hasil = app(HitungPotonganTunjanganKinerjaAction::class)->tahunan($pegawai, 2026);

    expect($hasil['tahun'])->toBe(2026)
        ->and($hasil['ringkasan']['jumlahHari'])->toBe(1)
        ->and($hasil['ringkasan']['totalPersen'])->toBe(1.0);
});

it('removes the lateness deduction when the same-day replacement window is met', function () {
    $pegawai = Pegawai::factory()->create();
    $shift = shiftTunjangan();
    $tanggal = '2026-11-02';

    presensiTunjangan($pegawai, $shift, $tanggal, '07:50:00', '16:22:00');

    $hasil = app(HitungPotonganTunjanganKinerjaAction::class)(
        $pegawai,
        CarbonImmutable::parse($tanggal),
        CarbonImmutable::parse($tanggal),
    );

    expect($hasil['baris'][0]['levelTl'])->toBeNull()
        ->and($hasil['baris'][0]['persenTl'])->toBe(0.0)
        ->and($hasil['baris'][0]['penggantianTerpenuhi'])->toBeTrue()
        ->and($hasil['baris'][0]['menitDiganti'])->toBe(22)
        ->and($hasil['ringkasan']['jumlahPenggantian'])->toBe(1)
        ->and($hasil['ringkasan']['totalMenitDiganti'])->toBe(22);
});

it('keeps the lateness deduction when checkout misses the replacement window', function () {
    $pegawai = Pegawai::factory()->create();
    $shift = shiftTunjangan();
    $tanggal = '2026-11-03';

    presensiTunjangan($pegawai, $shift, $tanggal, '07:50:00', '16:26:00');

    $hasil = app(HitungPotonganTunjanganKinerjaAction::class)(
        $pegawai,
        CarbonImmutable::parse($tanggal),
        CarbonImmutable::parse($tanggal),
    );

    expect($hasil['baris'][0]['levelTl'])->toBe('TL1')
        ->and($hasil['baris'][0]['persenTl'])->toBe(1.0)
        ->and($hasil['baris'][0]['penggantianTerpenuhi'])->toBeFalse()
        ->and($hasil['ringkasan']['jumlahPenggantian'])->toBe(0);
});

it('does not replace lateness over thirty minutes', function () {
    $pegawai = Pegawai::factory()->create();
    $shift = shiftTunjangan();
    $tanggal = '2026-11-04';

    presensiTunjangan($pegawai, $shift, $tanggal, '08:01:00', '16:40:00');

    $hasil = app(HitungPotonganTunjanganKinerjaAction::class)(
        $pegawai,
        CarbonImmutable::parse($tanggal),
        CarbonImmutable::parse($tanggal),
    );

    expect($hasil['baris'][0]['levelTl'])->toBe('TL2')
        ->and($hasil['baris'][0]['bisaDiganti'])->toBeFalse()
        ->and($hasil['baris'][0]['penggantianTerpenuhi'])->toBeFalse();
});

it('accumulates only unreplaced lateness in the monthly percentage', function () {
    $pegawai = Pegawai::factory()->create();
    $shift = shiftTunjangan();

    presensiTunjangan($pegawai, $shift, '2026-11-02', '07:50:00', '16:22:00');
    presensiTunjangan($pegawai, $shift, '2026-11-03', '07:50:00', '16:26:00');

    $hasil = app(HitungPotonganTunjanganKinerjaAction::class)->bulanan($pegawai, 2026, 11);

    expect($hasil['ringkasan']['jumlahPenggantian'])->toBe(1)
        ->and($hasil['ringkasan']['totalMenitDiganti'])->toBe(22)
        ->and($hasil['ringkasan']['totalPersenTl'])->toBe(1.0)
        ->and($hasil['ringkasan']['totalPersen'])->toBe(1.0);
});
