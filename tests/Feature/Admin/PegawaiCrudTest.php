<?php

use App\Actions\Pegawai\BuatPasswordAwalAction;
use App\Models\Departemen;
use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\mock;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

function departemenUntukPegawai(): Departemen
{
    return Departemen::factory()->create([
        'kode' => 'ADKL',
        'nama' => 'Dinas Kependudukan',
    ]);
}

function pegawaiUntukCrud(): Pegawai
{
    $departemen = departemenUntukPegawai();
    $user = User::factory()->create([
        'nama' => 'Budi Lama',
        'email' => 'budi.lama@example.test',
        'password' => 'lama12345',
        'role' => 'pegawai',
    ]);

    return Pegawai::factory()->create([
        'user_id' => $user->id,
        'id_dept' => $departemen->id,
        'nip' => '199302032022032001',
        'nama' => 'Budi Santoso',
        'jabatan' => 'Staf',
        'no_hp' => '081234567890',
        'foto' => null,
    ]);
}

/**
 * @return array<string, int|string>
 */
function dataPegawaiValid(Departemen $departemen): array
{
    return [
        'nip' => '199302032022032001',
        'email' => 'budi@example.test',
        'nama' => 'Budi Santoso',
        'jabatan' => 'Staf',
        'id_dept' => $departemen->id,
        'no_hp' => '081234567890',
    ];
}

it('redirects guests to the login page on every pegawai route', function () {
    $pegawai = pegawaiUntukCrud();

    $this->get(route('admin.pegawai.index'))->assertRedirect(route('login'));
    $this->get(route('admin.pegawai.create'))->assertRedirect(route('login'));
    $this->post(route('admin.pegawai.store'), [])->assertRedirect(route('login'));
    $this->get(route('admin.pegawai.edit', $pegawai))->assertRedirect(route('login'));
    $this->put(route('admin.pegawai.update', $pegawai), [])->assertRedirect(route('login'));
    $this->delete(route('admin.pegawai.destroy', $pegawai))->assertRedirect(route('login'));
});

it('keeps a pegawai out of every admin pegawai route', function () {
    $pegawai = pegawaiUntukCrud();
    $actor = User::factory()->create(['role' => 'pegawai']);

    $this->actingAs($actor)->get(route('admin.pegawai.index'))->assertRedirect('/dashboard');
    $this->actingAs($actor)->get(route('admin.pegawai.create'))->assertRedirect('/dashboard');
    $this->actingAs($actor)->post(route('admin.pegawai.store'), [])->assertRedirect('/dashboard');
    $this->actingAs($actor)->get(route('admin.pegawai.edit', $pegawai))->assertRedirect('/dashboard');
    $this->actingAs($actor)->put(route('admin.pegawai.update', $pegawai), [])->assertRedirect('/dashboard');
    $this->actingAs($actor)->delete(route('admin.pegawai.destroy', $pegawai))->assertRedirect('/dashboard');
});

it('lists pegawai with their account, department, and photo links', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $pegawai = pegawaiUntukCrud();

    $this->actingAs($admin)->get(route('admin.pegawai.index'))
        ->assertOk()
        ->assertSee('199302032022032001')
        ->assertSee('Budi Santoso')
        ->assertSee('Dinas Kependudukan')
        ->assertSee(route('admin.pegawai.create'), false)
        ->assertSee(route('admin.pegawai.edit', $pegawai), false)
        ->assertSee(route('admin.pegawai.password.edit', $pegawai), false)
        ->assertSee(route('admin.pegawai.destroy', $pegawai), false)
        ->assertSee('onsubmit="return confirm(', false)
        ->assertDontSee('name="password"', false);
});

it('filters pegawai by nip, nama, or jabatan through the search form', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    pegawaiUntukCrud();
    $lain = Departemen::factory()->create(['kode' => 'KEU', 'nama' => 'Keuangan']);
    Pegawai::factory()->create([
        'id_dept' => $lain->id,
        'nip' => '199401012019012002',
        'nama' => 'Siti Aminah',
        'jabatan' => 'Bendahara',
    ]);

    $this->actingAs($admin)->get(route('admin.pegawai.index', ['q' => 'Siti']))
        ->assertOk()
        ->assertSee('Siti Aminah')
        ->assertDontSee('Budi Santoso');

    $this->actingAs($admin)->get(route('admin.pegawai.index', ['q' => '199302032022032001']))
        ->assertOk()
        ->assertSee('Budi Santoso')
        ->assertDontSee('Siti Aminah');
});

