<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

it('redirects guests to the login page', function () {
    $this->get(route('password.edit'))->assertRedirect(route('login'));
    $this->post(route('password.update'), [])->assertRedirect(route('login'));
});

it('shows the change password form to the authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('password.edit'))
        ->assertOk()
        ->assertSee('Password lama')
        ->assertSee('Password baru')
        ->assertSee('Simpan password')
        ->assertSee('Kembali ke beranda')
        ->assertSee(route('dashboard'), false)
        ->assertSee(route('password.update'), false);
});

it('changes the password when the current password is correct', function () {
    $user = User::factory()->create(['password' => 'rahasia123']);

    $this->actingAs($user)->post(route('password.update'), [
        'current_password' => 'rahasia123',
        'password' => 'rahasia456',
        'password_confirmation' => 'rahasia456',
    ])
        ->assertRedirect(route('password.edit'))
        ->assertSessionHas('status', 'Password berhasil diubah.')
        ->assertSessionHasNoErrors();

    $password = $user->fresh()->password;

    expect(Hash::check('rahasia456', $password))->toBeTrue()
        ->and(Hash::check('rahasia123', $password))->toBeFalse();
});

it('rejects a wrong current password and keeps the old password', function () {
    $user = User::factory()->create(['password' => 'rahasia123']);

    $this->actingAs($user)->post(route('password.update'), [
        'current_password' => 'salah123',
        'password' => 'rahasia456',
        'password_confirmation' => 'rahasia456',
    ])->assertSessionHasErrors(['current_password' => 'Password lama tidak sesuai.']);

    expect(Hash::check('rahasia123', $user->fresh()->password))->toBeTrue();
});

it('requires at least eight characters, a matching confirmation, and a different password', function (string $baru, string $konfirmasi) {
    $user = User::factory()->create(['password' => 'rahasia123']);

    $this->actingAs($user)->post(route('password.update'), [
        'current_password' => 'rahasia123',
        'password' => $baru,
        'password_confirmation' => $konfirmasi,
    ])->assertSessionHasErrors('password');

    expect(Hash::check('rahasia123', $user->fresh()->password))->toBeTrue();
})->with([
    'terlalu pendek' => ['pendek', 'pendek'],
    'konfirmasi beda' => ['rahasia456', 'lain12345'],
    'sama dengan lama' => ['rahasia123', 'rahasia123'],
]);

it('does not allow the hardcoded 123456 password to log in', function () {
    $user = User::factory()->create(['password' => 'rahasia123']);

    expect(Hash::check('123456', $user->password))->toBeFalse();

    $this->post(route('login.store'), [
        'identitas' => $user->email,
        'password' => '123456',
    ])->assertSessionHasErrors('identitas');

    $this->assertGuest();
});
