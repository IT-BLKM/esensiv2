<?php

use App\Actions\Pengajuan\DaftarHariKerjaAction;
use App\Actions\Pengajuan\HitungHariKerjaAction;
use App\Models\HariLibur;
use App\Models\JamKerja;
use App\Models\KonfigurasiJamKerja;
use App\Models\Pegawai;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-25 09:00:00', 'Asia/Jakarta'));
    config(['presensi.zona_waktu' => 'Asia/Jakarta']);
});

it('defaults to Monday through Friday when the pegawai has no shift configuration', function () {
    $pegawai = Pegawai::factory()->create();

    $daftar = app(DaftarHariKerjaAction::class)(
        $pegawai,
        CarbonImmutable::parse('2026-11-20'), // Jumat
        CarbonImmutable::parse('2026-11-23'), // Senin
    );

    expect(array_column($daftar, 'tanggal'))->toBe(['2026-11-20', '2026-11-23'])
        ->and(app(HitungHariKerjaAction::class)(
            $pegawai,
            CarbonImmutable::parse('2026-11-20'),
            CarbonImmutable::parse('2026-11-23'),
        ))->toBe(2);
});

it('uses only the days that have a configured shift', function () {
    $pegawai = Pegawai::factory()->create();
    $shift = JamKerja::factory()->create();

    KonfigurasiJamKerja::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_jam_kerja' => $shift->id,
        'hari' => 'Rabu',
    ]);

    $daftar = app(DaftarHariKerjaAction::class)(
        $pegawai,
        CarbonImmutable::parse('2026-11-23'), // Senin
        CarbonImmutable::parse('2026-11-25'), // Rabu
    );

    expect($daftar)->toBe([['tanggal' => '2026-11-25', 'id_jam_kerja' => $shift->id]])
        ->and(app(HitungHariKerjaAction::class)(
            $pegawai,
            CarbonImmutable::parse('2026-11-23'),
            CarbonImmutable::parse('2026-11-25'),
        ))->toBe(1);
});

it('excludes national holidays from working days', function () {
    $pegawai = Pegawai::factory()->create();

    HariLibur::factory()->create([
        'tanggal' => '2026-11-25',
        'nama' => 'Hari libur uji',
    ]);

    $daftar = app(DaftarHariKerjaAction::class)(
        $pegawai,
        CarbonImmutable::parse('2026-11-23'), // Senin
        CarbonImmutable::parse('2026-11-27'), // Jumat
    );

    expect(array_column($daftar, 'tanggal'))->toBe([
        '2026-11-23',
        '2026-11-24',
        '2026-11-26',
        '2026-11-27',
    ])->and(app(HitungHariKerjaAction::class)(
        $pegawai,
        CarbonImmutable::parse('2026-11-23'),
        CarbonImmutable::parse('2026-11-27'),
    ))->toBe(4);
});

it('returns no working days for a weekend-only range', function () {
    $pegawai = Pegawai::factory()->create();

    $daftar = app(DaftarHariKerjaAction::class)(
        $pegawai,
        CarbonImmutable::parse('2026-11-21'), // Sabtu
        CarbonImmutable::parse('2026-11-22'), // Minggu
    );

    expect($daftar)->toBe([]);
});

it('returns no days when the end date is before the start date', function () {
    $pegawai = Pegawai::factory()->create();

    expect(app(DaftarHariKerjaAction::class)(
        $pegawai,
        CarbonImmutable::parse('2026-11-25'),
        CarbonImmutable::parse('2026-11-24'),
    ))->toBe([]);
});
