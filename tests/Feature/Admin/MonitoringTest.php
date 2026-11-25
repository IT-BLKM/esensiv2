<?php

use App\Models\Departemen;
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

function departemenMonitoring(string $kode, string $nama): Departemen
{
    return Departemen::factory()->create(['kode' => $kode, 'nama' => $nama]);
}

function shiftMonitoring(): JamKerja
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

function pegawaiMonitoring(Departemen $departemen, string $nama, string $nip): Pegawai
{
    return Pegawai::factory()->create([
        'id_dept' => $departemen->id,
        'nama' => $nama,
        'nip' => $nip,
    ]);
}

function presensiMonitoring(
    Pegawai $pegawai,
    string $tanggal,
    string $status = 'hadir',
    ?JamKerja $shift = null,
    ?string $jamMasuk = '07:45:00',
    ?string $jamKeluar = '16:05:00',
    ?string $koordinatMasuk = '-2.913900,104.695100',
    ?string $koordinatKeluar = '-2.913910,104.695110',
): Presensi {
    return Presensi::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_jam_kerja' => $status === 'hadir' ? $shift?->id : null,
        'tgl_presensi' => $tanggal,
        'jam_masuk' => $status === 'hadir' ? $jamMasuk : null,
        'jam_keluar' => $status === 'hadir' ? $jamKeluar : null,
        'koordinat_masuk' => $status === 'hadir' ? $koordinatMasuk : null,
        'koordinat_keluar' => $status === 'hadir' ? $koordinatKeluar : null,
        'status' => $status,
    ]);
}

it('redirects guests and pegawai away from monitoring', function () {
    $this->get(route('admin.monitoring.index'))->assertRedirect(route('login'));

    $pegawai = User::factory()->create(['role' => 'pegawai']);
    $this->actingAs($pegawai)->get(route('admin.monitoring.index'))->assertRedirect('/dashboard');
});

it('shows today attendance with employee, department, shift, coordinates, and map data', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $departemen = departemenMonitoring('ADKL', 'Dinas Kependudukan');
    $shift = shiftMonitoring();
    $pegawai = pegawaiMonitoring($departemen, 'Budi Santoso', '199302032022032001');

    $presensi = presensiMonitoring($pegawai, '2026-11-25', shift: $shift);

    $this->actingAs($admin)->get(route('admin.monitoring.index'))
        ->assertOk()
        ->assertSee('Monitoring kehadiran')
        ->assertSee('Rabu, 25 November 2026')
        ->assertSee('Budi Santoso')
        ->assertSee('199302032022032001')
        ->assertSee('Dinas Kependudukan')
        ->assertSee('Shift Pagi')
        ->assertSee('07:45')
        ->assertSee('16:05')
        ->assertSee('-2.913900,104.695100')
        ->assertSee('id="halaman-monitoring"', false)
        ->assertSee('id="peta-monitoring"', false)
        ->assertSee('data-kantor-latitude="-2.9138085845944"', false)
        ->assertSee('data-fokus-presensi="'.$presensi->id.'"', false)
        ->assertSee('"jenis":"masuk"', false)
        ->assertSee('"jenis":"keluar"', false);
});

it('filters by date using the presensi date index', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $departemen = departemenMonitoring('ADKL', 'Dinas Kependudukan');
    $shift = shiftMonitoring();
    $hariIni = pegawaiMonitoring($departemen, 'Budi Hari Ini', '199302032022032001');
    $kemarin = pegawaiMonitoring($departemen, 'Siti Kemarin', '199302032022032002');

    presensiMonitoring($hariIni, '2026-11-25', shift: $shift);
    presensiMonitoring($kemarin, '2026-11-24', shift: $shift);

    $this->actingAs($admin)->get(route('admin.monitoring.index'))
        ->assertOk()
        ->assertSee('Budi Hari Ini')
        ->assertDontSee('Siti Kemarin');

    $this->actingAs($admin)->get(route('admin.monitoring.index', ['tanggal' => '2026-11-24']))
        ->assertOk()
        ->assertSee('Siti Kemarin')
        ->assertDontSee('Budi Hari Ini');
});

it('filters by department', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $departemenA = departemenMonitoring('ADKL', 'Dinas Kependudukan');
    $departemenB = departemenMonitoring('KEU', 'Keuangan');
    $shift = shiftMonitoring();
    $pegawaiA = pegawaiMonitoring($departemenA, 'Budi Kependudukan', '199302032022032001');
    $pegawaiB = pegawaiMonitoring($departemenB, 'Siti Keuangan', '199302032022032002');

    presensiMonitoring($pegawaiA, '2026-11-25', shift: $shift);
    presensiMonitoring($pegawaiB, '2026-11-25', shift: $shift);

    $this->actingAs($admin)->get(route('admin.monitoring.index', ['id_dept' => $departemenA->id]))
        ->assertOk()
        ->assertSee('Budi Kependudukan')
        ->assertDontSee('Siti Keuangan');
});

it('filters by status', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $departemen = departemenMonitoring('ADKL', 'Dinas Kependudukan');
    $shift = shiftMonitoring();
    $hadir = pegawaiMonitoring($departemen, 'Budi Hadir', '199302032022032001');
    $cuti = pegawaiMonitoring($departemen, 'Siti Cuti', '199302032022032002');

    presensiMonitoring($hadir, '2026-11-25', shift: $shift);
    presensiMonitoring($cuti, '2026-11-25', 'cuti');

    $this->actingAs($admin)->get(route('admin.monitoring.index', ['status' => 'cuti']))
        ->assertOk()
        ->assertSee('Siti Cuti')
        ->assertDontSee('Budi Hadir');

    $this->actingAs($admin)->get(route('admin.monitoring.index', ['status' => 'hadir']))
        ->assertOk()
        ->assertSee('Budi Hadir')
        ->assertDontSee('Siti Cuti');
});

it('rejects invalid monitoring filters', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.monitoring.index', [
            'tanggal' => '25-11-2026',
            'id_dept' => 999999,
            'status' => 'alpha',
        ]))
        ->assertSessionHasErrors(['tanggal', 'id_dept', 'status']);
});

it('shows the empty state when no row matches the filter', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    departemenMonitoring('ADKL', 'Dinas Kependudukan');

    $this->actingAs($admin)->get(route('admin.monitoring.index'))
        ->assertOk()
        ->assertSee('Tidak ada baris presensi yang cocok dengan filter ini.');
});
