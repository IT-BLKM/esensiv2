<?php

use App\Models\Departemen;
use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

function departemenUntukCrud(): Departemen
{
    return Departemen::factory()->create([
        'kode' => 'ADKL',
        'nama' => 'Dinas Kependudukan',
    ]);
}

it('redirects guests to the login page on every departemen route', function () {
    $departemen = departemenUntukCrud();

    $this->get(route('admin.departemen.index'))->assertRedirect(route('login'));
    $this->get(route('admin.departemen.create'))->assertRedirect(route('login'));
    $this->post(route('admin.departemen.store'), [])->assertRedirect(route('login'));
    $this->get(route('admin.departemen.edit', $departemen))->assertRedirect(route('login'));
    $this->put(route('admin.departemen.update', $departemen), [])->assertRedirect(route('login'));
    $this->delete(route('admin.departemen.destroy', $departemen))->assertRedirect(route('login'));
});

it('keeps a pegawai out of every departemen route', function () {
    $departemen = departemenUntukCrud();
    $pegawai = User::factory()->create(['role' => 'pegawai']);

    $this->actingAs($pegawai)->get(route('admin.departemen.index'))->assertRedirect('/dashboard');
    $this->actingAs($pegawai)->get(route('admin.departemen.create'))->assertRedirect('/dashboard');
    $this->actingAs($pegawai)->post(route('admin.departemen.store'), [])->assertRedirect('/dashboard');
    $this->actingAs($pegawai)->get(route('admin.departemen.edit', $departemen))->assertRedirect('/dashboard');
    $this->actingAs($pegawai)->put(route('admin.departemen.update', $departemen), [])->assertRedirect('/dashboard');
    $this->actingAs($pegawai)->delete(route('admin.departemen.destroy', $departemen))->assertRedirect('/dashboard');
});

it('lists departemen in a table with create and delete controls', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $departemen = departemenUntukCrud();
    Departemen::factory()->create(['kode' => 'KESD', 'nama' => 'Dinas Kesehatan']);

    $this->actingAs($admin)->get(route('admin.departemen.index'))
        ->assertOk()
        ->assertSee('ADKL')
        ->assertSee('Dinas Kependudukan')
        ->assertSee('KESD')
        ->assertSee('Dinas Kesehatan')
        ->assertSee(route('admin.departemen.create'), false)
        ->assertSee(route('admin.departemen.edit', $departemen), false)
        ->assertSee('onsubmit="return confirm(', false);
});

it('filters departemen by kode or nama through the search form', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    departemenUntukCrud();
    Departemen::factory()->create(['kode' => 'KESD', 'nama' => 'Dinas Kesehatan']);

    $this->actingAs($admin)->get(route('admin.departemen.index', ['q' => 'Kesehatan']))
        ->assertOk()
        ->assertSee('KESD')
        ->assertDontSee('ADKL');

    $this->actingAs($admin)->get(route('admin.departemen.index', ['q' => 'ADK']))
        ->assertOk()
        ->assertSee('ADKL')
        ->assertDontSee('KESD');
});

it('renders the create form', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get(route('admin.departemen.create'))
        ->assertOk()
        ->assertSee('Tambah departemen')
        ->assertSee(route('admin.departemen.store'), false)
        ->assertSee('Kode departemen')
        ->assertSee('Nama departemen');
});

it('creates a departemen and flashes a success message', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('admin.departemen.store'), [
        'kode' => 'ADKL',
        'nama' => 'Dinas Kependudukan',
    ])
        ->assertRedirect(route('admin.departemen.index'))
        ->assertSessionHas('success', 'Departemen Dinas Kependudukan berhasil ditambahkan.')
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('departemen', [
        'kode' => 'ADKL',
        'nama' => 'Dinas Kependudukan',
    ]);
});

it('rejects a create request with empty kode and nama', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('admin.departemen.store'), [])
        ->assertSessionHasErrors(['kode', 'nama']);

    $this->assertDatabaseCount('departemen', 0);
});

