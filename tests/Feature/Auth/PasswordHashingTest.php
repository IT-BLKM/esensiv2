<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

it('hashes new passwords with argon2id', function () {
    $hash = Hash::make('rahasia-baru');

    expect($hash)->toStartWith('$argon2id$')
        ->and(Hash::check('rahasia-baru', $hash))->toBeTrue()
        ->and(Hash::needsRehash($hash))->toBeFalse();
});

it('verifies legacy bcrypt hashes and marks them for rehash', function () {
    $bcrypt = Hash::driver('bcrypt')->make('rahasia-lama');

    expect($bcrypt)->toStartWith('$2y$')
        ->and(Hash::check('rahasia-lama', $bcrypt))->toBeTrue()
        ->and(Hash::check('salah', $bcrypt))->toBeFalse()
        ->and(Hash::needsRehash($bcrypt))->toBeTrue();
});

it('rehashes a bcrypt password to argon2id after a successful login', function () {
    $user = User::factory()->create(['role' => 'pegawai']);
    $bcrypt = Hash::driver('bcrypt')->make('rahasia-lama');

    DB::table('user')->where('id', $user->id)->update(['password' => $bcrypt]);

    $this->post(route('login.store'), [
        'identitas' => $user->email,
        'password' => 'rahasia-lama',
    ])->assertRedirect('/dashboard');

    $hashBaru = DB::table('user')->where('id', $user->id)->value('password');

    expect($hashBaru)->toStartWith('$argon2id$')
        ->and(Hash::check('rahasia-lama', $hashBaru))->toBeTrue();
});

it('does not rehash when the login password is wrong', function () {
    $user = User::factory()->create(['role' => 'pegawai']);
    $bcrypt = Hash::driver('bcrypt')->make('rahasia-lama');

    DB::table('user')->where('id', $user->id)->update(['password' => $bcrypt]);

    $this->from(route('login'))->post(route('login.store'), [
        'identitas' => $user->email,
        'password' => 'password-salah',
    ])->assertSessionHasErrors('identitas');

    expect(DB::table('user')->where('id', $user->id)->value('password'))->toBe($bcrypt);
});

it('does not rehash an argon2id password on login', function () {
    $user = User::factory()->create(['role' => 'pegawai', 'password' => 'rahasia-baru']);
    $hashAwal = $user->fresh()->password;

    $this->post(route('login.store'), [
        'identitas' => $user->email,
        'password' => 'rahasia-baru',
    ])->assertRedirect('/dashboard');

    expect($user->fresh()->password)->toBe($hashAwal);
});

it('can disable rehash-on-login via configuration', function () {
    config(['hashing.rehash_on_login' => false]);

    $user = User::factory()->create(['role' => 'pegawai']);
    $bcrypt = Hash::driver('bcrypt')->make('rahasia-lama');

    DB::table('user')->where('id', $user->id)->update(['password' => $bcrypt]);

    $this->post(route('login.store'), [
        'identitas' => $user->email,
        'password' => 'rahasia-lama',
    ])->assertRedirect('/dashboard');

    expect(DB::table('user')->where('id', $user->id)->value('password'))->toBe($bcrypt);
});
