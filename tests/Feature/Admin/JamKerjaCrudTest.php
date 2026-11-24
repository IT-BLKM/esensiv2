<?php

use App\Models\JamKerja;
use App\Models\KonfigurasiJamKerja;
use App\Models\Presensi;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

function jamKerjaUntukCrud(): JamKerja
{
    return JamKerja::factory()->create([
        'kode' => 'SHFT',
        'nama' => 'Shift Pagi',
        'awal_masuk' => '07:30:00',
        'jam_masuk' => '08:00:00',
        'akhir_masuk' => '08:30:00',
        'jam_pulang' => '17:00:00',
    ]);
}

/**
 * @return array<string, string>
 */
function dataJamKerjaValid(): array
{
    return [
        'kode' => 'SHFT',
        'nama' => 'Shift Pagi',
        'awal_masuk' => '07:30',
        'jam_masuk' => '08:00',
        'akhir_masuk' => '08:30',
        'jam_pulang' => '17:00',
    ];
}

it('redirects guests to the login page on every jam kerja route', function () {
    $jamKerja = jamKerjaUntukCrud();

    $this->get(route('admin.jam-kerja.index'))->assertRedirect(route('login'));
    $this->get(route('admin.jam-kerja.create'))->assertRedirect(route('login'));
    $this->post(route('admin.jam-kerja.store'), [])->assertRedirect(route('login'));
    $this->get(route('admin.jam-kerja.edit', $jamKerja))->assertRedirect(route('login'));
    $this->put(route('admin.jam-kerja.update', $jamKerja), [])->assertRedirect(route('login'));
    $this->delete(route('admin.jam-kerja.destroy', $jamKerja))->assertRedirect(route('login'));
});

it('keeps a pegawai out of every jam kerja route', function () {
    $jamKerja = jamKerjaUntukCrud();
    $pegawai = User::factory()->create(['role' => 'pegawai']);

    $this->actingAs($pegawai)->get(route('admin.jam-kerja.index'))->assertRedirect('/dashboard');
    $this->actingAs($pegawai)->get(route('admin.jam-kerja.create'))->assertRedirect('/dashboard');
    $this->actingAs($pegawai)->post(route('admin.jam-kerja.store'), [])->assertRedirect('/dashboard');
    $this->actingAs($pegawai)->get(route('admin.jam-kerja.edit', $jamKerja))->assertRedirect('/dashboard');
    $this->actingAs($pegawai)->put(route('admin.jam-kerja.update', $jamKerja), [])->assertRedirect('/dashboard');
    $this->actingAs($pegawai)->delete(route('admin.jam-kerja.destroy', $jamKerja))->assertRedirect('/dashboard');
});

it('lists jam kerja in a table with create and delete controls', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $jamKerja = jamKerjaUntukCrud();

    $this->actingAs($admin)->get(route('admin.jam-kerja.index'))
        ->assertOk()
        ->assertSee('SHFT')
        ->assertSee('Shift Pagi')
        ->assertSee('07:30')
        ->assertSee('17:00')
        ->assertSee(route('admin.jam-kerja.create'), false)
        ->assertSee(route('admin.jam-kerja.edit', $jamKerja), false)
        ->assertSee('onsubmit="return confirm(', false);
});

it('filters jam kerja by kode or nama through the search form', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    jamKerjaUntukCrud();
    JamKerja::factory()->create(['kode' => 'SORE', 'nama' => 'Shift Sore']);

    $this->actingAs($admin)->get(route('admin.jam-kerja.index', ['q' => 'Sore']))
        ->assertOk()
        ->assertSee('SORE')
        ->assertDontSee('SHFT');

    $this->actingAs($admin)->get(route('admin.jam-kerja.index', ['q' => 'SHF']))
        ->assertOk()
        ->assertSee('SHFT')
        ->assertDontSee('SORE');
});

it('renders the create form with time inputs', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get(route('admin.jam-kerja.create'))
        ->assertOk()
        ->assertSee('Tambah jam kerja')
        ->assertSee(route('admin.jam-kerja.store'), false)
        ->assertSee('type="time"', false)
        ->assertSee('Awal masuk')
        ->assertSee('Jam pulang');
});

