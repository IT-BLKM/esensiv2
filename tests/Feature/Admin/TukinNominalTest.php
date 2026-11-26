<?php

use App\Models\JamKerja;
use App\Models\Pegawai;
use App\Models\Presensi;
use App\Models\TukinNominal;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->travelTo(CarbonImmutable::parse('2026-11-25 09:00:00', 'Asia/Jakarta'));
    config(['presensi.zona_waktu' => 'Asia/Jakarta']);
});

it('redirects guests and pegawai away from nominal tukin pages', function () {
    $this->get(route('admin.tukin-nominal.index'))->assertRedirect(route('login'));

    $pegawai = User::factory()->create(['role' => 'pegawai']);
    $this->actingAs($pegawai)->get(route('admin.tukin-nominal.index'))->assertRedirect('/dashboard');
});

it('lets an admin manage nominal tukin per jabatan', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('admin.tukin-nominal.store'), [
        'jabatan' => 'Staf Program',
        'nominal' => 10000000,
        'keterangan' => 'Nominal demo',
    ])->assertRedirect(route('admin.tukin-nominal.index'));

    $nominal = TukinNominal::query()->firstOrFail();
    expect($nominal->nominal)->toBe(10000000);

    $this->actingAs($admin)->put(route('admin.tukin-nominal.update', $nominal), [
        'jabatan' => 'Staf Program',
        'nominal' => 12000000,
        'keterangan' => 'Revisi',
    ])->assertRedirect(route('admin.tukin-nominal.index'));

    expect($nominal->refresh()->nominal)->toBe(12000000);
});

it('shows rupiah deductions on the tukin detail when nominal is configured', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $pegawai = Pegawai::factory()->create([
        'nama' => 'Budi Santoso',
        'jabatan' => 'Staf Program',
    ]);
    $shift = JamKerja::factory()->create([
        'kode' => 'JK01',
        'nama' => 'Shift Pagi',
        'jam_masuk' => '07:30:00',
        'jam_pulang' => '16:00:00',
    ]);
    Presensi::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_jam_kerja' => $shift->id,
        'tgl_presensi' => '2026-11-02',
        'jam_masuk' => '07:40:00',
        'jam_keluar' => '16:00:00',
        'status' => 'hadir',
    ]);
    TukinNominal::query()->create(['jabatan' => 'Staf Program', 'nominal' => 10000000]);

    $this->actingAs($admin)->get(route('admin.tukin.index', [
        'id_pegawai' => $pegawai->id,
        'bulan' => 11,
        'tahun' => 2026,
    ]))
        ->assertOk()
        ->assertSee('Nominal dasar')
        ->assertSee('Rp 10.000.000')
        ->assertSee('Rp 100.000');
});

it('rejects duplicate nominal for the same jabatan', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    TukinNominal::query()->create(['jabatan' => 'Bendahara', 'nominal' => 9000000]);

    $this->actingAs($admin)->post(route('admin.tukin-nominal.store'), [
        'jabatan' => 'Bendahara',
        'nominal' => 8000000,
    ])->assertSessionHasErrors([
        'jabatan' => 'Nominal untuk jabatan ini sudah ada.',
    ]);
});