it('renders a multipart create form without any password input', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    departemenUntukPegawai();

    $this->actingAs($admin)->get(route('admin.pegawai.create'))
        ->assertOk()
        ->assertSee('Tambah pegawai')
        ->assertSee(route('admin.pegawai.store'), false)
        ->assertSee('enctype="multipart/form-data"', false)
        ->assertSee('name="nip"', false)
        ->assertSee('name="email"', false)
        ->assertSee('name="foto"', false)
        ->assertSee('accept=".jpg,.jpeg,.png"', false)
        ->assertDontSee('name="password"', false);
});

it('creates a pegawai and its user with a hashed generated password', function () {
    Storage::fake('public');
    mock(BuatPasswordAwalAction::class)->shouldReceive('__invoke')->andReturn('Sekali123!');

    $admin = User::factory()->create(['role' => 'admin']);
    $departemen = departemenUntukPegawai();
    $foto = UploadedFile::fake()->image('budi.jpg');

    $this->actingAs($admin)->post(route('admin.pegawai.store'), [
        ...dataPegawaiValid($departemen),
        'foto' => $foto,
    ])
        ->assertRedirect(route('admin.pegawai.index'))
        ->assertSessionHas('success', 'Pegawai Budi Santoso berhasil ditambahkan.')
        ->assertSessionHas('passwordSekali', 'Sekali123!')
        ->assertSessionHasNoErrors();

    $pegawai = Pegawai::firstOrFail();

    expect($pegawai->nip)->toBe('199302032022032001')
        ->and($pegawai->nama)->toBe('Budi Santoso')
        ->and($pegawai->user->email)->toBe('budi@example.test')
        ->and($pegawai->user->role)->toBe('pegawai')
        ->and($pegawai->user->password)->not->toBe('Sekali123!')
        ->and(Hash::check('Sekali123!', $pegawai->user->password))->toBeTrue();

    Storage::disk('public')->assertExists($pegawai->foto);
    expect(Pegawai::count())->toBe(1);
});

it('creates a pegawai without a photo', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $departemen = departemenUntukPegawai();

    $this->actingAs($admin)->post(route('admin.pegawai.store'), dataPegawaiValid($departemen))
        ->assertRedirect(route('admin.pegawai.index'))
        ->assertSessionHasNoErrors();

    expect(Pegawai::firstOrFail()->foto)->toBeNull();
});

it('rejects an empty create payload', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('admin.pegawai.store'), [])
        ->assertSessionHasErrors(['nip', 'email', 'nama', 'jabatan', 'id_dept', 'no_hp']);

    expect(Pegawai::count())->toBe(0);
});

it('rejects an invalid nip format', function (string $nip, string $pesan) {
    $admin = User::factory()->create(['role' => 'admin']);
    $departemen = departemenUntukPegawai();

    $this->actingAs($admin)->post(route('admin.pegawai.store'), [
        ...dataPegawaiValid($departemen),
        'nip' => $nip,
    ])->assertSessionHasErrors(['nip' => $pesan]);

    expect(Pegawai::count())->toBe(0);
})->with([
    'terlalu pendek' => ['19930203202203200', 'NIP harus terdiri dari 18 digit.'],
    'mengandung huruf' => ['abcdefghijklmnopqr', 'NIP hanya boleh berisi angka.'],
]);

it('rejects a duplicate nip when creating', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $pegawai = pegawaiUntukCrud();

    $this->actingAs($admin)->post(route('admin.pegawai.store'), [
        ...dataPegawaiValid($pegawai->departemen),
        'nama' => 'Pegawai Duplikat',
    ])->assertSessionHasErrors(['nip' => 'NIP sudah digunakan pegawai lain.']);

    expect(Pegawai::where('nama', 'Pegawai Duplikat')->exists())->toBeFalse();
});

