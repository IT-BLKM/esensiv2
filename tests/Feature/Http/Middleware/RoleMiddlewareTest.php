<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('redirects guests to the login page', function (string $path) {
    $this->get($path)->assertRedirect('/login');
})->with([
    'dashboard pegawai' => '/dashboard',
    'dashboard admin' => '/admin/dashboard',
]);

it('allows an admin on the admin dashboard', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get('/admin/dashboard')
        ->assertOk()
        ->assertSee('Dashboard admin');
});

it('allows a pegawai on the pegawai dashboard', function () {
    $pegawai = User::factory()->create(['role' => 'pegawai']);

    $this->actingAs($pegawai)->get('/dashboard')
        ->assertOk()
        ->assertSee('Dashboard pegawai');
});

it('redirects a pegawai away from the admin dashboard with a warning', function () {
    $pegawai = User::factory()->create(['role' => 'pegawai']);

    $this->actingAs($pegawai)->get('/admin/dashboard')
        ->assertRedirect('/dashboard')
        ->assertSessionHas('warning', 'Halaman ini hanya untuk akun admin.');
});

it('redirects an admin away from the pegawai dashboard with a warning', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get('/dashboard')
        ->assertRedirect('/admin/dashboard')
        ->assertSessionHas('warning', 'Halaman ini hanya untuk akun pegawai.');
});
