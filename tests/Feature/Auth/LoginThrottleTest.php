<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

it('rejects the sixth login attempt within a minute with 429', function () {
    $user = User::factory()->create([
        'email' => 'pegawai@esensiv.test',
        'password' => 'rahasia123',
    ]);

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post(route('login.store'), [
            'identitas' => $user->email,
            'password' => 'salah',
        ])->assertSessionHasErrors(['identitas' => 'Email/NIP atau password salah.']);
    }

    $this->post(route('login.store'), [
        'identitas' => $user->email,
        'password' => 'rahasia123',
    ])
        ->assertStatus(429)
        ->assertDontSee($user->email)
        ->assertDontSee('rahasia123');

    $this->assertGuest();
});

it('allows login again after the throttle window passes', function () {
    $user = User::factory()->create(['password' => 'rahasia123']);

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post(route('login.store'), [
            'identitas' => $user->email,
            'password' => 'salah',
        ]);
    }

    $this->travel(61)->seconds();

    $this->post(route('login.store'), [
        'identitas' => $user->email,
        'password' => 'rahasia123',
    ])->assertRedirect('/dashboard')->assertSessionHasNoErrors();

    $this->assertAuthenticatedAs($user);
});

it('logs failed attempts without recording the password', function () {
    Log::spy();

    $this->post(route('login.store'), [
        'identitas' => 'tidak-ada@esensiv.test',
        'password' => 'rahasia123',
    ])->assertSessionHasErrors(['identitas' => 'Email/NIP atau password salah.']);

    Log::shouldHaveReceived('warning')
        ->once()
        ->with('Percobaan login gagal.', Mockery::on(function (array $context) {
            return $context['identitas'] === 'tidak-ada@esensiv.test'
                && $context['ip'] === '127.0.0.1'
                && ! array_key_exists('password', $context);
        }));
});

it('logs a successful attempt with the user id', function () {
    $user = User::factory()->create(['password' => 'rahasia123']);

    Log::spy();

    $this->post(route('login.store'), [
        'identitas' => $user->email,
        'password' => 'rahasia123',
    ])->assertRedirect('/dashboard');

    Log::shouldHaveReceived('info')
        ->once()
        ->with('Login berhasil.', Mockery::on(fn (array $context): bool => $context['user_id'] === $user->id));
});

it('shows the same generic error for an unknown email as for a wrong password', function () {
    $this->post(route('login.store'), [
        'identitas' => 'tidak-ada@esensiv.test',
        'password' => 'rahasia123',
    ])->assertSessionHasErrors(['identitas' => 'Email/NIP atau password salah.']);

    $this->assertGuest();
});
