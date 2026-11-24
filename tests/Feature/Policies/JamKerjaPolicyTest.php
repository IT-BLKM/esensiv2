<?php

use App\Models\JamKerja;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(LazilyRefreshDatabase::class);

it('allows only admins to manage jam kerja', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $pegawai = User::factory()->create(['role' => 'pegawai']);
    $jamKerja = JamKerja::factory()->create();

    expect(Gate::forUser($admin)->allows('viewAny', JamKerja::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('create', JamKerja::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('update', $jamKerja))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('delete', $jamKerja))->toBeTrue()
        ->and(Gate::forUser($pegawai)->denies('viewAny', JamKerja::class))->toBeTrue()
        ->and(Gate::forUser($pegawai)->denies('create', JamKerja::class))->toBeTrue()
        ->and(Gate::forUser($pegawai)->denies('update', $jamKerja))->toBeTrue()
        ->and(Gate::forUser($pegawai)->denies('delete', $jamKerja))->toBeTrue();
});
