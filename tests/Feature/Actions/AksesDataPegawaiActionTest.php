<?php

use App\Actions\Laporan\RingkasLaporanAction;
use App\Actions\Pengajuan\CariPengajuanAction;
use App\Actions\Pengajuan\DeletePengajuanAction;
use App\Actions\Pengajuan\UpdatePengajuanAction;
use App\Actions\Presensi\CariPresensiAction;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\Presensi;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/**
 * @return array{User, Pegawai}
 */
function akunPegawaiUntukAction(string $role = 'pegawai'): array
{
    $user = User::factory()->create(['role' => $role]);

    return [$user, Pegawai::factory()->create(['user_id' => $user->id])];
}

it('scopes daftar pengajuan to the owner and shows all to admin', function () {
    [$pemilik] = akunPegawaiUntukAction();
    [, $lain] = akunPegawaiUntukAction();
    [$admin] = akunPegawaiUntukAction('admin');
    $milikSendiri = Pengajuan::factory()->create(['id_pegawai' => $pemilik->id]);
    $milikOrangLain = Pengajuan::factory()->create(['id_pegawai' => $lain->id]);

    $milik = app(CariPengajuanAction::class)($pemilik);
    $semua = app(CariPengajuanAction::class)($admin);

    expect($milik->pluck('id')->all())->toBe([$milikSendiri->id])
        ->and($semua->pluck('id')->all())->toContain($milikSendiri->id, $milikOrangLain->id);
});

it('refuses to update or delete another pegawai pengajuan', function () {
    [$orangLain] = akunPegawaiUntukAction();
    [, $pemilik] = akunPegawaiUntukAction();
    $pengajuan = Pengajuan::factory()->create([
        'id_pegawai' => $pemilik->id,
        'keterangan' => 'Keterangan asli',
    ]);

    $ubah = fn () => app(UpdatePengajuanAction::class)($orangLain, $pengajuan, [
        'id_cuti' => $pengajuan->id_cuti,
        'tanggal_mulai' => '2026-11-20',
        'tanggal_berakhir' => '2026-11-22',
        'jenis' => 'cuti',
        'keterangan' => 'Keterangan palsu',
    ]);
    $hapus = fn () => app(DeletePengajuanAction::class)($orangLain, $pengajuan);

    expect($ubah)->toThrow(AuthorizationException::class)
        ->and($hapus)->toThrow(AuthorizationException::class);
    $this->assertModelExists($pengajuan);
    $this->assertDatabaseHas('pengajuan', [
        'id' => $pengajuan->id,
        'keterangan' => 'Keterangan asli',
    ]);
});

it('lets the owner update and delete their own pengajuan', function () {
    [$pemilikUser, $pemilik] = akunPegawaiUntukAction();
    $pengajuan = Pengajuan::factory()->create(['id_pegawai' => $pemilik->id]);

    $diubah = app(UpdatePengajuanAction::class)($pemilikUser, $pengajuan, [
        'id_cuti' => $pengajuan->id_cuti,
        'tanggal_mulai' => '2026-11-20',
        'tanggal_berakhir' => '2026-11-22',
        'jenis' => 'cuti',
        'keterangan' => 'Keterangan baru',
    ]);

    expect($diubah->keterangan)->toBe('Keterangan baru');
    $this->assertDatabaseHas('pengajuan', ['id' => $pengajuan->id, 'keterangan' => 'Keterangan baru']);

    expect(app(DeletePengajuanAction::class)($pemilikUser, $pengajuan))->toBeTrue();
    $this->assertModelMissing($pengajuan);
});

it('scopes daftar presensi to the owner and shows all to admin', function () {
    [$pemilik] = akunPegawaiUntukAction();
    [, $lain] = akunPegawaiUntukAction();
    [$admin] = akunPegawaiUntukAction('admin');
    $milikSendiri = Presensi::factory()->create([
        'id_pegawai' => $pemilik->id,
        'tgl_presensi' => '2026-11-20',
    ]);
    $milikOrangLain = Presensi::factory()->create([
        'id_pegawai' => $lain->id,
        'tgl_presensi' => '2026-11-21',
    ]);

    $milik = app(CariPresensiAction::class)($pemilik);
    $semua = app(CariPresensiAction::class)($admin);

    expect($milik->pluck('id')->all())->toBe([$milikSendiri->id])
        ->and($semua->pluck('id')->all())->toContain($milikSendiri->id, $milikOrangLain->id);
});

it('refuses to open another pegawai laporan', function () {
    [$orangLain] = akunPegawaiUntukAction();
    [$pemilikUser, $pemilik] = akunPegawaiUntukAction();
    Presensi::factory()->create([
        'id_pegawai' => $pemilik->id,
        'tgl_presensi' => '2026-11-20',
    ]);

    expect(fn () => app(RingkasLaporanAction::class)($orangLain, $pemilik))
        ->toThrow(AuthorizationException::class);
    expect(app(RingkasLaporanAction::class)($pemilikUser, $pemilik))->toHaveCount(1);
});
