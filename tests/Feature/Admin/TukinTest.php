<?php

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
    config([
        'presensi.zona_waktu' => 'Asia/Jakarta',
        'presensi.kode_jam_kerja_diabaikan' => ['JK03'],
    ]);
});

function shiftTukin(): JamKerja
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

function pegawaiTukin(string $nama = 'Budi Santoso', string $nip = '199302032022032001'): Pegawai
{
    return Pegawai::factory()->create(['nama' => $nama, 'nip' => $nip]);
}

function presensiTukin(Pegawai $pegawai, JamKerja $shift, string $tanggal, string $masuk, string $keluar): Presensi
{
    return Presensi::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_jam_kerja' => $shift->id,
        'tgl_presensi' => $tanggal,
        'jam_masuk' => $masuk,
        'jam_keluar' => $keluar,
        'status' => 'hadir',
    ]);
}

it('redirects guests and pegawai away from tukin pages', function () {
    $this->get(route('admin.tukin.index'))->assertRedirect(route('login'));
    $this->get(route('admin.tukin.export.pdf'))->assertRedirect(route('login'));
    $this->get(route('admin.tukin.export.excel'))->assertRedirect(route('login'));

    $pegawai = User::factory()->create(['role' => 'pegawai']);

    $this->actingAs($pegawai)->get(route('admin.tukin.index'))->assertRedirect('/dashboard');
    $this->actingAs($pegawai)->get(route('admin.tukin.export.pdf'))->assertRedirect('/dashboard');
});

it('shows the tukin recap with tl and psw levels', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $shift = shiftTukin();
    $pegawai = pegawaiTukin();

    presensiTukin($pegawai, $shift, '2026-11-02', '08:00:00', '16:00:00'); // TL1 30 menit
    presensiTukin($pegawai, $shift, '2026-11-03', '07:30:00', '15:30:00'); // PSW1 30 menit

    $this->actingAs($admin)->get(route('admin.tukin.index', [
        'bulan' => 11,
        'tahun' => 2026,
    ]))
        ->assertOk()
        ->assertSee('Tunjangan Kinerja (TUKIN)')
        ->assertSee('Budi Santoso')
        ->assertSee('Rekap pegawai')
        ->assertSee('1,00%')
        ->assertSee('0,50%')
        ->assertSee('1,50%');
});

it('shows the detail of where the deduction came from when a pegawai is selected', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $shift = shiftTukin();
    $pegawai = pegawaiTukin();

    presensiTukin($pegawai, $shift, '2026-11-02', '08:00:00', '16:00:00');
    presensiTukin($pegawai, $shift, '2026-11-03', '07:30:00', '15:30:00');

    $this->actingAs($admin)->get(route('admin.tukin.index', [
        'id_pegawai' => $pegawai->id,
        'bulan' => 11,
        'tahun' => 2026,
    ]))
        ->assertOk()
        ->assertSee('Rincian Budi Santoso')
        ->assertSee('02 Nov 2026')
        ->assertSee('03 Nov 2026')
        ->assertSee('TL1')
        ->assertSee('PSW1')
        ->assertSee('30 menit')
        ->assertSee('Excel pegawai ini')
        ->assertSee('PDF pegawai ini');
});

it('downloads the tukin recap as excel and pdf', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $shift = shiftTukin();
    $pegawai = pegawaiTukin();

    presensiTukin($pegawai, $shift, '2026-11-02', '08:00:00', '16:00:00');

    $this->actingAs($admin)->get(route('admin.tukin.export.excel', [
        'bulan' => 11,
        'tahun' => 2026,
    ]))
        ->assertOk()
        ->assertDownload('rekap-tukin-semua-2026-11.xlsx')
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $response = $this->actingAs($admin)->get(route('admin.tukin.export.pdf', [
        'bulan' => 11,
        'tahun' => 2026,
    ]));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload('rekap-tukin-semua-2026-11.pdf');

    expect($response->getContent())->toStartWith('%PDF');
});

it('downloads the per-pegawai tukin excel and pdf', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $shift = shiftTukin();
    $pegawai = pegawaiTukin();

    presensiTukin($pegawai, $shift, '2026-11-02', '08:00:00', '16:00:00');

    $this->actingAs($admin)->get(route('admin.tukin.export.excel', [
        'id_pegawai' => $pegawai->id,
        'bulan' => 11,
        'tahun' => 2026,
    ]))
        ->assertOk()
        ->assertDownload('laporan-tukin-budi-santoso-2026-11.xlsx');

    $response = $this->actingAs($admin)->get(route('admin.tukin.export.pdf', [
        'id_pegawai' => $pegawai->id,
        'bulan' => 11,
        'tahun' => 2026,
    ]));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload('laporan-tukin-budi-santoso-2026-11.pdf');

    expect($response->getContent())->toStartWith('%PDF');
});

it('rejects invalid tukin filters', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get(route('admin.tukin.index', [
        'id_pegawai' => 999999,
        'bulan' => 13,
        'tahun' => 1999,
    ]))->assertSessionHasErrors(['id_pegawai', 'bulan', 'tahun']);
});
