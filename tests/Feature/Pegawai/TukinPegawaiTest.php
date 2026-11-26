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
    config(['presensi.zona_waktu' => 'Asia/Jakarta']);
});

it('redirects guests away from the employee tukin page', function () {
    $this->get(route('tukin.index'))->assertRedirect(route('login'));
});

it('shows the logged-in pegawai own tukin detail', function () {
    $user = User::factory()->create(['role' => 'pegawai']);
    $pegawai = Pegawai::factory()->create(['user_id' => $user->id, 'nama' => 'Budi Santoso']);
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
        'jam_masuk' => '08:00:00',
        'jam_keluar' => '16:00:00',
        'status' => 'hadir',
    ]);

    $this->actingAs($user)->get(route('tukin.index', ['bulan' => 11, 'tahun' => 2026]))
        ->assertOk()
        ->assertSee('Potongan TUKIN')
        ->assertSee('Budi Santoso')
        ->assertSee('02 Nov 2026')
        ->assertSee('TL1')
        ->assertSee('1,00%');
});

it('keeps employee tukin access limited to their own account', function () {
    $user = User::factory()->create(['role' => 'pegawai']);
    $pegawai = Pegawai::factory()->create(['user_id' => $user->id]);
    $lain = Pegawai::factory()->create(['nama' => 'Pegawai Lain']);

    $this->actingAs($user)->get(route('tukin.index'))
        ->assertOk()
        ->assertSee($pegawai->nama)
        ->assertDontSee($lain->nama);
});
