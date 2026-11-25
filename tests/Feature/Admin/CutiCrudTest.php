<?php

use App\Models\Cuti;
use App\Models\Pengajuan;
use App\Models\SaldoCuti;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

function cutiUntukCrud(): Cuti
{
    return Cuti::factory()->create([
        'kode' => 'C01',
        'nama' => 'Cuti Tahunan',
        'maks_hari' => 12,
        'kuota' => 12,
        'maks_bawa' => 6,
    ]);
}

/**
 * @return array<string, int|string>
 */
function dataCutiValid(): array
{
    return [
        'kode' => 'C01',
        'nama' => 'Cuti Tahunan',
        'maks_hari' => 12,
        'kuota' => 12,
        'maks_bawa' => 6,
    ];
}

it('redirects guests to the login page on every cuti route', function () {
    $cuti = cutiUntukCrud();

    $this->get(route('admin.cuti.index'))->assertRedirect(route('login'));
    $this->get(route('admin.cuti.create'))->assertRedirect(route('login'));
    $this->post(route('admin.cuti.store'), [])->assertRedirect(route('login'));
    $this->get(route('admin.cuti.edit', $cuti))->assertRedirect(route('login'));
    $this->put(route('admin.cuti.update', $cuti), [])->assertRedirect(route('login'));
    $this->delete(route('admin.cuti.destroy', $cuti))->assertRedirect(route('login'));
});

it('keeps a pegawai out of every cuti route', function () {
    $cuti = cutiUntukCrud();
    $pegawai = User::factory()->create(['role' => 'pegawai']);

    $this->actingAs($pegawai)->get(route('admin.cuti.index'))->assertRedirect('/dashboard');
    $this->actingAs($pegawai)->get(route('admin.cuti.create'))->assertRedirect('/dashboard');
    $this->actingAs($pegawai)->post(route('admin.cuti.store'), [])->assertRedirect('/dashboard');
    $this->actingAs($pegawai)->get(route('admin.cuti.edit', $cuti))->assertRedirect('/dashboard');
    $this->actingAs($pegawai)->put(route('admin.cuti.update', $cuti), [])->assertRedirect('/dashboard');
    $this->actingAs($pegawai)->delete(route('admin.cuti.destroy', $cuti))->assertRedirect('/dashboard');
});

it('lists cuti rules in a table without any saldo column', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $cuti = cutiUntukCrud();
    Cuti::factory()->create(['kode' => 'C02', 'nama' => 'Cuti Bersalin']);

    $this->actingAs($admin)->get(route('admin.cuti.index'))
        ->assertOk()
        ->assertSee('C01')
        ->assertSee('Cuti Tahunan')
        ->assertSee('C02')
        ->assertSee('Cuti Bersalin')
        ->assertSee('Maks bawa')
        ->assertSee(route('admin.cuti.create'), false)
        ->assertSee(route('admin.cuti.edit', $cuti), false)
        ->assertSee('onsubmit="return confirm(', false)
        ->assertDontSee('name="saldo"', false);
});

it('filters cuti by kode or nama through the search form', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    cutiUntukCrud();
    Cuti::factory()->create(['kode' => 'C02', 'nama' => 'Cuti Bersalin']);

    $this->actingAs($admin)->get(route('admin.cuti.index', ['q' => 'Bersalin']))
        ->assertOk()
        ->assertSee('C02')
        ->assertDontSee('C01');

    $this->actingAs($admin)->get(route('admin.cuti.index', ['q' => 'C01']))
        ->assertOk()
        ->assertSee('C01')
        ->assertDontSee('C02');
});

it('renders numeric inputs and no saldo field on the create form', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get(route('admin.cuti.create'))
        ->assertOk()
        ->assertSee('Tambah jenis cuti')
        ->assertSee(route('admin.cuti.store'), false)
        ->assertSee('Kode cuti')
        ->assertSee('Maksimal bawa')
        ->assertSee('type="number"', false)
        ->assertSee('min="0"', false)
        ->assertDontSee('name="saldo"', false);
});

it('creates a cuti rule and flashes a success message', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('admin.cuti.store'), [
        ...dataCutiValid(),
        'saldo' => 99,
    ])
        ->assertRedirect(route('admin.cuti.index'))
        ->assertSessionHas('success', 'Jenis cuti Cuti Tahunan berhasil ditambahkan.')
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('cuti', [
        'kode' => 'C01',
        'nama' => 'Cuti Tahunan',
        'maks_hari' => 12,
        'kuota' => 12,
        'maks_bawa' => 6,
    ]);
    $this->assertDatabaseEmpty('saldo_cuti');
});

it('rejects a create request with missing required fields', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('admin.cuti.store'), [])
        ->assertSessionHasErrors(['kode', 'nama', 'maks_bawa']);

    $this->assertDatabaseEmpty('cuti');
});

it('rejects a duplicate kode when creating', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    cutiUntukCrud();

    $this->actingAs($admin)->post(route('admin.cuti.store'), [
        ...dataCutiValid(),
        'nama' => 'Jenis duplikat',
    ])->assertSessionHasErrors(['kode' => 'Kode cuti sudah digunakan.']);

    $this->assertDatabaseMissing('cuti', ['nama' => 'Jenis duplikat']);
});

