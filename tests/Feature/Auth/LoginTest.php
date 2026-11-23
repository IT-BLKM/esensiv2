<?php

use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

it('renders the single login form for guests', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Email atau NIP')
        ->assertSee('Masuk');
});

it('authenticates an admin by email and redirects to the admin panel', function () {
    $admin = User::factory()->create([
        'email' => 'admin@esensiv.test',
        'password' => 'rahasia123',
        'role' => 'admin',
    ]);

    $this->post(route('login.store'), [
        'identitas' => 'admin@esensiv.test',
        'password' => 'rahasia123',
    ])
        ->assertRedirect('/admin/dashboard')
        ->assertSessionHasNoErrors();

    $this->assertAuthenticatedAs($admin);
});

it('authenticates a pegawai by NIP and redirects to the dashboard', function () {
    $user = User::factory()->create(['password' => 'rahasia123']);

    // PegawaiFactory owns user_id, so the linking key is supplied explicitly here.
    Pegawai::factory()->create([
        'user_id' => $user->id,
        'nip' => '199302032022032001',
    ]);

    $this->post(route('login.store'), [
        'identitas' => '199302032022032001',
        'password' => 'rahasia123',
    ])->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($user);
});

it('rejects an unknown NIP with a generic error message', function () {
    $this->post(route('login.store'), [
        'identitas' => '000000000000000000',
        'password' => 'rahasia123',
    ])->assertSessionHasErrors(['identitas' => 'Email/NIP atau password salah.']);

    $this->assertGuest();
});

it('rejects a wrong password with the same generic error message', function () {
    User::factory()->create([
        'email' => 'pegawai@esensiv.test',
        'password' => 'rahasia123',
    ]);

    $this->post(route('login.store'), [
        'identitas' => 'pegawai@esensiv.test',
        'password' => 'salah',
    ])->assertSessionHasErrors(['identitas' => 'Email/NIP atau password salah.']);

    $this->assertGuest();
});

it('requires both identity and password', function () {
    $this->post(route('login.store'), [])
        ->assertSessionHasErrors(['identitas', 'password']);
});

it('logs out and clears the session and remember token', function () {
    $user = User::factory()->create(['password' => 'rahasia123']);

    $this->post(route('login.store'), [
        'identitas' => $user->email,
        'password' => 'rahasia123',
        'remember' => true,
    ])->assertRedirect('/dashboard');

    $tokenSebelumLogout = $user->fresh()->remember_token;

    $this->post(route('logout'))->assertRedirect(route('login'));

    $this->assertGuest();
    expect($user->fresh()->remember_token)->not->toBe($tokenSebelumLogout);
});

it('redirects guests to the login page when they log out', function () {
    $this->post(route('logout'))->assertRedirect(route('login'));
});
