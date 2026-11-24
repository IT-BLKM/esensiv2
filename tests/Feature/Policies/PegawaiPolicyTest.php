<?php

use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(LazilyRefreshDatabase::class);

it('allows only admins to manage master pegawai data', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $pegawai = User::factory()->create(['role' => 'pegawai']);
    $target = Pegawai::factory()->create();

    expect(Gate::forUser($admin)->allows('managePegawai', Pegawai::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('managePegawai', $target))->toBeTrue()
        ->and(Gate::forUser($pegawai)->denies('managePegawai', Pegawai::class))->toBeTrue()
        ->and(Gate::forUser($pegawai)->denies('managePegawai', $target))->toBeTrue();
});