it('rejects a kode longer than four characters and a nama longer than twenty five', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('admin.cuti.store'), [
        ...dataCutiValid(),
        'kode' => 'CUTI PANJANG',
    ])->assertSessionHasErrors('kode');

    $this->actingAs($admin)->post(route('admin.cuti.store'), [
        ...dataCutiValid(),
        'kode' => 'C02',
        'nama' => str_repeat('a', 26),
    ])->assertSessionHasErrors('nama');

    $this->assertDatabaseEmpty('cuti');
});

it('rejects negative or non integer rule numbers', function (string $field, mixed $value, string $pesan) {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('admin.cuti.store'), [
        ...dataCutiValid(),
        $field => $value,
    ])->assertSessionHasErrors([$field => $pesan]);

    $this->assertDatabaseEmpty('cuti');
})->with([
    'maks_hari negatif' => ['maks_hari', -1, 'Maksimal hari tidak boleh negatif.'],
    'kuota negatif' => ['kuota', -1, 'Kuota tidak boleh negatif.'],
    'maks_bawa negatif' => ['maks_bawa', -1, 'Maksimal bawa tidak boleh negatif.'],
    'maks_hari desimal' => ['maks_hari', 1.5, 'Maksimal hari harus berupa angka.'],
]);

it('accepts zero and blank optional rules', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('admin.cuti.store'), [
        'kode' => 'C03',
        'nama' => 'Cuti Alasan Penting',
        'maks_hari' => 0,
        'kuota' => '',
        'maks_bawa' => 0,
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('cuti', [
        'kode' => 'C03',
        'maks_hari' => 0,
        'kuota' => null,
        'maks_bawa' => 0,
    ]);
});

it('renders the edit form with the current rules', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $cuti = cutiUntukCrud();

    $this->actingAs($admin)->get(route('admin.cuti.edit', $cuti))
        ->assertOk()
        ->assertSee('Ubah jenis cuti')
        ->assertSee(route('admin.cuti.update', $cuti), false)
        ->assertSee('value="C01"', false)
        ->assertSee('value="Cuti Tahunan"', false)
        ->assertSee('value="12"', false)
        ->assertSee('value="6"', false);
});

it('updates a cuti rule and flashes a success message', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $cuti = cutiUntukCrud();

    $this->actingAs($admin)->put(route('admin.cuti.update', $cuti), [
        'kode' => 'C01',
        'nama' => 'Cuti Tahunan Baru',
        'maks_hari' => 0,
        'kuota' => null,
        'maks_bawa' => 0,
    ])
        ->assertRedirect(route('admin.cuti.index'))
        ->assertSessionHas('success', 'Jenis cuti Cuti Tahunan Baru berhasil diperbarui.')
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('cuti', [
        'id' => $cuti->id,
        'kode' => 'C01',
        'nama' => 'Cuti Tahunan Baru',
        'maks_hari' => 0,
        'kuota' => null,
        'maks_bawa' => 0,
    ]);
});

it('rejects an update that uses another cuti kode', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $cuti = cutiUntukCrud();
    Cuti::factory()->create(['kode' => 'C02', 'nama' => 'Cuti Bersalin']);

    $this->actingAs($admin)->put(route('admin.cuti.update', $cuti), [
        ...dataCutiValid(),
        'kode' => 'C02',
    ])->assertSessionHasErrors('kode');

    expect($cuti->fresh()->kode)->toBe('C01');
});

it('deletes an unused cuti rule', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $cuti = cutiUntukCrud();

    $this->actingAs($admin)->delete(route('admin.cuti.destroy', $cuti))
        ->assertRedirect(route('admin.cuti.index'))
        ->assertSessionHas('success', 'Jenis cuti Cuti Tahunan berhasil dihapus.');

    $this->assertDatabaseMissing('cuti', ['id' => $cuti->id]);
});

it('refuses to delete a cuti rule referenced by pengajuan or saldo', function (string $jenis) {
    $admin = User::factory()->create(['role' => 'admin']);
    $cuti = cutiUntukCrud();

    if ($jenis === 'pengajuan') {
        Pengajuan::factory()->create(['id_cuti' => $cuti->id]);
    } else {
        SaldoCuti::factory()->create(['id_cuti' => $cuti->id]);
    }

    $this->actingAs($admin)->delete(route('admin.cuti.destroy', $cuti))
        ->assertRedirect(route('admin.cuti.index'))
        ->assertSessionHas('error', 'Jenis cuti Cuti Tahunan tidak bisa dihapus karena masih digunakan pengajuan atau saldo.');

    $this->assertModelExists($cuti);
})->with(['pengajuan', 'saldo']);

it('renders the flashed success and error messages on the index page', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->withSession(['success' => 'Jenis cuti berhasil ditambahkan.'])
        ->get(route('admin.cuti.index'))
        ->assertOk()
        ->assertSee('Jenis cuti berhasil ditambahkan.');

    $this->actingAs($admin)
        ->withSession(['error' => 'Jenis cuti tidak bisa dihapus.'])
        ->get(route('admin.cuti.index'))
        ->assertOk()
        ->assertSee('Jenis cuti tidak bisa dihapus.');
});
