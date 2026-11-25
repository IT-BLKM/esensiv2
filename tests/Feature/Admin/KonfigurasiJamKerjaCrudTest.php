<?php

use App\Models\JamKerja;
use App\Models\KonfigurasiJamKerja;
use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(fn () => $this->withoutVite());

/**
 * Senin–Jumat memakai shift, Sabtu–Minggu libur.
 *
 * @return array<int, array{hari: string, id_jam_kerja: int|null}>
 */
function dataJadwalLengkap(JamKerja $shift): array
{
    return collect(KonfigurasiJamKerja::HARI)
        ->map(fn (string $hari): array => [
            'hari' => $hari,
            'id_jam_kerja' => in_array($hari, ['Sabtu', 'Minggu'], true) ? null : $shift->id,
        ])
        ->all();
}

it('redirects guests to the login page on every konfigurasi route', function () {
    $pegawai = Pegawai::factory()->create();

    $this->get(route('admin.konfigurasi.index'))->assertRedirect(route('login'));
    $this->get(route('admin.konfigurasi.edit', $pegawai))->assertRedirect(route('login'));
    $this->put(route('admin.konfigurasi.update', $pegawai), [])->assertRedirect(route('login'));
});

it('keeps a pegawai out of every konfigurasi route', function () {
    $pegawai = Pegawai::factory()->create();
    $user = User::factory()->create(['role' => 'pegawai']);

    $this->actingAs($user)->get(route('admin.konfigurasi.index'))->assertRedirect('/dashboard');
    $this->actingAs($user)->get(route('admin.konfigurasi.edit', $pegawai))->assertRedirect('/dashboard');
    $this->actingAs($user)->put(route('admin.konfigurasi.update', $pegawai), [])->assertRedirect('/dashboard');
});

it('shows every employee with a compact seven day schedule', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $pegawai = Pegawai::factory()->create(['nama' => 'Budi Santoso']);
    $shift = JamKerja::factory()->create(['kode' => 'PAGI', 'nama' => 'Shift Pagi']);
    KonfigurasiJamKerja::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_jam_kerja' => $shift->id,
        'hari' => 'Senin',
    ]);

    $this->actingAs($admin)->get(route('admin.konfigurasi.index'))
        ->assertOk()
        ->assertSee('Budi Santoso')
        ->assertSee($pegawai->nip)
        ->assertSee('Senin')
        ->assertSee('Minggu')
        ->assertSee('PAGI')
        ->assertSee(route('admin.konfigurasi.edit', $pegawai), false)
        ->assertDontSee('Halaman kosong');
});

it('renders the form with all seven days and the stored shift selected', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $pegawai = Pegawai::factory()->create(['nama' => 'Budi Santoso']);
    $shift = JamKerja::factory()->create(['kode' => 'PAGI', 'nama' => 'Shift Pagi']);
    KonfigurasiJamKerja::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_jam_kerja' => $shift->id,
        'hari' => 'Rabu',
    ]);

    $this->actingAs($admin)->get(route('admin.konfigurasi.edit', $pegawai))
        ->assertOk()
        ->assertSee('Atur jam kerja Budi Santoso')
        ->assertSee('Pilih Jam Kerja')
        ->assertSee('Ringkasan jadwal')
        ->assertSee('value="'.$shift->id.'"', false)
        ->assertSee('name="jadwal[0][hari]"', false)
        ->assertSee('name="jadwal[6][id_jam_kerja]"', false)
        ->assertSee(route('admin.konfigurasi.update', $pegawai), false)
        ->assertSee('value="PUT"', false);
});

it('saves the schedule with one row per day and ignores a forged employee id', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $pegawai = Pegawai::factory()->create(['nama' => 'Budi Santoso']);
    $pegawaiLain = Pegawai::factory()->create();
    $shift = JamKerja::factory()->create(['kode' => 'PAGI']);

    $this->actingAs($admin)->put(route('admin.konfigurasi.update', $pegawai), [
        'id_pegawai' => $pegawaiLain->id,
        'jadwal' => dataJadwalLengkap($shift),
    ])
        ->assertRedirect(route('admin.konfigurasi.edit', $pegawai))
        ->assertSessionHas('success', 'Jadwal jam kerja Budi Santoso berhasil disimpan (5 hari).')
        ->assertSessionHasNoErrors();

    $this->assertDatabaseCount('konfigurasi_jam_kerja', 5);
    $this->assertDatabaseHas('konfigurasi_jam_kerja', [
        'id_pegawai' => $pegawai->id,
        'id_jam_kerja' => $shift->id,
        'hari' => 'Senin',
    ]);
    $this->assertDatabaseMissing('konfigurasi_jam_kerja', ['id_pegawai' => $pegawaiLain->id]);
    $this->assertDatabaseMissing('konfigurasi_jam_kerja', ['hari' => 'Sabtu']);
});