it('rejects a duplicate email when creating', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $departemen = departemenUntukPegawai();
    User::factory()->create(['email' => 'budi@example.test']);

    $this->actingAs($admin)->post(route('admin.pegawai.store'), dataPegawaiValid($departemen))
        ->assertSessionHasErrors(['email' => 'Email sudah digunakan akun lain.']);

    expect(Pegawai::count())->toBe(0);
});

it('rejects an invalid email when creating', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $departemen = departemenUntukPegawai();

    $this->actingAs($admin)->post(route('admin.pegawai.store'), [
        ...dataPegawaiValid($departemen),
        'email' => 'bukan-email',
    ])->assertSessionHasErrors(['email' => 'Format email tidak valid.']);

    expect(Pegawai::count())->toBe(0);
});

it('rejects an invalid photo type or size', function (UploadedFile $foto, string $pesan) {
    $admin = User::factory()->create(['role' => 'admin']);
    $departemen = departemenUntukPegawai();

    $this->actingAs($admin)->post(route('admin.pegawai.store'), [
        ...dataPegawaiValid($departemen),
        'foto' => $foto,
    ])->assertSessionHasErrors(['foto' => $pesan]);

    expect(Pegawai::count())->toBe(0);
})->with([
    'bukan gambar' => [fn () => UploadedFile::fake()->create('foto.pdf', 100, 'application/pdf'), 'Foto harus berformat JPG, JPEG, atau PNG.'],
    'terlalu besar' => [fn () => UploadedFile::fake()->image('besar.jpg')->size(2049), 'Ukuran foto maksimal 2 MB.'],
]);

it('renders the edit form with the current values and photo', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'admin']);
    $pegawai = pegawaiUntukCrud();
    Storage::disk('public')->put('foto-pegawai/lama.jpg', 'foto lama');
    $pegawai->update(['foto' => 'foto-pegawai/lama.jpg']);

    $this->actingAs($admin)->get(route('admin.pegawai.edit', $pegawai))
        ->assertOk()
        ->assertSee('Ubah pegawai')
        ->assertSee(route('admin.pegawai.update', $pegawai), false)
        ->assertSee('value="199302032022032001"', false)
        ->assertSee('value="budi.lama@example.test"', false)
        ->assertSee('value="Budi Santoso"', false)
        ->assertSee('enctype="multipart/form-data"', false)
        ->assertSee('foto-pegawai/lama.jpg', false);
});

it('updates a pegawai and synchronizes the related user without changing the password', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $pegawai = pegawaiUntukCrud();
    $departemenBaru = Departemen::factory()->create(['kode' => 'KEU', 'nama' => 'Keuangan']);
    $userId = $pegawai->user_id;

    $this->actingAs($admin)->put(route('admin.pegawai.update', $pegawai), [
        'nip' => '199302032022032002',
        'email' => 'budi.baru@example.test',
        'nama' => 'Budi Diperbarui',
        'jabatan' => 'Kepala Bagian',
        'id_dept' => $departemenBaru->id,
        'no_hp' => '081987654321',
    ])
        ->assertRedirect(route('admin.pegawai.index'))
        ->assertSessionHas('success', 'Pegawai Budi Diperbarui berhasil diperbarui.')
        ->assertSessionHasNoErrors();

    $pegawai->refresh();
    $user = User::findOrFail($userId);

    expect($pegawai->nip)->toBe('199302032022032002')
        ->and($pegawai->nama)->toBe('Budi Diperbarui')
        ->and($pegawai->id_dept)->toBe($departemenBaru->id)
        ->and($user->nama)->toBe('Budi Diperbarui')
        ->and($user->email)->toBe('budi.baru@example.test')
        ->and(Hash::check('lama12345', $user->password))->toBeTrue();
});

it('rejects an update that uses an email owned by another account', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $pegawai = pegawaiUntukCrud();
    User::factory()->create(['email' => 'dipakai@example.test']);

    $this->actingAs($admin)->put(route('admin.pegawai.update', $pegawai), [
        ...dataPegawaiValid($pegawai->departemen),
        'email' => 'dipakai@example.test',
    ])->assertSessionHasErrors(['email' => 'Email sudah digunakan akun lain.']);

    expect($pegawai->fresh()->user->email)->toBe('budi.lama@example.test');
});