it('creates a jam kerja and flashes a success message', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('admin.jam-kerja.store'), dataJamKerjaValid())
        ->assertRedirect(route('admin.jam-kerja.index'))
        ->assertSessionHas('success', 'Jam kerja Shift Pagi berhasil ditambahkan.')
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('jam_kerja', [
        'kode' => 'SHFT',
        'nama' => 'Shift Pagi',
        'awal_masuk' => '07:30',
        'jam_pulang' => '17:00',
    ]);
});

it('rejects an empty create payload', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('admin.jam-kerja.store'), [])
        ->assertSessionHasErrors(['kode', 'nama', 'awal_masuk', 'jam_masuk', 'akhir_masuk', 'jam_pulang']);

    $this->assertDatabaseEmpty('jam_kerja');
});

it('rejects a duplicate kode when creating', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    jamKerjaUntukCrud();

    $this->actingAs($admin)->post(route('admin.jam-kerja.store'), [
        ...dataJamKerjaValid(),
        'nama' => 'Shift Duplikat',
    ])->assertSessionHasErrors(['kode' => 'Kode jam kerja sudah digunakan.']);

    $this->assertDatabaseMissing('jam_kerja', ['nama' => 'Shift Duplikat']);
});

it('rejects a kode that is not exactly four characters', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('admin.jam-kerja.store'), [
        ...dataJamKerjaValid(),
        'kode' => 'SHF',
    ])->assertSessionHasErrors(['kode' => 'Kode jam kerja harus tepat 4 karakter.']);
});

it('rejects times that break the required order', function (array $overrides, string $field, string $pesan) {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('admin.jam-kerja.store'), [
        ...dataJamKerjaValid(),
        ...$overrides,
    ])->assertSessionHasErrors([$field => $pesan]);

    $this->assertDatabaseEmpty('jam_kerja');
})->with([
    'jam masuk sebelum awal masuk' => [
        ['jam_masuk' => '06:00'],
        'jam_masuk',
        'Jam masuk tidak boleh lebih awal dari jam awal masuk.',
    ],
    'akhir masuk sebelum jam masuk' => [
        ['jam_masuk' => '09:00'],
        'akhir_masuk',
        'Jam akhir masuk tidak boleh lebih awal dari jam masuk.',
    ],
    'jam pulang sebelum akhir masuk' => [
        ['jam_pulang' => '07:00'],
        'jam_pulang',
        'Jam pulang tidak boleh lebih awal dari jam akhir masuk.',
    ],
]);

it('rejects a time that is not in HH:MM format', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('admin.jam-kerja.store'), [
        ...dataJamKerjaValid(),
        'awal_masuk' => '07.30',
    ])->assertSessionHasErrors(['awal_masuk' => 'Jam awal masuk harus berformat HH:MM.']);
});

it('accepts equal times as the inclusive boundary', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('admin.jam-kerja.store'), [
        ...dataJamKerjaValid(),
        'awal_masuk' => '08:00',
        'jam_masuk' => '08:00',
        'akhir_masuk' => '08:00',
        'jam_pulang' => '08:00',
    ])->assertSessionHasNoErrors();
});

it('renders the edit form with the current values', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $jamKerja = jamKerjaUntukCrud();

    $this->actingAs($admin)->get(route('admin.jam-kerja.edit', $jamKerja))
        ->assertOk()
        ->assertSee('Ubah jam kerja')
        ->assertSee(route('admin.jam-kerja.update', $jamKerja), false)
        ->assertSee('value="SHFT"', false)
        ->assertSee('value="Shift Pagi"', false)
        ->assertSee('value="07:30"', false)
        ->assertSee('value="17:00"', false);
});

