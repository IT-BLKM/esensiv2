<?php

use App\Models\Cuti;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(LazilyRefreshDatabase::class);

it('allows only admins to manage cuti master data', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $pegawai = User::factory()->create(['role' => 'pegawai']);
    $cuti = Cuti::factory()->create();

    expect(Gate::forUser($admin)->allows('viewAny', Cuti::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('create', Cuti::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('update', $cuti))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('delete', $cuti))->toBeTrue()
        ->and(Gate::forUser($pegawai)->denies('viewAny', Cuti::class))->toBeTrue()
        ->and(Gate::forUser($pegawai)->denies('create', Cuti::class))->toBeTrue()
        ->and(Gate::forUser($pegawai)->denies('update', $cuti))->toBeTrue()
        ->and(Gate::forUser($pegawai)->denies('delete', $cuti))->toBeTrue();
});
