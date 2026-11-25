<?php

use App\Models\Departemen;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(LazilyRefreshDatabase::class);

it('allows only admins to manage departemen', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $pegawai = User::factory()->create(['role' => 'pegawai']);
    $departemen = Departemen::factory()->create();

    expect(Gate::forUser($admin)->allows('viewAny', Departemen::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('create', Departemen::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('update', $departemen))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('delete', $departemen))->toBeTrue()
        ->and(Gate::forUser($pegawai)->denies('viewAny', Departemen::class))->toBeTrue()
        ->and(Gate::forUser($pegawai)->denies('create', Departemen::class))->toBeTrue()
        ->and(Gate::forUser($pegawai)->denies('update', $departemen))->toBeTrue()
        ->and(Gate::forUser($pegawai)->denies('delete', $departemen))->toBeTrue();
});
