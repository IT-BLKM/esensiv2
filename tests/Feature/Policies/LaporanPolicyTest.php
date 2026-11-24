<?php

use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(LazilyRefreshDatabase::class);

/**
 * @return array{User, Pegawai}
 */
function pegawaiUntukLaporanPolicy(string $role = 'pegawai'): array
{
    $user = User::factory()->create(['role' => $role]);

    return [$user, Pegawai::factory()->create(['user_id' => $user->id])];
}

it('allows admins to open any pegawai laporan', function () {
    [$admin] = pegawaiUntukLaporanPolicy('admin');
    [, $pemilik] = pegawaiUntukLaporanPolicy();

    expect(Gate::forUser($admin)->allows('viewAny', Pegawai::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('view', $pemilik))->toBeTrue();
});

it('allows pegawai to open only their own laporan', function () {
    [$pemilikUser, $pemilik] = pegawaiUntukLaporanPolicy();
    [, $orangLain] = pegawaiUntukLaporanPolicy();

    expect(Gate::forUser($pemilikUser)->allows('viewAny', Pegawai::class))->toBeTrue()
        ->and(Gate::forUser($pemilikUser)->allows('view', $pemilik))->toBeTrue()
        ->and(Gate::forUser($pemilikUser)->denies('view', $orangLain))->toBeTrue();
});

it('denies laporan access to a user without a pegawai record', function () {
    $user = User::factory()->create(['role' => 'pegawai']);
    [, $pemilik] = pegawaiUntukLaporanPolicy();

    expect(Gate::forUser($user)->denies('viewAny', Pegawai::class))->toBeTrue()
        ->and(Gate::forUser($user)->denies('view', $pemilik))->toBeTrue();
});