it('updates a jam kerja and flashes a success message', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $jamKerja = jamKerjaUntukCrud();

    $this->actingAs($admin)->put(route('admin.jam-kerja.update', $jamKerja), [
        'kode' => 'SHFT',
        'nama' => 'Shift Pagi Baru',
        'awal_masuk' => '07:00',
        'jam_masuk' => '07:30',
        'akhir_masuk' => '08:00',
        'jam_pulang' => '16:00',
    ])
        ->assertRedirect(route('admin.jam-kerja.index'))
        ->assertSessionHas('success', 'Jam kerja Shift Pagi Baru berhasil diperbarui.')
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('jam_kerja', [
        'id' => $jamKerja->id,
        'kode' => 'SHFT',
        'nama' => 'Shift Pagi Baru',
        'awal_masuk' => '07:00',
        'jam_pulang' => '16:00',
    ]);
});

it('rejects an update that uses another jam kerja kode', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $jamKerja = jamKerjaUntukCrud();
    JamKerja::factory()->create(['kode' => 'SORE', 'nama' => 'Shift Sore']);

    $this->actingAs($admin)->put(route('admin.jam-kerja.update', $jamKerja), [
        ...dataJamKerjaValid(),
        'kode' => 'SORE',
    ])->assertSessionHasErrors(['kode' => 'Kode jam kerja sudah digunakan.']);

    expect($jamKerja->fresh()->kode)->toBe('SHFT');
});

it('rejects an update with times out of order', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $jamKerja = jamKerjaUntukCrud();

    $this->actingAs($admin)->put(route('admin.jam-kerja.update', $jamKerja), [
        ...dataJamKerjaValid(),
        'jam_pulang' => '07:00',
    ])->assertSessionHasErrors('jam_pulang');

    expect($jamKerja->fresh()->jam_pulang)->toBe('17:00:00');
});

it('deletes an unused jam kerja and flashes a success message', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $jamKerja = jamKerjaUntukCrud();

    $this->actingAs($admin)->delete(route('admin.jam-kerja.destroy', $jamKerja))
        ->assertRedirect(route('admin.jam-kerja.index'))
        ->assertSessionHas('success', 'Jam kerja Shift Pagi berhasil dihapus.');

    $this->assertDatabaseMissing('jam_kerja', ['id' => $jamKerja->id]);
});

it('refuses to delete a jam kerja used by a pegawai schedule', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $jamKerja = jamKerjaUntukCrud();
    $konfigurasi = KonfigurasiJamKerja::factory()->create(['id_jam_kerja' => $jamKerja->id]);

    $this->actingAs($admin)->delete(route('admin.jam-kerja.destroy', $jamKerja))
        ->assertRedirect(route('admin.jam-kerja.index'))
        ->assertSessionHas('error', 'Jam kerja Shift Pagi tidak bisa dihapus karena masih digunakan konfigurasi atau presensi.');

    $this->assertModelExists($jamKerja);
    $this->assertDatabaseHas('konfigurasi_jam_kerja', [
        'id' => $konfigurasi->id,
        'id_jam_kerja' => $jamKerja->id,
    ]);
});

it('refuses to delete a jam kerja recorded in presensi', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $jamKerja = jamKerjaUntukCrud();
    $presensi = Presensi::factory()->create(['id_jam_kerja' => $jamKerja->id]);

    $this->actingAs($admin)->delete(route('admin.jam-kerja.destroy', $jamKerja))
        ->assertRedirect(route('admin.jam-kerja.index'))
        ->assertSessionHas('error', 'Jam kerja Shift Pagi tidak bisa dihapus karena masih digunakan konfigurasi atau presensi.');

    $this->assertModelExists($jamKerja);
    $this->assertDatabaseHas('presensi', [
        'id' => $presensi->id,
        'id_jam_kerja' => $jamKerja->id,
    ]);
});

it('renders the flashed success and error messages on the index page', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->withSession(['success' => 'Jam kerja berhasil ditambahkan.'])
        ->get(route('admin.jam-kerja.index'))
        ->assertOk()
        ->assertSee('Jam kerja berhasil ditambahkan.');

    $this->actingAs($admin)
        ->withSession(['error' => 'Jam kerja tidak bisa dihapus.'])
        ->get(route('admin.jam-kerja.index'))
        ->assertOk()
        ->assertSee('Jam kerja tidak bisa dihapus.');
});
