<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

it('sets every pegawai password to the configured default without touching admin', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'password' => 'admin-rahasia',
    ]);

    $pegawaiA = User::factory()->create([
        'role' => 'pegawai',
        'password' => 'password-awal-a',
    ]);
    $pegawaiB = User::factory()->create([
        'role' => 'pegawai',
        'password' => '123456',
    ]);

    $this->artisan('pegawai:set-password-default', ['--force' => true])
        ->expectsOutputToContain('1 akun diubah, 1 akun sudah memakai password default')
        ->assertSuccessful();

    expect(Hash::check('123456', $pegawaiA->fresh()->password))->toBeTrue()
        ->and(Hash::check('123456', $pegawaiB->fresh()->password))->toBeTrue()
        ->and(Hash::check('admin-rahasia', $admin->fresh()->password))->toBeTrue()
        ->and(Hash::check('123456', $admin->fresh()->password))->toBeFalse();
});

it('is idempotent when run again', function () {
    $pegawai = User::factory()->create(['role' => 'pegawai', 'password' => '123456']);

    $this->artisan('pegawai:set-password-default', ['--force' => true])->assertSuccessful();
    $this->artisan('pegawai:set-password-default', ['--force' => true])
        ->expectsOutputToContain('0 akun diubah, 1 akun sudah memakai password default')
        ->assertSuccessful();

    expect(Hash::check('123456', $pegawai->fresh()->password))->toBeTrue();
});

it('uses a configurable default password', function () {
    config(['auth.default_pegawai_password' => 'rahasia-baru']);

    $pegawai = User::factory()->create(['role' => 'pegawai', 'password' => '123456']);

    $this->artisan('pegawai:set-password-default', ['--force' => true])->assertSuccessful();

    expect(Hash::check('rahasia-baru', $pegawai->fresh()->password))->toBeTrue()
        ->and(Hash::check('123456', $pegawai->fresh()->password))->toBeFalse();
});

it('asks for confirmation before changing passwords', function () {
    $pegawai = User::factory()->create(['role' => 'pegawai', 'password' => 'lama-123']);

    $this->artisan('pegawai:set-password-default')
        ->expectsConfirmation('Set password SEMUA akun pegawai menjadi "123456"?', 'no')
        ->assertSuccessful();

    expect(Hash::check('lama-123', $pegawai->fresh()->password))->toBeTrue()
        ->and(Hash::check('123456', $pegawai->fresh()->password))->toBeFalse();
});
