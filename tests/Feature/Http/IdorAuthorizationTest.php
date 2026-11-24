<?php

use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\Presensi;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/**
 * @return array{User, Pegawai}
 */
function akunPegawaiUntukHttp(string $role = 'pegawai'): array
{
    $user = User::factory()->create(['role' => $role]);

    return [$user, Pegawai::factory()->create(['user_id' => $user->id])];
}

it('redirects guests away from pegawai data endpoints', function () {
    $pengajuan = Pengajuan::factory()->create();
    $presensi = Presensi::factory()->create();
    $pegawai = $pengajuan->pegawai;

    $this->get(route('pegawai.pengajuan.index'))->assertRedirect(route('login'));
    $this->get(route('pegawai.pengajuan.show', $pengajuan))->assertRedirect(route('login'));
    $this->get(route('pegawai.presensi.show', $presensi))->assertRedirect(route('login'));
    $this->get(route('pegawai.laporan.show', $pegawai))->assertRedirect(route('login'));
});

it('returns 403 when pegawai a accesses pengajuan of pegawai b', function () {
    [$pegawaiA] = akunPegawaiUntukHttp();
    [, $pegawaiB] = akunPegawaiUntukHttp();
    $pengajuanB = Pengajuan::factory()->create([
        'id_pegawai' => $pegawaiB->id,
        'keterangan' => 'Pengajuan pegawai B',
    ]);
    $payload = [
        'id_cuti' => $pengajuanB->id_cuti,
        'tanggal_mulai' => '2026-11-20',
        'tanggal_berakhir' => '2026-11-22',
        'jenis' => 'cuti',
        'keterangan' => 'Diubah pegawai A',
    ];

    $this->actingAs($pegawaiA)
        ->get(route('pegawai.pengajuan.show', $pengajuanB))
        ->assertForbidden();
    $this->actingAs($pegawaiA)
        ->get(route('pegawai.pengajuan.edit', $pengajuanB))
        ->assertForbidden();
    $this->actingAs($pegawaiA)
        ->put(route('pegawai.pengajuan.update', $pengajuanB), $payload)
        ->assertForbidden();
    $this->actingAs($pegawaiA)
        ->delete(route('pegawai.pengajuan.destroy', $pengajuanB))
        ->assertForbidden();

    $this->assertModelExists($pengajuanB);
    $this->assertDatabaseHas('pengajuan', [
        'id' => $pengajuanB->id,
        'keterangan' => 'Pengajuan pegawai B',
    ]);
});

it('lets pegawai and admin access only permitted pengajuan', function () {
    [$pegawaiAUser, $pegawaiA] = akunPegawaiUntukHttp();
    [$admin] = akunPegawaiUntukHttp('admin');
    $milikA = Pengajuan::factory()->create(['id_pegawai' => $pegawaiA->id]);

    $this->actingAs($pegawaiAUser)
        ->get(route('pegawai.pengajuan.index'))
        ->assertOk()
        ->assertJsonPath('0.id', $milikA->id);
    $this->actingAs($pegawaiAUser)
        ->get(route('pegawai.pengajuan.show', $milikA))
        ->assertOk();
    $this->actingAs($admin)
        ->get(route('pegawai.pengajuan.show', $milikA))
        ->assertOk();
    $this->actingAs($pegawaiAUser)
        ->delete(route('pegawai.pengajuan.destroy', $milikA))
        ->assertNoContent();

    $this->assertModelMissing($milikA);
});

it('returns 403 when pegawai a accesses presensi or laporan of pegawai b', function () {
    [$pegawaiA] = akunPegawaiUntukHttp();
    [, $pegawaiB] = akunPegawaiUntukHttp();
    $presensiB = Presensi::factory()->create(['id_pegawai' => $pegawaiB->id]);

    $this->actingAs($pegawaiA)
        ->get(route('pegawai.presensi.show', $presensiB))
        ->assertForbidden();
    $this->actingAs($pegawaiA)
        ->get(route('pegawai.laporan.show', $pegawaiB))
        ->assertForbidden();
});

it('lets pegawai and admin access permitted presensi and laporan', function () {
    [$pegawaiAUser, $pegawaiA] = akunPegawaiUntukHttp();
    [$admin] = akunPegawaiUntukHttp('admin');
    $presensiA = Presensi::factory()->create(['id_pegawai' => $pegawaiA->id]);

    $this->actingAs($pegawaiAUser)
        ->get(route('pegawai.presensi.index'))
        ->assertOk()
        ->assertJsonPath('0.id', $presensiA->id);
    $this->actingAs($pegawaiAUser)
        ->get(route('pegawai.presensi.show', $presensiA))
        ->assertOk();
    $this->actingAs($pegawaiAUser)
        ->get(route('pegawai.laporan.show', $pegawaiA))
        ->assertOk()
        ->assertJsonPath('id_pegawai', $pegawaiA->id);
    $this->actingAs($admin)
        ->get(route('pegawai.presensi.show', $presensiA))
        ->assertOk();
    $this->actingAs($admin)
        ->get(route('pegawai.laporan.show', $pegawaiA))
        ->assertOk();
});
