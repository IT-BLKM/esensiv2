<?php

use App\Models\Cuti;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\Presensi;
use App\Models\SaldoCuti;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-01-01 00:10:00', 'Asia/Jakarta'));
    config(['presensi.zona_waktu' => 'Asia/Jakarta']);
});

function cutiTahunanCommand(): Cuti
{
    return Cuti::factory()->create([
        'kode' => 'C01',
        'nama' => 'Cuti Tahunan',
        'maks_hari' => 12,
        'kuota' => 12,
        'maks_bawa' => 6,
    ]);
}

function pakaiCutiCommand(Pegawai $pegawai, Cuti $cuti, int $tahun, int $jumlah): void
{
    $mulai = CarbonImmutable::create($tahun, 1, 1);

    $pengajuan = Pengajuan::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_cuti' => $cuti->id,
        'tanggal_mulai' => $mulai->toDateString(),
        'tanggal_berakhir' => $mulai->addDays($jumlah - 1)->toDateString(),
        'jenis' => 'cuti',
        'status' => 'approved',
    ]);

    foreach (range(0, $jumlah - 1) as $offset) {
        Presensi::factory()->create([
            'id_pegawai' => $pegawai->id,
            'id_jam_kerja' => null,
            'id_pengajuan' => $pengajuan->id,
            'tgl_presensi' => $mulai->addDays($offset)->toDateString(),
            'jam_masuk' => null,
            'jam_keluar' => null,
            'foto_masuk' => null,
            'foto_keluar' => null,
            'koordinat_masuk' => null,
            'koordinat_keluar' => null,
            'status' => 'cuti',
        ]);
    }
}

it('creates the previous year savings from unused annual quota', function () {
    $cuti = cutiTahunanCommand();
    $pegawai = Pegawai::factory()->create();

    pakaiCutiCommand($pegawai, $cuti, 2025, 5);

    $this->artisan('cuti:rollover-tahunan', ['--tahun' => 2026])
        ->assertSuccessful();

    expect(SaldoCuti::query()
        ->where('id_pegawai', $pegawai->id)
        ->where('id_cuti', $cuti->id)
        ->where('tahun_sumber', 2025)
        ->value('saldo'))->toBe(6);
});

it('is idempotent when the command runs twice', function () {
    $cuti = cutiTahunanCommand();
    $pegawai = Pegawai::factory()->create();

    pakaiCutiCommand($pegawai, $cuti, 2025, 5);

    $this->artisan('cuti:rollover-tahunan', ['--tahun' => 2026])->assertSuccessful();
    $this->artisan('cuti:rollover-tahunan', ['--tahun' => 2026])->assertSuccessful();

    expect(SaldoCuti::query()
        ->where('id_pegawai', $pegawai->id)
        ->where('id_cuti', $cuti->id)
        ->where('tahun_sumber', 2025)
        ->count())->toBe(1);
});

it('skips pegawai without any activity in the source year', function () {
    cutiTahunanCommand();
    $pegawai = Pegawai::factory()->create();

    $this->artisan('cuti:rollover-tahunan', ['--tahun' => 2026])
        ->assertSuccessful();

    expect(SaldoCuti::query()->where('id_pegawai', $pegawai->id)->count())->toBe(0);
});

it('rejects an invalid year option', function () {
    cutiTahunanCommand();

    $this->artisan('cuti:rollover-tahunan', ['--tahun' => 'dua ribu'])
        ->assertFailed();
});

it('registers the rollover in the yearly schedule', function () {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('cuti:rollover-tahunan')
        ->assertSuccessful();

    $event = collect(app(Schedule::class)->events())
        ->first(fn ($jadwal) => str_contains((string) $jadwal->command, 'cuti:rollover-tahunan'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('5 0 1 1 *')
        ->and($event->timezone)->toBe('Asia/Jakarta');
});
