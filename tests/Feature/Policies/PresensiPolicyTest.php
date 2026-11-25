<?php

use App\Models\Pegawai;
use App\Models\Presensi;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(LazilyRefreshDatabase::class);

/**
 * @return array{User, Pegawai}
 */
function pegawaiUntukPresensiPolicy(string $role = 'pegawai'): array
{
    $user = User::factory()->create(['role' => $role]);

    return [$user, Pegawai::factory()->create(['user_id' => $user->id])];
}

it('allows admins to view any presensi', function () {
    [$admin] = pegawaiUntukPresensiPolicy('admin');
    [, $pemilik] = pegawaiUntukPresensiPolicy();
    $presensi = Presensi::factory()->create(['id_pegawai' => $pemilik->id]);

    expect(Gate::forUser($admin)->allows('viewAny', Presensi::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('view', $presensi))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('update', $presensi))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('delete', $presensi))->toBeTrue();
});

it('allows pegawai to view only their own presensi', function () {
    [$pemilikUser, $pemilik] = pegawaiUntukPresensiPolicy();
    [$orangLain] = pegawaiUntukPresensiPolicy();
    $presensi = Presensi::factory()->create(['id_pegawai' => $pemilik->id]);

    expect(Gate::forUser($pemilikUser)->allows('viewAny', Presensi::class))->toBeTrue()
        ->and(Gate::forUser($pemilikUser)->allows('view', $presensi))->toBeTrue()
        ->and(Gate::forUser($orangLain)->denies('view', $presensi))->toBeTrue()
        ->and(Gate::forUser($orangLain)->denies('update', $presensi))->toBeTrue()
        ->and(Gate::forUser($orangLain)->denies('delete', $presensi))->toBeTrue();
});

it('denies presensi access to a user without a pegawai record', function () {
    $user = User::factory()->create(['role' => 'pegawai']);
    $presensi = Presensi::factory()->create();

    expect(Gate::forUser($user)->denies('viewAny', Presensi::class))->toBeTrue()
        ->and(Gate::forUser($user)->denies('view', $presensi))->toBeTrue();
});