it('rejects a duplicate kode when creating', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    departemenUntukCrud();

    $this->actingAs($admin)->post(route('admin.departemen.store'), [
        'kode' => 'ADKL',
        'nama' => 'Departemen duplikat',
    ])->assertSessionHasErrors(['kode' => 'Kode departemen sudah digunakan.']);

    $this->assertDatabaseMissing('departemen', ['nama' => 'Departemen duplikat']);
});

it('rejects a kode longer than four characters', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('admin.departemen.store'), [
        'kode' => 'KESEHATAN',
        'nama' => 'Dinas Kesehatan',
    ])->assertSessionHasErrors('kode');
});

it('renders the edit form with the current values', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $departemen = departemenUntukCrud();

    $this->actingAs($admin)->get(route('admin.departemen.edit', $departemen))
        ->assertOk()
        ->assertSee('Ubah departemen')
        ->assertSee(route('admin.departemen.update', $departemen), false)
        ->assertSee('value="ADKL"', false)
        ->assertSee('value="Dinas Kependudukan"', false);
});

it('updates a departemen and flashes a success message', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $departemen = departemenUntukCrud();

    $this->actingAs($admin)->put(route('admin.departemen.update', $departemen), [
        'kode' => 'DUKP',
        'nama' => 'Dinas Kependudukan dan Pencatatan Sipil',
    ])
        ->assertRedirect(route('admin.departemen.index'))
        ->assertSessionHas('success', 'Departemen Dinas Kependudukan dan Pencatatan Sipil berhasil diperbarui.')
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('departemen', [
        'id' => $departemen->id,
        'kode' => 'DUKP',
        'nama' => 'Dinas Kependudukan dan Pencatatan Sipil',
    ]);
});

it('lets a departemen keep its own kode when updated', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $departemen = departemenUntukCrud();

    $this->actingAs($admin)->put(route('admin.departemen.update', $departemen), [
        'kode' => 'ADKL',
        'nama' => 'Dinas Kependudukan Baru',
    ])->assertSessionHasNoErrors();

    expect($departemen->fresh()->nama)->toBe('Dinas Kependudukan Baru');
});

it('rejects an update that uses another departemen kode', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $departemen = departemenUntukCrud();
    Departemen::factory()->create(['kode' => 'KESD', 'nama' => 'Dinas Kesehatan']);

    $this->actingAs($admin)->put(route('admin.departemen.update', $departemen), [
        'kode' => 'KESD',
        'nama' => 'Dinas Kependudukan',
    ])->assertSessionHasErrors('kode');

    expect($departemen->fresh()->kode)->toBe('ADKL');
});

it('deletes an empty departemen and flashes a success message', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $departemen = departemenUntukCrud();

    $this->actingAs($admin)->delete(route('admin.departemen.destroy', $departemen))
        ->assertRedirect(route('admin.departemen.index'))
        ->assertSessionHas('success', 'Departemen Dinas Kependudukan berhasil dihapus.');

    $this->assertDatabaseMissing('departemen', ['id' => $departemen->id]);
});

it('refuses to delete a departemen that still has pegawai', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $departemen = departemenUntukCrud();
    $pegawai = Pegawai::factory()->create(['id_dept' => $departemen->id]);

    $this->actingAs($admin)->delete(route('admin.departemen.destroy', $departemen))
        ->assertRedirect(route('admin.departemen.index'))
        ->assertSessionHas('error', 'Departemen Dinas Kependudukan tidak bisa dihapus karena masih digunakan pegawai.');

    $this->assertModelExists($departemen);
    $this->assertDatabaseHas('pegawai', ['id' => $pegawai->id, 'id_dept' => $departemen->id]);
});

it('renders the flashed success and error messages on the index page', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->withSession(['success' => 'Departemen berhasil ditambahkan.'])
        ->get(route('admin.departemen.index'))
        ->assertOk()
        ->assertSee('Departemen berhasil ditambahkan.');

    $this->actingAs($admin)
        ->withSession(['error' => 'Departemen tidak bisa dihapus.'])
        ->get(route('admin.departemen.index'))
        ->assertOk()
        ->assertSee('Departemen tidak bisa dihapus.');
});