it('updates an existing day instead of inserting a duplicate row', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $pegawai = Pegawai::factory()->create();
    $shiftLama = JamKerja::factory()->create();
    $shiftBaru = JamKerja::factory()->create();
    KonfigurasiJamKerja::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_jam_kerja' => $shiftLama->id,
        'hari' => 'Senin',
    ]);

    $jadwal = dataJadwalLengkap($shiftBaru);
    $this->actingAs($admin)->put(route('admin.konfigurasi.update', $pegawai), ['jadwal' => $jadwal])
        ->assertSessionHasNoErrors();
    $this->actingAs($admin)->put(route('admin.konfigurasi.update', $pegawai), ['jadwal' => $jadwal])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseCount('konfigurasi_jam_kerja', 5);
    $this->assertSame(1, KonfigurasiJamKerja::query()
        ->where('id_pegawai', $pegawai->id)
        ->where('hari', 'Senin')
        ->count());
    $this->assertDatabaseHas('konfigurasi_jam_kerja', [
        'id_pegawai' => $pegawai->id,
        'id_jam_kerja' => $shiftBaru->id,
        'hari' => 'Senin',
    ]);
    $this->assertDatabaseMissing('konfigurasi_jam_kerja', ['id_jam_kerja' => $shiftLama->id]);
});

it('removes the schedule row when a day is left blank', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $pegawai = Pegawai::factory()->create();
    $shift = JamKerja::factory()->create();
    KonfigurasiJamKerja::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_jam_kerja' => $shift->id,
        'hari' => 'Sabtu',
    ]);

    $this->actingAs($admin)->put(route('admin.konfigurasi.update', $pegawai), [
        'jadwal' => dataJadwalLengkap($shift),
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseMissing('konfigurasi_jam_kerja', [
        'id_pegawai' => $pegawai->id,
        'hari' => 'Sabtu',
    ]);
});

it('rejects an incomplete or tampered schedule without writing anything', function (array $payload, string $field) {
    $admin = User::factory()->create(['role' => 'admin']);
    $pegawai = Pegawai::factory()->create();
    $shift = JamKerja::factory()->create();

    $this->actingAs($admin)->put(route('admin.konfigurasi.update', $pegawai), ['jadwal' => $payload])
        ->assertSessionHasErrors($field);

    $this->assertDatabaseEmpty('konfigurasi_jam_kerja');
})->with([
    'missing jadwal' => [[], 'jadwal'],
    'wrong number of days' => [[['hari' => 'Senin', 'id_jam_kerja' => 1]], 'jadwal'],
    'duplicate day' => [[
        ['hari' => 'Senin', 'id_jam_kerja' => 1],
        ['hari' => 'Senin', 'id_jam_kerja' => 1],
        ['hari' => 'Selasa', 'id_jam_kerja' => 1],
        ['hari' => 'Rabu', 'id_jam_kerja' => 1],
        ['hari' => 'Kamis', 'id_jam_kerja' => 1],
        ['hari' => 'Jumat', 'id_jam_kerja' => 1],
        ['hari' => 'Sabtu', 'id_jam_kerja' => 1],
    ], 'jadwal.1.hari'],
    'unknown day' => [[
        ['hari' => 'Senin', 'id_jam_kerja' => 1],
        ['hari' => 'Selasa', 'id_jam_kerja' => 1],
        ['hari' => 'Rabu', 'id_jam_kerja' => 1],
        ['hari' => 'Kamis', 'id_jam_kerja' => 1],
        ['hari' => 'Jumat', 'id_jam_kerja' => 1],
        ['hari' => 'Sabtu', 'id_jam_kerja' => 1],
        ['hari' => 'Libur', 'id_jam_kerja' => 1],
    ], 'jadwal.6.hari'],
    'unknown shift' => [[
        ['hari' => 'Senin', 'id_jam_kerja' => 99999],
        ['hari' => 'Selasa', 'id_jam_kerja' => null],
        ['hari' => 'Rabu', 'id_jam_kerja' => null],
        ['hari' => 'Kamis', 'id_jam_kerja' => null],
        ['hari' => 'Jumat', 'id_jam_kerja' => null],
        ['hari' => 'Sabtu', 'id_jam_kerja' => null],
        ['hari' => 'Minggu', 'id_jam_kerja' => null],
    ], 'jadwal.0.id_jam_kerja'],
]);

it('returns 404 for an employee that does not exist', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get(route('admin.konfigurasi.edit', 99999))->assertNotFound();
    $this->actingAs($admin)->put(route('admin.konfigurasi.update', 99999), [])->assertNotFound();
});
