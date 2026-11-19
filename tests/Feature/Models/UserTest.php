<?php

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('stores a user with its nama, email, and role', function () {
    $user = User::factory()->create(['role' => 'admin']);

    $this->assertModelExists($user);
    expect($user->role)->toBe('admin');
});

test('defaults the role to pegawai', function () {
    $user = User::create([
        'nama' => 'Pegawai Baru',
        'email' => 'pegawai@example.com',
        'password' => 'rahasia',
    ]);

    $this->assertDatabaseHas('user', [
        'id' => $user->id,
        'role' => 'pegawai',
    ]);
});

test('rejects a user with a duplicate email', function () {
    User::factory()->create(['email' => 'sama@example.com']);

    User::factory()->create(['email' => 'sama@example.com']);
})->throws(QueryException::class);
