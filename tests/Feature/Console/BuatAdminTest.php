<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

it('creates an admin with a hashed password', function () {
    $this->artisan('admin:create', [
        'email' => 'admin@example.test',
        '--nama' => 'Admin Baru',
        '--password' => 'rahasia-kuat-123',
    ])->assertSuccessful();

    $admin = User::query()->where('email', 'admin@example.test')->firstOrFail();

    expect($admin->role)->toBe('admin')
        ->and($admin->nama)->toBe('Admin Baru')
        ->and(Hash::check('rahasia-kuat-123', $admin->password))->toBeTrue();
});

it('rejects a short admin password', function () {
    $this->artisan('admin:create', [
        'email' => 'admin@example.test',
        '--password' => 'pendek',
    ])->assertFailed();

    expect(User::query()->where('email', 'admin@example.test')->exists())->toBeFalse();
});

it('rejects an email that already belongs to a non-admin account', function () {
    User::factory()->create(['email' => 'pegawai@example.test', 'role' => 'pegawai']);

    $this->artisan('admin:create', [
        'email' => 'pegawai@example.test',
        '--password' => 'rahasia-kuat-123',
    ])->assertFailed();

    expect(User::query()->where('email', 'pegawai@example.test')->value('role'))->toBe('pegawai');
});