it('rejects an update that uses another pegawai nip', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $pegawai = pegawaiUntukCrud();
    $lain = Departemen::factory()->create(['kode' => 'KEU', 'nama' => 'Keuangan']);
    Pegawai::factory()->create([
        'id_dept' => $lain->id,
        'nip' => '199401012019012002',
        'nama' => 'Siti Aminah',
    ]);

    $this->actingAs($admin)->put(route('admin.pegawai.update', $pegawai), [
        ...dataPegawaiValid($pegawai->departemen),
        'nip' => '199401012019012002',
    ])->assertSessionHasErrors(['nip' => 'NIP sudah digunakan pegawai lain.']);

    expect($pegawai->fresh()->nip)->toBe('199302032022032001');
});

it('replaces the photo when a new one is uploaded and removes the old file', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'admin']);
    $pegawai = pegawaiUntukCrud();
    Storage::disk('public')->put('foto-pegawai/lama.jpg', 'foto lama');
    $pegawai->update(['foto' => 'foto-pegawai/lama.jpg']);

    $this->actingAs($admin)->put(route('admin.pegawai.update', $pegawai), [
        ...dataPegawaiValid($pegawai->departemen),
        'foto' => UploadedFile::fake()->image('baru.png'),
    ])->assertRedirect(route('admin.pegawai.index'))->assertSessionHasNoErrors();

    $pathBaru = $pegawai->fresh()->foto;

    expect($pathBaru)->not->toBe('foto-pegawai/lama.jpg');
    Storage::disk('public')->assertMissing('foto-pegawai/lama.jpg');
    Storage::disk('public')->assertExists($pathBaru);
});

it('keeps the current photo when no new file is uploaded', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'admin']);
    $pegawai = pegawaiUntukCrud();
    Storage::disk('public')->put('foto-pegawai/lama.jpg', 'foto lama');
    $pegawai->update(['foto' => 'foto-pegawai/lama.jpg']);

    $this->actingAs($admin)->put(route('admin.pegawai.update', $pegawai), dataPegawaiValid($pegawai->departemen))
        ->assertRedirect(route('admin.pegawai.index'))
        ->assertSessionHasNoErrors();

    expect($pegawai->fresh()->foto)->toBe('foto-pegawai/lama.jpg');
    Storage::disk('public')->assertExists('foto-pegawai/lama.jpg');
});

it('deletes the pegawai together with its user and photo', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'admin']);
    $pegawai = pegawaiUntukCrud();
    Storage::disk('public')->put('foto-pegawai/lama.jpg', 'foto lama');
    $pegawai->update(['foto' => 'foto-pegawai/lama.jpg']);
    $userId = $pegawai->user_id;

    $this->actingAs($admin)->delete(route('admin.pegawai.destroy', $pegawai))
        ->assertRedirect(route('admin.pegawai.index'))
        ->assertSessionHas('success', 'Pegawai Budi Santoso berhasil dihapus.');

    $this->assertDatabaseMissing('pegawai', ['id' => $pegawai->id]);
    $this->assertDatabaseMissing('user', ['id' => $userId]);
    Storage::disk('public')->assertMissing('foto-pegawai/lama.jpg');
});

it('shows red validation styling on the create form when fields are invalid', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    departemenUntukPegawai();

    $this->actingAs($admin)
        ->from(route('admin.pegawai.create'))
        ->followingRedirects()
        ->post(route('admin.pegawai.store'), [])
        ->assertOk()
        ->assertSee('text-red-600', false)
        ->assertSee('border-red-500', false)
        ->assertSee('Departemen wajib dipilih.');
});

it('shows the generated password only once after creating a pegawai', function () {
    mock(BuatPasswordAwalAction::class)->shouldReceive('__invoke')->andReturn('Sekali123!');

    $admin = User::factory()->create(['role' => 'admin']);
    $departemen = departemenUntukPegawai();

    $this->actingAs($admin)->followingRedirects()
        ->post(route('admin.pegawai.store'), dataPegawaiValid($departemen))
        ->assertOk()
        ->assertSee('Sekali123!');

    $this->actingAs($admin)->get(route('admin.pegawai.index'))
        ->assertOk()
        ->assertDontSee('Sekali123!');
});
