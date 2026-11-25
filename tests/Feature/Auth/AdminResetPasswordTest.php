<?php

use App\Actions\Pegawai\BuatPasswordAwalAction;
use App\Models\PasswordResetLog;
use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\mock;

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
});

it('lets an admin open the reset form without a password input', function () {
    $pegawai = pegawaiUntukReset();
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get(route('admin.pegawai.password.edit', $pegawai))
        ->assertOk()
        ->assertSee('Budi Santoso')
        ->assertSee('199302032022032001')
        ->assertSee(route('admin.pegawai.password.update', $pegawai), false)
        ->assertSee('Buat password baru')
        ->assertDontSee('name="password"', false);
});

it('generates a strong password, hashes it, and records who reset it', function () {
    $pegawai = pegawaiUntukReset();
    $admin = User::factory()->create(['role' => 'admin']);
    $targetUserId = $pegawai->user_id;

    $this->actingAs($admin)->post(route('admin.pegawai.password.update', $pegawai))
        ->assertRedirect(route('admin.pegawai.password.edit', $pegawai))
        ->assertSessionHas('status', 'Password Budi Santoso berhasil direset.')
        ->assertSessionHas('passwordSekali')
        ->assertSessionHasNoErrors();

    $password = session('passwordSekali');
    $user = User::findOrFail($targetUserId);

    expect($password)->not->toBe('123456')
        ->and(strlen($password))->toBe(16)
        ->and($user->password)->not->toBe($password)
        ->and(Hash::check($password, $user->password))->toBeTrue()
        ->and(Hash::check('lama12345', $user->password))->toBeFalse();

    $log = PasswordResetLog::firstOrFail();

    expect($log->admin_id)->toBe($admin->id)
        ->and($log->id_pegawai)->toBe($pegawai->id)
        ->and($log->created_at)->not->toBeNull();
});

it('ignores a client supplied password instead of using 123456', function () {
    $pegawai = pegawaiUntukReset();
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('admin.pegawai.password.update', $pegawai), [
        'password' => '123456',
        'password_confirmation' => '123456',
    ])->assertSessionHasNoErrors();

    expect(session('passwordSekali'))->not->toBe('123456')
        ->and(Hash::check('123456', $pegawai->user->fresh()->password))->toBeFalse()
        ->and(PasswordResetLog::count())->toBe(1);
});

it('only accepts POST for the reset action', function () {
    $pegawai = pegawaiUntukReset();
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get(route('admin.pegawai.password.update', $pegawai))
        ->assertMethodNotAllowed();

    $this->get("/pegawai/{$pegawai->nip}/resetpassword")->assertNotFound();
});

it('displays the generated password only once after a reset', function () {
    mock(BuatPasswordAwalAction::class)->shouldReceive('__invoke')->andReturn('Sekali123!');

    $pegawai = pegawaiUntukReset();
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->followingRedirects()
        ->post(route('admin.pegawai.password.update', $pegawai))
        ->assertOk()
        ->assertSee('Sekali123!');

    $this->actingAs($admin)->get(route('admin.pegawai.password.edit', $pegawai))
        ->assertOk()
        ->assertDontSee('Sekali123!');
});
