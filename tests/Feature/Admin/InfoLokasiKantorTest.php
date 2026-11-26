<?php

use App\Models\Pegawai;
use App\Models\Pengaturan;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(fn () => $this->withoutVite());

/**
 * Nomor issue yang diuji di sini: #30 (info lokasi) dan #137 (admin dapat
 * mengubah koordinat/radius). Nilai awal selalu dibaca dari config(), bukan
 * ditulis literal, supaya test tetap benar walau .env tiap developer berbeda.
 */
function adminInfoLokasi(): User
{
    return User::factory()->create(['role' => 'admin']);
}

it('shows the office location and radius as editable fields', function () {
    $radius = (int) config('presensi.radius');

    $this->actingAs(adminInfoLokasi())->get(route('admin.konfigurasi.index'))
        ->assertOk()
        ->assertSee('Lokasi & radius absen')
        ->assertSee('Lintang (latitude)')
        ->assertSee((string) config('presensi.latitude'))
        ->assertSee('Bujur (longitude)')
        ->assertSee((string) config('presensi.longitude'))
        ->assertSee('Radius absen (meter)')
        ->assertSee('value="'.$radius.'"', false)
        ->assertSee('Simpan lokasi')
        ->assertSee('Buka di peta');
});

it('links the coordinates to a map without hardcoding a url literal', function () {
    $titik = urlencode(config('presensi.latitude').','.config('presensi.longitude'));

    $this->actingAs(adminInfoLokasi())->get(route('admin.konfigurasi.index'))
        ->assertOk()
        ->assertSee('https://www.google.com/maps/search/?api=1&amp;query='.$titik, false)
        ->assertSee('rel="noopener noreferrer"', false);
});

it('lets an admin save the office coordinates and radius', function () {
    $this->actingAs(adminInfoLokasi())
        ->put(route('admin.konfigurasi.lokasi.update'), [
            'latitude' => '-3.0123456',
            'longitude' => '104.7123456',
            'radius' => 2500,
        ])
        ->assertRedirect(route('admin.konfigurasi.index'))
        ->assertSessionHas('success', 'Lokasi dan radius kantor berhasil disimpan.');

    expect(Pengaturan::nilai('lokasi_latitude'))->toBe('-3.0123456')
        ->and(Pengaturan::nilai('lokasi_longitude'))->toBe('104.7123456')
        ->and(Pengaturan::nilai('lokasi_radius'))->toBe('2500');

    $this->actingAs(adminInfoLokasi())->get(route('admin.konfigurasi.index'))
        ->assertOk()
        ->assertSee('-3.0123456')
        ->assertSee('104.7123456')
        ->assertSee('2.500 m');
});

it('uses the saved location on the presensi page', function () {
    Pengaturan::simpan('lokasi_latitude', '-3.1');
    Pengaturan::simpan('lokasi_longitude', '104.7');
    Pengaturan::simpan('lokasi_radius', '75');

    $user = User::factory()->create(['role' => 'pegawai']);
    Pegawai::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->get(route('presensi.index'))
        ->assertOk()
        ->assertSee('data-kantor-latitude="-3.1"', false)
        ->assertSee('data-kantor-longitude="104.7"', false)
        ->assertSee('data-kantor-radius="75"', false);
});

it('rejects invalid office location input', function (array $data, string $field, string $message) {
    $this->actingAs(adminInfoLokasi())
        ->put(route('admin.konfigurasi.lokasi.update'), $data)
        ->assertSessionHasErrors([$field => $message]);

    expect(Pengaturan::query()->count())->toBe(0);
})->with([
    'lintang di luar rentang' => [
        ['latitude' => '-91', 'longitude' => '104.7', 'radius' => 2500],
        'latitude',
        'Lintang kantor harus berada di antara -90 dan 90.',
    ],
    'bujur di luar rentang' => [
        ['latitude' => '-3.0', 'longitude' => '181', 'radius' => 2500],
        'longitude',
        'Bujur kantor harus berada di antara -180 dan 180.',
    ],
    'radius nol' => [
        ['latitude' => '-3.0', 'longitude' => '104.7', 'radius' => 0],
        'radius',
        'Radius absen minimal 1 meter.',
    ],
    'radius bukan angka bulat' => [
        ['latitude' => '-3.0', 'longitude' => '104.7', 'radius' => '12.5'],
        'radius',
        'Radius absen harus berupa angka bulat.',
    ],
]);

it('forbids guests and pegawai from changing the office location', function () {
    $this->put(route('admin.konfigurasi.lokasi.update'), [
        'latitude' => '-3.0',
        'longitude' => '104.7',
        'radius' => 2500,
    ])->assertRedirect(route('login'));

    $pegawai = User::factory()->create(['role' => 'pegawai']);

    $this->actingAs($pegawai)->put(route('admin.konfigurasi.lokasi.update'), [
        'latitude' => '-3.0',
        'longitude' => '104.7',
        'radius' => 2500,
    ])->assertRedirect('/dashboard');

    expect(Pengaturan::query()->count())->toBe(0);
});
