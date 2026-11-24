<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(LazilyRefreshDatabase::class);

it('allows only admins to reset another user password', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $pegawai = User::factory()->create(['role' => 'pegawai']);
    $target = User::factory()->create(['role' => 'pegawai']);

    expect(Gate::forUser($admin)->allows('resetPassword', $target))->toBeTrue()
        ->and(Gate::forUser($pegawai)->denies('resetPassword', $target))->toBeTrue();
});
