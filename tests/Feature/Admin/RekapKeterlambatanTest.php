<?php

use App\Actions\Laporan\RekapKehadiranAction;
use App\Models\JamKerja;
use App\Models\Pegawai;
use App\Models\Presensi;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->travelTo(CarbonImmutable::parse('2026-11-25 09:00:00', 'Asia/Jakarta'));
    config(['presensi.zona_waktu' => 'Asia/Jakarta']);
});

function shiftRekapKeterlambatan(): JamKerja
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

function presensiRekapKeterlambatan(Pegawai $pegawai, JamKerja $shift, string $tanggal, string $jamMasuk): Presensi
{
    return Presensi::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_jam_kerja' => $shift->id,
        'tgl_presensi' => $tanggal,
        'jam_masuk' => $jamMasuk,
        'jam_keluar' => '16:00:00',
        'status' => 'hadir',
    ]);
}

it('redirects guests and pegawai away from rekap keterlambatan', function () {
    $this->get(route('admin.rekap.index'))->assertRedirect(route('login'));

    $pegawai = User::factory()->create(['role' => 'pegawai']);
    $this->actingAs($pegawai)->get(route('admin.rekap.index'))->assertRedirect('/dashboard');
});

it('shows the same monthly late total on dashboard and rekap page', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $shift = shiftRekapKeterlambatan();
    $pegawaiA = Pegawai::factory()->create(['nama' => 'Budi Santoso']);
    $pegawaiB = Pegawai::factory()->create(['nama' => 'Siti Aminah']);

    presensiRekapKeterlambatan($pegawaiA, $shift, '2026-11-02', '07:45:00');
    presensiRekapKeterlambatan($pegawaiA, $shift, '2026-11-03', '08:15:00');
    presensiRekapKeterlambatan($pegawaiB, $shift, '2026-11-02', '07:35:00');

    $total = app(RekapKehadiranAction::class)->semuaBulanan(2026, 11)['ringkasan']['totalMenitTerlambatTeks'];

    $this->actingAs($admin)->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Dashboard admin')
        ->assertSee($total)
        ->assertSeeInOrder(['Budi Santoso', 'Siti Aminah']);

    $this->actingAs($admin)->get(route('admin.rekap.index'))
        ->assertOk()
        ->assertSee('Rekap keterlambatan')
        ->assertSee($total)
        ->assertSeeInOrder(['Budi Santoso', 'Siti Aminah']);
});

it('filters the recap by month', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $shift = shiftRekapKeterlambatan();
    $pegawai = Pegawai::factory()->create(['nama' => 'Budi Santoso']);

    presensiRekapKeterlambatan($pegawai, $shift, '2026-10-05', '08:00:00');
    presensiRekapKeterlambatan($pegawai, $shift, '2026-11-02', '07:45:00');

    $this->actingAs($admin)->get(route('admin.rekap.index', [
        'bulan' => 10,
        'tahun' => 2026,
    ]))
        ->assertOk()
        ->assertSee('Oktober 2026')
        ->assertSee('30 menit')
        ->assertDontSee('15 menit');
});

it('shows a yearly accumulation when month is empty', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $shift = shiftRekapKeterlambatan();
    $pegawai = Pegawai::factory()->create(['nama' => 'Budi Santoso']);

    presensiRekapKeterlambatan($pegawai, $shift, '2026-10-05', '08:00:00');
    presensiRekapKeterlambatan($pegawai, $shift, '2026-11-02', '07:45:00');

    $this->actingAs($admin)->get(route('admin.rekap.index', [
        'bulan' => '',
        'tahun' => 2026,
    ]))
        ->assertOk()
        ->assertSee('Rekap 2026')
        ->assertSee('45 menit');
});

it('rejects invalid rekap filters', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get(route('admin.rekap.index', [
        'bulan' => 13,
        'tahun' => 1999,
    ]))->assertSessionHasErrors(['bulan', 'tahun']);
});

it('shows a friendly empty state when there are no pegawai', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get(route('admin.rekap.index'))
        ->assertOk()
        ->assertSee('Belum ada data pegawai.');
});
