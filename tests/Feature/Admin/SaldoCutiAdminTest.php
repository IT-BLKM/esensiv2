<?php

use App\Models\Cuti;
use App\Models\Pegawai;
use App\Models\SaldoCuti;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->travelTo(CarbonImmutable::parse('2026-11-25 09:00:00', 'Asia/Jakarta'));
    config(['presensi.zona_waktu' => 'Asia/Jakarta']);
});

function cutiTahunanSaldo(): Cuti
{
    return Cuti::factory()->create([
        'kode' => Cuti::KODE_TAHUNAN,
        'nama' => 'Cuti Tahunan',
        'maks_hari' => 12,
        'kuota' => 12,
        'maks_bawa' => 6,
    ]);
}

it('redirects guests and pegawai away from saldo cuti pages', function () {
    $pegawai = Pegawai::factory()->create();

    $this->get(route('admin.saldo-cuti.index'))->assertRedirect(route('login'));
    $this->get(route('admin.saldo-cuti.edit', $pegawai))->assertRedirect(route('login'));

    $user = User::factory()->create(['role' => 'pegawai']);

    $this->actingAs($user)->get(route('admin.saldo-cuti.index'))->assertRedirect('/dashboard');
    $this->actingAs($user)->get(route('admin.saldo-cuti.edit', $pegawai))->assertRedirect('/dashboard');
});

it('shows the saldo cuti page with carry-over years', function () {
    cutiTahunanSaldo();
    $pegawai = Pegawai::factory()->create(['nama' => 'Budi Santoso']);

    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->get(route('admin.saldo-cuti.index'))
        ->assertOk()
        ->assertSee('Saldo awal cuti tahunan')
        ->assertSee('Budi Santoso')
        ->assertSee('Tabungan 2025')
        ->assertSee('Tabungan 2024');
});

it('lets an admin save the carry-over balance', function () {
    $cuti = cutiTahunanSaldo();
    $pegawai = Pegawai::factory()->create();

    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->put(route('admin.saldo-cuti.update', $pegawai), [
            'saldo_tahun_lalu' => 6,
            'saldo_dua_tahun_lalu' => 4,
        ])
        ->assertRedirect(route('admin.saldo-cuti.index'))
        ->assertSessionHas('success', "Saldo awal cuti {$pegawai->nama} berhasil disimpan.");

    $saldo = SaldoCuti::query()
        ->where('id_pegawai', $pegawai->id)
        ->where('id_cuti', $cuti->id)
        ->pluck('saldo', 'tahun_sumber');

    expect($saldo[2025])->toBe(6)
        ->and($saldo[2024])->toBe(4);

    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->get(route('admin.saldo-cuti.edit', $pegawai))
        ->assertOk()
        ->assertSee('value="6"', false)
        ->assertSee('value="4"', false)
        ->assertSee('22 hari');
});

it('rejects a carry-over above the configured maximum', function () {
    cutiTahunanSaldo();
    $pegawai = Pegawai::factory()->create();

    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->put(route('admin.saldo-cuti.update', $pegawai), [
            'saldo_tahun_lalu' => 7,
            'saldo_dua_tahun_lalu' => -1,
        ])
        ->assertSessionHasErrors([
            'saldo_tahun_lalu' => 'Saldo tahun lalu maksimal 6 hari.',
            'saldo_dua_tahun_lalu' => 'Saldo dua tahun lalu tidak boleh negatif.',
        ]);
});
