<?php

use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(LazilyRefreshDatabase::class);

/**
 * @return array{User, Pegawai}
 */
function pegawaiUntukPengajuanPolicy(string $role = 'pegawai'): array
{
    $user = User::factory()->create(['role' => $role]);

    return [$user, Pegawai::factory()->create(['user_id' => $user->id])];
}

it('allows admins to view, update and delete any pengajuan', function () {
    [$admin] = pegawaiUntukPengajuanPolicy('admin');
    [, $pemilik] = pegawaiUntukPengajuanPolicy();
    $pengajuan = Pengajuan::factory()->create(['id_pegawai' => $pemilik->id]);

    expect(Gate::forUser($admin)->allows('viewAny', Pengajuan::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('create', Pengajuan::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('view', $pengajuan))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('update', $pengajuan))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('delete', $pengajuan))->toBeTrue();
});

it('allows pegawai to manage only their own pengajuan', function () {
    [$pemilikUser, $pemilik] = pegawaiUntukPengajuanPolicy();
    [$orangLain] = pegawaiUntukPengajuanPolicy();
    $pengajuan = Pengajuan::factory()->create(['id_pegawai' => $pemilik->id]);

    expect(Gate::forUser($pemilikUser)->allows('viewAny', Pengajuan::class))->toBeTrue()
        ->and(Gate::forUser($pemilikUser)->allows('create', Pengajuan::class))->toBeTrue()
        ->and(Gate::forUser($pemilikUser)->allows('view', $pengajuan))->toBeTrue()
        ->and(Gate::forUser($pemilikUser)->allows('update', $pengajuan))->toBeTrue()
        ->and(Gate::forUser($pemilikUser)->allows('delete', $pengajuan))->toBeTrue()
        ->and(Gate::forUser($orangLain)->denies('view', $pengajuan))->toBeTrue()
        ->and(Gate::forUser($orangLain)->denies('update', $pengajuan))->toBeTrue()
        ->and(Gate::forUser($orangLain)->denies('delete', $pengajuan))->toBeTrue();
});

it('denies data access to a user without a pegawai record', function () {
    $user = User::factory()->create(['role' => 'pegawai']);
    $pengajuan = Pengajuan::factory()->create();

    expect(Gate::forUser($user)->denies('viewAny', Pengajuan::class))->toBeTrue()
        ->and(Gate::forUser($user)->denies('create', Pengajuan::class))->toBeTrue()
        ->and(Gate::forUser($user)->denies('view', $pengajuan))->toBeTrue();
});
