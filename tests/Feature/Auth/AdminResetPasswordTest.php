<?php

use App\Models\PasswordResetLog;
use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

function pegawaiUntukReset(): Pegawai
{
    $user = User::factory()->create(['password' => 'lama12345']);

    return Pegawai::factory()->create([
        'user_id' => $user->id,
        'nip' => '199302032022032001',
        'nama' => 'Budi Santoso',
    ]);
}

it('redirects guests to the login page', function () {
    $pegawai = pegawaiUntukReset();

    $this->get(route('admin.pegawai.password.edit', $pegawai))->assertRedirect(route('login'));
    $this->post(route('admin.pegawai.password.update', $pegawai), [])->assertRedirect(route('login'));
});

it('does not let a pegawai reach the admin reset form', function () {
    $pegawai = pegawaiUntukReset();
    $actor = User::factory()->create(['role' => 'pegawai']);

    $this->actingAs($actor)->get(route('admin.pegawai.password.edit', $pegawai))
        ->assertRedirect('/dashboard');

    expect(Gate::forUser($actor)->denies('resetPassword', $pegawai->user))->toBeTrue();
});

it('lets an admin open the reset form for a pegawai', function () {
    $pegawai = pegawaiUntukReset();
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get(route('admin.pegawai.password.edit', $pegawai))
        ->assertOk()
        ->assertSee('Budi Santoso')
        ->assertSee('199302032022032001')
        ->assertSee(route('admin.pegawai.password.update', $pegawai), false);
});

it('hashes the new password and records who reset it', function () {
    $pegawai = pegawaiUntukReset();
    $admin = User::factory()->create(['role' => 'admin']);
    $targetUserId = $pegawai->user_id;

    $this->actingAs($admin)->post(route('admin.pegawai.password.update', $pegawai), [
        'password' => 'rahasia789',
        'password_confirmation' => 'rahasia789',
    ])
        ->assertRedirect(route('admin.dashboard'))
        ->assertSessionHas('status', 'Password Budi Santoso berhasil direset.')
        ->assertSessionHasNoErrors();

    expect(Hash::check('rahasia789', User::findOrFail($targetUserId)->password))->toBeTrue();

    $log = PasswordResetLog::firstOrFail();

    expect($log->admin_id)->toBe($admin->id)
        ->and($log->id_pegawai)->toBe($pegawai->id)
        ->and($log->created_at)->not->toBeNull();
});

it('validates the new password with eight characters and confirmation', function (string $baru, string $konfirmasi) {
    $pegawai = pegawaiUntukReset();
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('admin.pegawai.password.update', $pegawai), [
        'password' => $baru,
        'password_confirmation' => $konfirmasi,
    ])->assertSessionHasErrors('password');

    expect(Hash::check('lama12345', $pegawai->user->fresh()->password))->toBeTrue();
    expect(PasswordResetLog::count())->toBe(0);
})->with([
    'terlalu pendek' => ['pendek', 'pendek'],
    'konfirmasi beda' => ['rahasia789', 'lain12345'],
]);

it('only accepts POST for the reset action', function () {
    $pegawai = pegawaiUntukReset();
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get(route('admin.pegawai.password.update', $pegawai))
        ->assertStatus(405);

    $this->get("/pegawai/{$pegawai->nip}/resetpassword")->assertNotFound();
});

it('never displays the new password after a reset', function () {
    $pegawai = pegawaiUntukReset();
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->followingRedirects()->post(route('admin.pegawai.password.update', $pegawai), [
        'password' => 'rahasia789',
        'password_confirmation' => 'rahasia789',
    ])
        ->assertOk()
        ->assertSee('Dashboard admin')
        ->assertDontSee('rahasia789');
});
