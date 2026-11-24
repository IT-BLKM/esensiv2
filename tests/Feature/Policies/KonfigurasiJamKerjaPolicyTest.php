<?php

use App\Models\KonfigurasiJamKerja;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(LazilyRefreshDatabase::class);

it('allows only admins to configure employee shifts', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $pegawai = User::factory()->create(['role' => 'pegawai']);

    expect(Gate::forUser($admin)->allows('viewAny', KonfigurasiJamKerja::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('update', KonfigurasiJamKerja::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('delete', KonfigurasiJamKerja::class))->toBeTrue()
        ->and(Gate::forUser($pegawai)->denies('viewAny', KonfigurasiJamKerja::class))->toBeTrue()
        ->and(Gate::forUser($pegawai)->denies('update', KonfigurasiJamKerja::class))->toBeTrue()
        ->and(Gate::forUser($pegawai)->denies('delete', KonfigurasiJamKerja::class))->toBeTrue();
});
