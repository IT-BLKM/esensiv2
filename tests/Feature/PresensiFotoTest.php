<?php

use App\Models\Pegawai;
use App\Models\Presensi;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
});

function pemilikFotoPresensi(): array
{
    $user = User::factory()->create(['role' => 'pegawai']);
    $pegawai = Pegawai::factory()->create(['user_id' => $user->id]);

    $presensi = Presensi::factory()->create([
        'id_pegawai' => $pegawai->id,
        'foto_masuk' => 'presensi/uji/masuk.png',
        'foto_keluar' => 'presensi/uji/keluar.png',
    ]);

    Storage::disk('local')->put('presensi/uji/masuk.png', 'foto-masuk');
    Storage::disk('local')->put('presensi/uji/keluar.png', 'foto-keluar');

    return [$user, $pegawai, $presensi];
}

it('serves the presensi photo to its owner from the private disk', function () {
    [$user, , $presensi] = pemilikFotoPresensi();

    $this->actingAs($user)
        ->get(route('pegawai.presensi.foto', [$presensi, 'masuk']))
        ->assertOk()
        ->assertHeader('content-type', 'image/png');
});

it('lets admin access a presensi photo', function () {
    [, , $presensi] = pemilikFotoPresensi();
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('pegawai.presensi.foto', [$presensi, 'keluar']))
        ->assertOk();
});

it('forbids another pegawai from opening a presensi photo', function () {
    [, , $presensi] = pemilikFotoPresensi();
    $orangLain = User::factory()->create(['role' => 'pegawai']);
    Pegawai::factory()->create(['user_id' => $orangLain->id]);

    $this->actingAs($orangLain)
        ->get(route('pegawai.presensi.foto', [$presensi, 'masuk']))
        ->assertForbidden();
});

it('returns 404 for a missing photo or invalid jenis', function () {
    [, , $presensi] = pemilikFotoPresensi();
    $user = $presensi->pegawai->user;

    $tanpaFoto = Presensi::factory()->create([
        'id_pegawai' => $presensi->id_pegawai,
        'tgl_presensi' => '2026-11-20',
        'foto_masuk' => null,
        'foto_keluar' => null,
    ]);

    $this->actingAs($user)
        ->get(route('pegawai.presensi.foto', [$tanpaFoto, 'masuk']))
        ->assertNotFound();

    $this->actingAs($user)
        ->get(route('pegawai.presensi.foto', [$presensi, 'samping']))
        ->assertNotFound();
});

it('redirects guests and does not expose the generic private storage route', function () {
    [, , $presensi] = pemilikFotoPresensi();

    $this->get(route('pegawai.presensi.foto', [$presensi, 'masuk']))
        ->assertRedirect(route('login'));

    $this->get('/storage/presensi/uji/masuk.png')->assertNotFound();
});
