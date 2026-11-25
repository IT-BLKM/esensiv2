<?php

use App\Models\Cuti;
use App\Models\JamKerja;
use App\Models\KonfigurasiJamKerja;
use App\Models\Pegawai;
use App\Models\Presensi;
use App\Models\SaldoCuti;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
 * Dashboard pegawai (issue #34) — diuji lewat HTTP karena tampilan dan
 * angkanya berasal dari Action yang sama. Waktu dibekukan pada Rabu,
 * 25 November 2026 supaya "hari ini", "bulan ini", dan "tahun ini" pasti.
 */
beforeEach(function () {
    $this->withoutVite();
    $this->travelTo(CarbonImmutable::parse('2026-11-25 09:00:00'));
});

/**
 * @return array{User, Pegawai}
 */
function akunDashboardPegawai(string $role = 'pegawai'): array
{
    $user = User::factory()->create(['role' => $role]);

    return [$user, Pegawai::factory()->create(['user_id' => $user->id])];
}

function shiftDashboard(): JamKerja
{
    return JamKerja::factory()->create([
        'kode' => 'JK01',
        'nama' => 'Shift Pagi',
        'awal_masuk' => '07:00:00',
        'jam_masuk' => '07:30:00',
        'akhir_masuk' => '09:30:00',
        'jam_pulang' => '16:00:00',
    ]);
}

function jadwalkanDashboard(Pegawai $pegawai, JamKerja $shift, string $hari = 'Rabu'): void
{
    KonfigurasiJamKerja::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_jam_kerja' => $shift->id,
        'hari' => $hari,
    ]);
}

function presensiDashboard(
    Pegawai $pegawai,
    string $tanggal,
    string $jamMasuk,
    ?JamKerja $shift = null,
): Presensi {
    return Presensi::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_jam_kerja' => $shift?->id,
        'tgl_presensi' => $tanggal,
        'jam_masuk' => $jamMasuk,
        'status' => 'hadir',
    ]);
}

it('shows today schedule, the recorded check-in, and the late badge', function () {
    [$user, $pegawai] = akunDashboardPegawai();
    $shift = shiftDashboard();
    jadwalkanDashboard($pegawai, $shift);
    presensiDashboard($pegawai, '2026-11-25', '07:45:00', $shift);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Rabu, 25 November 2026')
        ->assertSee('Shift Pagi')
        ->assertSee('07:45')
        ->assertSee('Terlambat 15 menit')
        ->assertSee('Jadwal')
        ->assertSee('16:00');
});

it('shows the scheduled shift before the pegawai checks in', function () {
    [$user, $pegawai] = akunDashboardPegawai();
    jadwalkanDashboard($pegawai, shiftDashboard());

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Shift Pagi')
        ->assertSee('07:30')
        ->assertSee('Belum ada presensi tercatat hari ini.');
});

it('renders the monthly recap and the yearly lateness total', function () {
    [$user, $pegawai] = akunDashboardPegawai();
    $shift = shiftDashboard();

    presensiDashboard($pegawai, '2026-11-02', '07:15:00', $shift);
    presensiDashboard($pegawai, '2026-11-03', '08:00:00', $shift);
    presensiDashboard($pegawai, '2026-01-05', '08:10:00', $shift);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Rekap November 2026')
        ->assertSee('Hadir berarti masuk tepat waktu; terlambat dihitung sebagai angka terpisah.')
        ->assertSee('30 menit')
        ->assertSee('Terlambat tahun 2026')
        ->assertSee('1 jam 10 menit (2 hari)');
});

it('shows 24 days annual leave including two years of savings', function () {
    [$user, $pegawai] = akunDashboardPegawai();
    $cuti = Cuti::factory()->create([
        'kode' => 'C01',
        'nama' => 'Cuti Tahunan',
        'kuota' => 12,
        'maks_hari' => 12,
        'maks_bawa' => 6,
    ]);

    SaldoCuti::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_cuti' => $cuti->id,
        'tahun_sumber' => 2024,
        'saldo' => 6,
    ]);
    SaldoCuti::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_cuti' => $cuti->id,
        'tahun_sumber' => 2025,
        'saldo' => 6,
    ]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Sisa cuti tahunan')
        ->assertSee('24 hari')
        ->assertSee('Tabungan 2024')
        ->assertSee('Tabungan 2025')
        ->assertSee('Hari libur tidak mengurangi kuota');
});

it('shows the empty state when there is no schedule and no record', function () {
    [$user] = akunDashboardPegawai();

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Tidak ada jadwal shift untuk hari ini.')
        ->assertSee('Belum ada presensi tercatat hari ini.')
        ->assertSee('Belum ada presensi tercatat bulan ini.')
        ->assertSee('0 menit');
});

it('never shows another pegawai records', function () {
    [$userA] = akunDashboardPegawai();
    [, $pegawaiB] = akunDashboardPegawai();
    $shiftB = shiftDashboard();

    $shiftB->update(['nama' => 'Shift Khusus B']);
    jadwalkanDashboard($pegawaiB, $shiftB);
    presensiDashboard($pegawaiB, '2026-11-25', '09:30:00', $shiftB);

    $this->actingAs($userA)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Shift Khusus B')
        ->assertDontSee('2 jam')
        ->assertDontSee('09:30')
        ->assertSee('0 menit');
});

it('warns when the account is not linked to pegawai data', function () {
    $user = User::factory()->create(['role' => 'pegawai']);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Data pegawai belum tersedia')
        ->assertDontSee('Rekap November 2026');
});
